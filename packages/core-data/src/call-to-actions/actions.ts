import { cloneDeep, mergeWith } from 'lodash';
import { compare as jsonpatchCompare } from 'fast-json-patch';

import { __, sprintf } from '@popup-maker/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { decodeEntities } from '@wordpress/html-entities';

import { ACTION_TYPES, NOTICE_CONTEXT } from './constants';

import { DispatchStatus } from '../constants';
import { fetchFromApi, getErrorMessage } from '../utils';
import { validateCallToAction } from './validation';
import { editableEntity } from './utils';
import type { EditorId, Notice } from '../types';
import type {
	CallToAction,
	ThunkAction,
	EditableCta,
	PartialEditableCta,
} from './types';
import type { EditRecordAction } from './reducer';

const {
	RECEIVE_RECORD,
	PURGE_RECORD,
	EDITOR_CHANGE_ID,
	EDIT_RECORD,
	START_EDITING_RECORD,
	SAVE_EDITED_RECORD,
	UNDO_EDIT_RECORD,
	REDO_EDIT_RECORD,
	RESET_EDIT_RECORD,
	CHANGE_ACTION_STATUS,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

/**
 * Helper function to handle field-specific validation errors using WordPress notices
 *
 * @param {any}      error    The error object from the API
 * @param {number}   ctaId    The CTA ID for field error association
 * @param {Function} dispatch The dispatch function with createErrorNotice
 * @param {any}      registry The registry for accessing stores
 */
const handleFieldValidationErrors = (
	error: any,
	ctaId: number | undefined,
	dispatch: any,
	registry: any
) => {
	// Handle field-specific validation errors
	if ( error?.code === 'rest_invalid_param' && error?.data?.params ) {
		// Clear previous field errors for this CTA
		if ( ctaId ) {
			const notices = registry
				.select( noticesStore )
				.getNotices( NOTICE_CONTEXT );
			const fieldErrors = notices.filter(
				( n: Notice ) => n.id?.startsWith( `field-error-${ ctaId }-` )
			);
			fieldErrors.forEach( ( n: Notice ) =>
				registry
					.dispatch( noticesStore )
					.removeNotice( n.id, NOTICE_CONTEXT )
			);
		}

		// Create notices for each field error
		Object.entries( error.data.params ).forEach( ( [ field, message ] ) => {
			// Check if this is a nested field error with details
			if ( field === 'settings' && error.data.details?.settings ) {
				const settingsError = error.data.details.settings;

				// Handle the primary error
				if ( settingsError.data?.field ) {
					dispatch.createErrorNotice( settingsError.message, {
						id: `field-error-${ ctaId || 'new' }-${
							settingsError.data.field
						}`,
						isDismissible: false,
						type: 'default', // Prevent auto-dismiss
					} );
				}

				// Handle additional errors
				if (
					settingsError.additional_errors &&
					Array.isArray( settingsError.additional_errors )
				) {
					settingsError.additional_errors.forEach(
						( additionalError: any ) => {
							if (
								additionalError.data?.field &&
								additionalError.message
							) {
								dispatch.createErrorNotice(
									additionalError.message,
									{
										id: `field-error-${ ctaId || 'new' }-${
											additionalError.data.field
										}`,
										isDismissible: false,
										type: 'default', // Prevent auto-dismiss
									}
								);
							}
						}
					);
				}
			} else {
				// Regular field error
				dispatch.createErrorNotice( message as string, {
					id: `field-error-${ ctaId || 'new' }-${ field }`,
					isDismissible: false,
					type: 'default', // Prevent auto-dismiss
				} );
			}
		} );
		return true;
	}
	return false;
};

/*****************************************************
 * SECTION: Entity actions
 *****************************************************/
const entityActions = {
	/**
	 * Create a new entity record. Values sent to the server immediately.
	 *
	 * @param {Editable} callToAction The entity to create.
	 * @param {boolean}  validate     An optional validation function.
	 * @param {boolean}  withNotices  Whether to show notices.
	 * @return {Promise<CallToAction< 'edit' > | false>} The created entity or false if validation fails.
	 */
	createCallToAction:
		(
			callToAction: Partial< EditableCta >,
			validate: boolean = true,
			withNotices: boolean = true
		): ThunkAction< CallToAction< 'edit' > | false > =>
		async ( { dispatch, registry } ) => {
			const action = 'createCallToAction';

			try {
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Resolving,
					},
				} );

				const { id, ...newCta } = callToAction;

				if ( validate ) {
					const validation = validateCallToAction( newCta );

					if ( true !== validation ) {
						dispatch( {
							type: CHANGE_ACTION_STATUS,
							payload: {
								actionName: action,
								status: DispatchStatus.Error,
								message: validation.message,
							},
						} );
						if ( withNotices ) {
							await dispatch.createErrorNotice(
								validation.message,
								{
									id: 'call-to-action-validation-error',
								}
							);
						}

						return false;
					}
				}

				const result = await fetchFromApi< CallToAction< 'edit' > >(
					`ctas?context=edit`,
					{
						method: 'POST',
						data: newCta,
					}
				);

				if ( result ) {
					registry.batch( () => {
						dispatch( {
							type: CHANGE_ACTION_STATUS,
							payload: {
								actionName: action,
								status: DispatchStatus.Success,
							},
						} );

						if ( withNotices ) {
							dispatch.createSuccessNotice(
								sprintf(
									// translators: %s: call to action title.
									__(
										'Call to action "%s" saved successfully.',
										'popup-maker'
									),
									decodeEntities( result?.title.rendered )
								),
								{
									id: 'call-to-action-saved',
								}
							);
						}

						dispatch( {
							type: RECEIVE_RECORD,
							payload: {
								record: result,
							},
						} );
					} );

					return result;
				}

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Error,
						message: __(
							'An error occurred, call to action was not saved.',
							'popup-maker'
						),
					},
				} );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Error,
						message: errorMessage,
					},
				} );

				if ( withNotices ) {
					// Generate a generic error notice.
					dispatch.createErrorNotice( errorMessage );
				}

				throw error;
			}

			return false;
		},

	/**
	 * Update an existing entity record. Values sent to the server immediately.
	 *
	 * @param {PartialEditableCta} callToAction The entity to update.
	 * @param {boolean}            validate     An optional validation function.
	 * @param {boolean}            withNotices  Whether to show notices.
	 * @return {Promise<T | boolean>} The updated entity or false if validation fails.
	 */
	updateCallToAction:
		(
			callToAction: PartialEditableCta,
			validate: boolean = true,
			withNotices: boolean = true
		): ThunkAction< CallToAction< 'edit' > | false > =>
		async ( { select, dispatch, registry } ) => {
			const action = 'updateCallToAction';

			try {
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Resolving,
					},
				} );

				if ( validate ) {
					const validation = validateCallToAction( callToAction );

					if ( true !== validation ) {
						dispatch( {
							type: CHANGE_ACTION_STATUS,
							payload: {
								actionName: action,
								status: DispatchStatus.Error,
								message: validation.message,
							},
						} );

						if ( withNotices ) {
							await dispatch.createErrorNotice(
								validation.message,
								{
									id: 'call-to-action-validation-error',
								}
							);
						}

						return false;
					}
				}

				const canonicalCallToAction = await select.getCallToAction(
					callToAction.id
				);

				if ( ! canonicalCallToAction ) {
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						payload: {
							actionName: action,
							status: DispatchStatus.Error,
							message: __(
								'Call to action not found',
								'popup-maker'
							),
						},
					} );

					return false;
				}

				const result = await fetchFromApi< CallToAction< 'edit' > >(
					`ctas/${ canonicalCallToAction.id }`,
					{
						method: 'POST',
						data: callToAction,
					}
				);

				if ( result ) {
					registry.batch( () => {
						// dispatch.finishResolution( action, operation );
						dispatch( {
							type: CHANGE_ACTION_STATUS,
							payload: {
								actionName: action,
								status: DispatchStatus.Success,
							},
						} );

						if ( withNotices ) {
							dispatch.createSuccessNotice(
								sprintf(
									// translators: %s: call to action title.
									__(
										'Call to action "%s" updated successfully.',
										'popup-maker'
									),
									decodeEntities( result?.title.rendered )
								),
								{
									id: 'call-to-action-saved',
								}
							);
						}

						dispatch( {
							type: RECEIVE_RECORD,
							payload: {
								record: result,
							},
						} );
					} );

					return result;
				}

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Error,
						message: __(
							'An error occurred, call to action was not saved.',
							'popup-maker'
						),
					},
				} );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Error,
						message: errorMessage,
					},
				} );

				if ( withNotices ) {
					// Generate a generic error notice.
					dispatch.createErrorNotice( errorMessage );
				}

				throw error;
			}

			return false;
		},

	/**
	 * Delete an existing entity record.
	 *
	 * @param {number}  id          The entity ID.
	 * @param {boolean} forceDelete Whether to force the deletion.
	 * @param {boolean} withNotices Whether to show notices.
	 * @return {Promise<boolean>} Whether the deletion was successful.
	 */
	deleteCallToAction:
		(
			id: number,
			forceDelete: boolean = false,
			withNotices: boolean = true
		): ThunkAction< boolean > =>
		async ( { dispatch, registry } ) => {
			const action = 'deleteCallToAction';

			try {
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Resolving,
					},
				} );

				// Get the canonical directly from server to verify it exists.
				const canonicalCallToAction = await fetchFromApi<
					CallToAction< 'edit' >
				>( `ctas/${ id }?context=edit` );

				if ( ! canonicalCallToAction ) {
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						payload: {
							actionName: action,
							status: DispatchStatus.Error,
							message: __(
								'Call to action not found',
								'popup-maker'
							),
						},
					} );

					return false;
				}

				const force = forceDelete ? '?force=true' : '';

				const result = await fetchFromApi< boolean >(
					`ctas/${ id }${ force }`,
					{
						method: 'DELETE',
					}
				);

				if ( result ) {
					registry.batch( () => {
						dispatch( {
							type: CHANGE_ACTION_STATUS,
							payload: {
								actionName: action,
								status: DispatchStatus.Success,
							},
						} );

						if ( withNotices ) {
							dispatch.createSuccessNotice(
								sprintf(
									// translators: %s: call to action title.
									__(
										'Call to action "%s" deleted successfully.',
										'popup-maker'
									),
									decodeEntities(
										canonicalCallToAction?.title.rendered
									)
								),
								{
									id: 'call-to-action-deleted',
								}
							);
						}

						if ( forceDelete ) {
							dispatch( {
								type: PURGE_RECORD,
								payload: {
									id,
								},
							} );
						} else {
							dispatch( {
								type: RECEIVE_RECORD,
								payload: {
									record: {
										...canonicalCallToAction,
										status: 'trash',
									},
								},
							} );
						}
					} );
				}

				return result;
			} catch ( error ) {
				// await dispatch.failResolution( action, operation );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Error,
						message: __( 'Failed to delete entity', 'popup-maker' ),
					},
				} );

				if ( withNotices ) {
					await dispatch.createErrorNotice(
						error instanceof Error
							? error.message
							: __( 'Failed to delete entity', 'popup-maker' )
					);
				}

				throw error;
			}
		},
};

/*****************************************************
 * SECTION: Editor actions
 *****************************************************/
const editorActions = {
	/**
	 * Edit an existing entity record. Values are not sent to the server until save.
	 *
	 * @param {number}               id    The entity ID.
	 * @param {Partial<EditableCta>} edits The edits to apply.
	 * @return {Promise<boolean>} Whether the edit was successful.
	 */
	editRecord:
		( id: number, edits: Partial< EditableCta > ): ThunkAction =>
		async ( { select, dispatch, registry } ) => {
			try {
				let canonicalCallToAction: EditableCta | undefined;
				const hasEditedEntity = select.hasEditedEntity( id );

				if ( hasEditedEntity ) {
					canonicalCallToAction = select.getEditedCallToAction(
						id
					) as EditableCta;
				} else {
					canonicalCallToAction = await fetchFromApi<
						CallToAction< 'edit' > & { _links: any }
					>( `ctas/${ id }?context=edit` ).then( ( result ) =>
						// Convert to editable entity if found.
						result
							? editableEntity< CallToAction< 'edit' > >( result )
							: undefined
					);
					if ( ! canonicalCallToAction ) {
						return;
					}
				}

				registry.batch( async () => {
					if ( ! hasEditedEntity ) {
						await dispatch( {
							type: START_EDITING_RECORD,
							payload: {
								id,
								editableEntity: canonicalCallToAction,
							},
						} );
					}

					// Create a new object with the edits deeply merged into the canonical entity
					// First clone the canonical entity, then deep merge the edits
					const editedEntity = mergeWith(
						{},
						cloneDeep( canonicalCallToAction ),
						edits,
						( _objValue, srcValue ) => {
							if ( Array.isArray( srcValue ) ) {
								// Always replace arrays completely, even if empty
								return srcValue.slice();
							}
							return undefined;
						}
					);

					// Force patches for empty arrays to ensure they are saved
					const diff = jsonpatchCompare(
						canonicalCallToAction ?? {},
						editedEntity
					);

					await dispatch( {
						type: EDIT_RECORD,
						payload: {
							id,
							edits: diff,
						},
					} as EditRecordAction );
				} );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// eslint-disable-next-line no-console
				console.error( 'Edit failed:', error );

				await dispatch.createErrorNotice( errorMessage );
			}
		},

	/**
	 * Save an edited entity record.
	 *
	 * @param {number}  id          The entity ID.
	 * @param {boolean} validate    An optional validation function.
	 * @param {boolean} withNotices Whether to show notices.
	 * @return {Promise<boolean>} Whether the save was successful.
	 */
	saveEditedRecord:
		(
			id: number,
			validate: boolean = true,
			withNotices: boolean = true
		): ThunkAction< boolean > =>
		async ( { select, dispatch, registry } ) => {
			const action = 'saveRecord';

			try {
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Resolving,
					},
				} );

				if ( ! select.hasEdits( id ) ) {
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						payload: {
							actionName: action,
							status: DispatchStatus.Error,
							message: __( 'No edits to save', 'popup-maker' ),
						},
					} );

					return false;
				}

				const historyIndex = select.getCurrentEditHistoryIndex( id );
				const editedCallToAction = select.getEditedCallToAction( id );

				if ( ! editedCallToAction ) {
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						payload: {
							actionName: action,
							status: DispatchStatus.Error,
							message: __( 'No edits to save', 'popup-maker' ),
						},
					} );

					return false;
				}

				if ( editedCallToAction && validate ) {
					const validation =
						validateCallToAction( editedCallToAction );

					if ( true !== validation ) {
						registry.batch( async () => {
							dispatch( {
								type: CHANGE_ACTION_STATUS,
								payload: {
									actionName: action,
									status: DispatchStatus.Error,
									message: validation.message,
								},
							} );

							if ( withNotices ) {
								await dispatch.createErrorNotice(
									validation.message,
									{
										id: 'call-to-action-validation-error',
									}
								);
							}
						} );

						return false;
					}
				}

				const result = await dispatch.updateCallToAction(
					editedCallToAction,
					false,
					false
				);

				if ( result ) {
					registry.batch( () => {
						dispatch( {
							type: CHANGE_ACTION_STATUS,
							payload: {
								actionName: action,
								status: DispatchStatus.Success,
							},
						} );

						if ( withNotices ) {
							dispatch.createSuccessNotice(
								sprintf(
									// translators: %s: call to action title.
									__(
										'Call to action "%s" saved successfully.',
										'popup-maker'
									),
									decodeEntities( result?.title.rendered )
								),
								{
									id: 'call-to-action-saved',
								}
							);
						}

						dispatch( {
							type: SAVE_EDITED_RECORD,
							payload: {
								id,
								historyIndex,
								editedEntity: editableEntity( result ),
							},
						} );
					} );

					return true;
				}

				return false;
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// eslint-disable-next-line no-console
				console.error( 'Save failed:', error );

				registry.batch( async () => {
					if ( withNotices ) {
						await dispatch.createErrorNotice( errorMessage );
					}

					dispatch( {
						type: CHANGE_ACTION_STATUS,
						payload: {
							actionName: action,
							status: DispatchStatus.Error,
							message: errorMessage,
						},
					} );
				} );

				throw error;
			}
		},

	/**
	 * Undo the last action.
	 *
	 * @param {number} id    The entity ID.
	 * @param {number} steps The number of steps to undo.
	 * @return {Promise<void>}
	 */
	undo:
		( id: number, steps: number = 1 ): ThunkAction =>
		async ( { select, dispatch } ) => {
			const ctaId = id > 0 ? id : select.getEditorId();

			if ( typeof ctaId === 'undefined' ) {
				return;
			}

			await dispatch( {
				type: UNDO_EDIT_RECORD,
				payload: {
					id: ctaId,
					steps,
				},
			} );
		},

	/**
	 * Redo the last action.
	 *
	 * @param {number} id    The entity ID.
	 * @param {number} steps The number of steps to redo.
	 * @return {Promise<void>}
	 */
	redo:
		( id: number, steps: number = 1 ): ThunkAction =>
		async ( { select, dispatch } ) => {
			const ctaId = id > 0 ? id : select.getEditorId();

			if ( typeof ctaId === 'undefined' ) {
				return;
			}

			await dispatch( {
				type: REDO_EDIT_RECORD,
				payload: {
					id: ctaId,
					steps,
				},
			} );
		},

	/**
	 * Reset the edits for an entity record.
	 *
	 * @param {number} id The entity ID.
	 * @return {Promise<void>}
	 */
	resetRecordEdits:
		( id: number ): ThunkAction =>
		async ( { select, dispatch } ) => {
			const ctaId = id > 0 ? id : select.getEditorId();

			if ( typeof ctaId === 'undefined' ) {
				return;
			}

			dispatch( {
				type: RESET_EDIT_RECORD,
				payload: {
					id: ctaId,
				},
			} );
		},

	/**
	 * Update the editor values.
	 *
	 * @param {Partial<EditableCta>} values The values to update.
	 * @return {Promise<void>}
	 */
	updateEditorValues:
		( values: PartialEditableCta ): ThunkAction< void > =>
		async ( { dispatch, select } ) => {
			const editorId = select.getEditorId();

			if ( typeof editorId === 'undefined' ) {
				return;
			}

			dispatch.editRecord( editorId, values );
		},

	/**
	 * Save the editor values.
	 *
	 * @return {Promise<boolean>} Whether the save was successful.
	 */
	saveEditorValues:
		(): ThunkAction< boolean > =>
		async ( { dispatch, select } ) => {
			const editorId = select.getEditorId();
			const editorValues = select.getCurrentEditorValues();

			if ( ! editorId || ! editorValues ) {
				dispatch.createErrorNotice(
					__( 'No editor values to save', 'popup-maker' )
				);
				return false;
			}

			return dispatch.saveEditedRecord( editorId );
		},

	/**
	 * Reset the editor values.
	 *
	 * @return {Promise<void>}
	 */
	resetEditorValues:
		(): ThunkAction< void > =>
		async ( { dispatch, select } ) => {
			const editorId = select.getEditorId();

			if ( typeof editorId === 'undefined' ) {
				return;
			}

			dispatch.resetRecordEdits( editorId );
		},

	/**
	 * Change the editor ID.
	 *
	 * @param {EditorId} editorId The editor ID.
	 * @return {Promise<void>}
	 */
	changeEditorId:
		( editorId: EditorId ): ThunkAction< void > =>
		async ( { select, dispatch } ) => {
			try {
				if ( typeof editorId === 'undefined' ) {
					dispatch( {
						type: EDITOR_CHANGE_ID,
						payload: {
							editorId: undefined,
						},
					} );
					return;
				}

				if ( ! select.hasEditedEntity( editorId ) ) {
					const entity = await fetchFromApi< CallToAction< 'edit' > >(
						`ctas/${ editorId }?context=edit`
					);

					if ( ! entity ) {
						dispatch.createErrorNotice(
							__( 'Call to action not found', 'popup-maker' )
						);
						return;
					}

					dispatch( {
						type: START_EDITING_RECORD,
						payload: {
							id: editorId,
							editableEntity: editableEntity( entity ),
							setEditorId: true,
						},
					} );
				} else {
					dispatch( {
						type: EDITOR_CHANGE_ID,
						payload: {
							editorId,
						},
					} );
				}
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// eslint-disable-next-line no-console
				console.error( 'Failed to change editor ID:', error );

				dispatch.createErrorNotice( errorMessage );
			}
		},
};

/*****************************************************
 * SECTION: Notice actions
 *****************************************************/
const noticeActions = {
	/**
	 * Create a notice.
	 *
	 * @param {Notice[ 'status' ]}  status  The notice status.
	 * @param {Notice[ 'content' ]} content The notice content.
	 * @param {Notice}              options The notice options.
	 * @return {Promise<void>}
	 */
	createNotice:
		(
			/**
			 * Notice status.
			 */
			status: Notice[ 'status' ] = 'info',
			/**
			 * Notice content.
			 */
			content: Notice[ 'content' ] = '',
			/**
			 * Notice options.
			 */
			options?: Notice
		): ThunkAction =>
		async ( { registry } ) => {
			registry.dispatch( noticesStore ).createNotice( status, content, {
				...options,
				context: NOTICE_CONTEXT,
			} );
		},

	/**
	 * Create an error notice.
	 *
	 * @param {string}                             content The notice content.
	 * @param {Omit<Notice, 'status' | 'content'>} options The notice options.
	 * @return {Promise<void>}
	 */
	createErrorNotice:
		(
			/**
			 * Notice content.
			 */
			content: string,
			/**
			 * Notice options.
			 */
			options?: Omit< Notice, 'status' | 'content' >
		): ThunkAction =>
		async ( { registry } ) => {
			registry.dispatch( noticesStore ).createNotice( 'error', content, {
				...options,
				context: NOTICE_CONTEXT,
			} );
		},

	/**
	 * Create a success notice.
	 *
	 * @param {string}                             content The notice content.
	 * @param {Omit<Notice, 'status' | 'content'>} options The notice options.
	 * @return {Promise<void>}
	 */
	createSuccessNotice:
		(
			/**
			 * Notice content.
			 */
			content: string,
			/**
			 * Notice options.
			 */
			options?: Omit< Notice, 'status' | 'content' >
		): ThunkAction =>
		async ( { registry } ) => {
			registry
				.dispatch( noticesStore )
				.createNotice( 'success', content, {
					...options,
					context: NOTICE_CONTEXT,
				} );
		},

	/**
	 * Remove a notice for a given context.
	 *
	 * @param {string} id The notice ID.
	 * @return {Promise<void>}
	 */
	removeNotice:
		( id: string ): ThunkAction =>
		async ( { registry } ) => {
			registry
				.dispatch( noticesStore )
				.removeNotice( id, NOTICE_CONTEXT );
		},

	/**
	 * Remove all notices for a given context.
	 *
	 * @param {string[]} ids The notice IDs.
	 * @return {Promise<void>}
	 */
	removeAllNotices:
		( ids?: string[] ): ThunkAction =>
		async ( { registry } ) => {
			if ( ids ) {
				registry
					.dispatch( noticesStore )
					.removeNotices( ids, NOTICE_CONTEXT );
			} else {
				const notices = registry
					.select( noticesStore )
					.getNotices( NOTICE_CONTEXT );
				const noticeIds = notices.map( ( notice ) => notice.id );
				registry
					.dispatch( noticesStore )
					.removeNotices( noticeIds, NOTICE_CONTEXT );
			}
		},
};

/*****************************************************
 * SECTION: Resolution actions
 *****************************************************/
const resolutionActions = {
	/**
	 * Change status of a dispatch action request.
	 *
	 * @param {CallToActionsStore[ 'ActionNames' ]} actionName Action name to change status of.
	 * @param {Statuses}                            status     New status.
	 * @param {string|undefined}                    message    Optional error message.
	 * @return {Object} Action object.
	 */
	changeActionStatus:
		(
			actionName: string,
			status: DispatchStatus,
			message?: string | { message: string; [ key: string ]: any }
		): ThunkAction =>
		( { dispatch } ) => {
			if ( message ) {
				// eslint-disable-next-line no-console
				console.log( actionName, message );
			}

			dispatch( {
				type: CHANGE_ACTION_STATUS,
				payload: {
					actionName,
					status,
					message,
				},
			} );
		},

	/**
	 * Start resolution for an entity.
	 */
	// startResolution:
	// 	( id: number | string, operation: string = 'fetch' ) =>
	// 	( { dispatch } ) => {
	// 		console.log( 'startResolution', id, operation );
	// 		dispatch( {
	// 			type: START_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				operation,
	// 			},
	// 		} );
	// 	},

	/**
	 * Finish resolution for an entity.
	 */
	// finishResolution:
	// 	( id: number | string, operation: string = 'fetch' ) =>
	// 	( { dispatch } ) => {
	// 		dispatch( {
	// 			type: FINISH_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				operation,
	// 			},
	// 		} );
	// 	},

	/**
	 * Fail resolution for an entity.
	 */
	// failResolution:
	// 	(
	// 		id: number | string,
	// 		error: string,
	// 		operation: string = 'fetch',
	// 		extra?: Record< string, any >
	// 	): ThunkAction =>
	// 	( { dispatch } ) => {
	// 		dispatch( {
	// 			type: FAIL_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				error,
	// 				operation,
	// 				extra,
	// 			},
	// 		} );
	// 	},

	/**
	 * Invalidate resolution for an entity.
	 *
	 * @param {number | string} id The entity ID.
	 * @return {Promise<void>}
	 */
	invalidateResolution:
		( id: number | string ): ThunkAction =>
		( { dispatch } ) => {
			dispatch( {
				type: INVALIDATE_RESOLUTION,
				payload: {
					id,
				},
			} );
		},
};

const actions = {
	// Entity actions
	...entityActions,
	// Notice actions
	...noticeActions,
	// Editor actions
	...editorActions,
	// Resolution state actions
	...resolutionActions,
};

export default actions;
