import { __, sprintf } from '@popup-maker/i18n';
import { store as noticesStore } from '@wordpress/notices';

import { ACTION_TYPES, NOTICE_CONTEXT } from './constants';

import type { EditorId, Notice } from '../types';
import type {
	Popup,
	ThunkAction,
	EditablePopup,
	PartialEditablePopup,
} from './types';
import { DispatchStatus } from '../constants';
import { fetchFromApi, getErrorMessage } from '../utils';
import { validatePopup } from './validation';
import { editableEntity } from './utils';

const {
	RECIEVE_RECORD,
	PURGE_RECORD,
	EDITOR_CHANGE_ID,
	EDIT_RECORD,
	START_EDITING_RECORD,
	SAVE_EDITED_RECORD,
	UNDO_EDIT_RECORD,
	REDO_EDIT_RECORD,
	RESET_EDIT_RECORD,
	CHANGE_ACTION_STATUS,
	// START_RESOLUTION,
	// FINISH_RESOLUTION,
	// FAIL_RESOLUTION,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

/*****************************************************
 * SECTION: Entity actions
 *****************************************************/
const entityActions = {
	/**
	 * Create a new entity record. Values sent to the server immediately.
	 *
	 * @param {EditablePopup} callToAction The entity to create.
	 * @param {Function}      validate     An optional validation function.
	 * @return {Promise<EditablePopup | boolean>} The created entity or false if validation fails.
	 */
	createPopup:
		(
			callToAction: Partial< EditablePopup >,
			validate: boolean = true
		): ThunkAction< Popup< 'edit' > | false > =>
		async ( { dispatch } ) => {
			const action = 'createPopup';

			try {
				// dispatch.startResolution( action, operation );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Resolving,
				} );

				const { id, ...newCta } = callToAction;

				if ( validate ) {
					const validation = validatePopup( newCta );

					if ( true !== validation ) {
						// dispatch.failResolution(
						// 	action,
						// 	validation.message,
						// 	operation,
						// 	validation
						// );
						dispatch( {
							type: CHANGE_ACTION_STATUS,
							actionName: action,
							status: DispatchStatus.Error,
							message: validation.message,
						} );
						// TODO REVIEW: Do we need to handle this with a notice, or can we just get the message from resolution status?
						await dispatch.createErrorNotice( validation.message, {
							id: 'call-to-action-validation-error',
							closeDelay: 5000,
						} );

						return false;
					}
				}

				const result = await fetchFromApi< Popup< 'edit' > >(
					// TODO REVIEW: Is context=edit needed?
					`ctas?context=edit`,
					{
						method: 'POST',
						data: newCta,
					}
				);

				if ( result ) {
					// dispatch.finishResolution( action, operation );
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Success,
					} );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: call to action title.
							__(
								'Call to action "%s" saved successfully.',
								'popup-maker'
							),
							result?.title.rendered
						),
						{
							id: 'call-to-action-saved',
							closeDelay: 5000,
						}
					);

					dispatch( {
						type: RECIEVE_RECORD,
						payload: {
							record: result,
						},
					} );

					return result;
				}

				// dispatch.failResolution(
				// 	action,
				// 	__(
				// 		'An error occurred, call to action was not saved.',
				// 		'popup-maker'
				// 	),
				// 	operation
				// );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Error,
					message: __(
						'An error occurred, call to action was not saved.',
						'popup-maker'
					),
				} );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// Mark resolution failed.
				// dispatch.failResolution(
				// 	'createPopup',
				// 	errorMessage,
				// 	'POST'
				// );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Error,
					message: errorMessage,
				} );

				// Generate a generic error notice.
				dispatch.createErrorNotice( errorMessage );

				throw error;
			}

			return false;
		},

	/**
	 * Update an existing entity record. Values sent to the server immediately.
	 *
	 * @param {Partial<EditablePopup>&{id:EditablePopup[ 'id' ]}} callToAction The entity to update.
	 * @param {Function}                                          validate     An optional validation function.
	 * @return {Promise<T | boolean>} The updated entity or false if validation fails.
	 */
	updatePopup:
		(
			callToAction: Partial< EditablePopup > & { id: number },
			validate: boolean = true
		): ThunkAction< Popup< 'edit' > | false > =>
		async ( { select, dispatch } ) => {
			const action = 'updatePopup';

			try {
				// dispatch.startResolution( action, operation );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Resolving,
				} );

				if ( validate ) {
					const validation = validatePopup( callToAction );

					if ( true !== validation ) {
						// dispatch.failResolution(
						// 	action,
						// 	validation.message,
						// 	operation,
						// 	validation
						// );

						dispatch( {
							type: CHANGE_ACTION_STATUS,
							actionName: action,
							status: DispatchStatus.Error,
							message: validation.message,
						} );
						// TODO REVIEW: Do we need to handle this with a notice, or can we just get the message from resolution status?
						await dispatch.createErrorNotice( validation.message, {
							id: 'call-to-action-validation-error',
							closeDelay: 5000,
						} );

						return false;
					}
				}

				const canonicalPopup = await select.getPopup( callToAction.id );

				if ( ! canonicalPopup ) {
					// dispatch.failResolution(
					// 	action,
					// 	__( 'Call to action not found', 'popup-maker' ),
					// 	operation
					// );

					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Error,
						message: __(
							'Call to action not found',
							'popup-maker'
						),
					} );

					return false;
				}

				// TODO REVIEW: Test the return types of each of these calls so we can be sure.
				const result = await fetchFromApi< Popup< 'edit' > >(
					`ctas/${ canonicalPopup.id }`,
					{
						method: 'POST',
						data: callToAction,
					}
				);

				if ( result ) {
					// dispatch.finishResolution( action, operation );
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Success,
					} );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: call to action title.
							__(
								'Call to action "%s" updated successfully.',
								'popup-maker'
							),
							result?.title.rendered
						),
						{
							id: 'call-to-action-saved',
							closeDelay: 5000,
						}
					);

					dispatch( {
						type: RECIEVE_RECORD,
						payload: {
							record: result,
						},
					} );

					return result;
				}

				// dispatch.failResolution(
				// 	action,
				// 	__(
				// 		'An error occurred, call to action was not saved.',
				// 		'popup-maker'
				// 	),
				// 	operation
				// );

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Error,
					message: __(
						'An error occurred, call to action was not saved.',
						'popup-maker'
					),
				} );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// Mark resolution failed.
				// dispatch.failResolution( action, errorMessage, operation );

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Error,
					message: errorMessage,
				} );

				// Generate a generic error notice.
				dispatch.createErrorNotice( errorMessage );

				throw error;
			}

			return false;
		},

	/**
	 * Delete an existing entity record.
	 *
	 * @param {number}  id          The entity ID.
	 * @param {boolean} forceDelete Whether to force the deletion.
	 * @return {Promise<boolean>} Whether the deletion was successful.
	 */
	deletePopup:
		( id: number, forceDelete: boolean = false ): ThunkAction< boolean > =>
		async ( { dispatch } ) => {
			const action = 'deletePopup';

			try {
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Resolving,
				} );

				// Get the canonical directly from server to verify it exists.
				// TODO REVIEW: Test this.
				const canonicalPopup = await fetchFromApi< Popup< 'edit' > >(
					`ctas/${ id }?context=edit`
				);

				if ( ! canonicalPopup ) {
					// dispatch.failResolution(
					// 	action,
					// 	__( 'Call to action not found', 'popup-maker' ),
					// 	operation
					// );

					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Error,
						message: __(
							'Call to action not found',
							'popup-maker'
						),
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
					// dispatch.finishResolution( action, operation );
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Success,
					} );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: call to action title.
							__(
								'Call to action "%s" deleted successfully.',
								'popup-maker'
							),
							canonicalPopup?.title.rendered
						),
						{
							id: 'call-to-action-deleted',
							closeDelay: 5000,
						}
					);

					if ( forceDelete ) {
						dispatch( {
							type: PURGE_RECORD,
							payload: {
								id,
							},
						} );
					} else {
						dispatch( {
							type: RECIEVE_RECORD,
							payload: {
								record: {
									...canonicalPopup,
									status: 'trash',
								},
							},
						} );
					}
				}

				return result;
			} catch ( error ) {
				// await dispatch.failResolution( action, operation );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Error,
					message: __( 'Call to action not found', 'popup-maker' ),
				} );

				await dispatch.createErrorNotice(
					error instanceof Error
						? error.message
						: __( 'Failed to delete entity', 'popup-maker' )
				);
				throw error;
			}
		},
};

/*****************************************************
 * SECTION: Editor actions
 * REVIEW: ALL OF THESE ACTIONS NEED TO BE REFACTORED TO USE THE NEW THUNK ACTIONS.
 *****************************************************/
const editorActions = {
	/**
	 * Edit an existing entity record. Values are not sent to the server until save.
	 *
	 * @param {number}                 id    The entity ID.
	 * @param {Partial<EditablePopup>} edits The edits to apply.
	 * @return {Promise<boolean>} Whether the edit was successful.
	 */
	editRecord:
		( id: number, edits: Partial< EditablePopup > ): ThunkAction =>
		async ( { select, dispatch } ) => {
			const action = 'editRecord';

			try {
				// dispatch.startResolution( action, operation );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Resolving,
				} );

				let canonicalPopup: EditablePopup | undefined;

				if ( select.hasEditedEntity( id ) ) {
					canonicalPopup = select.getEditedEntity( id );
				} else {
					canonicalPopup = await fetchFromApi< Popup< 'edit' > >(
						`ctas/${ id }?context=edit`
					).then( ( result ) =>
						// Convert to editable entity if found.
						result
							? editableEntity< Popup< 'edit' > >( result )
							: undefined
					);
					if ( ! canonicalPopup ) {
						// dispatch.failResolution(
						// 	action,
						// 	__( 'Call to action not found', 'popup-maker' ),
						// 	operation
						// );

						dispatch( {
							type: CHANGE_ACTION_STATUS,
							actionName: action,
							status: DispatchStatus.Error,
							message: __(
								'Call to action not found',
								'popup-maker'
							),
						} );

						return;
					}

					await dispatch( {
						type: START_EDITING_RECORD,
						payload: { id, editableEntity: canonicalPopup },
					} );
				}

				await dispatch( {
					type: EDIT_RECORD,
					payload: {
						id,
						edits,
						editableEntity: canonicalPopup,
					},
				} );

				// dispatch.finishResolution( action, operation );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Success,
				} );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// eslint-disable-next-line no-console
				console.error( 'Edit failed:', error );

				await dispatch.createErrorNotice( errorMessage );

				// dispatch.failResolution(
				// 	action,
				// 	errorMessage,
				// 	operation,
				// 	error as Record< string, any >
				// );

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Error,
					message: __( 'Call to action not found', 'popup-maker' ),
				} );
			}
		},

	/**
	 * Save an edited entity record.
	 *
	 * @param {number}   id       The entity ID.
	 * @param {Function} validate An optional validation function.
	 * @return {Promise<boolean>} Whether the save was successful.
	 */
	saveEditedRecord:
		( id: number, validate: boolean = true ): ThunkAction< boolean > =>
		async ( { select, dispatch } ) => {
			const action = 'saveRecord';

			try {
				// dispatch.startResolution( action, operation );
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Resolving,
				} );

				if ( ! select.hasEdits( id ) ) {
					// dispatch.failResolution(
					// 	action,
					// 	__( 'No edits to save', 'popup-maker' ),
					// 	operation
					// );

					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Error,
						message: __( 'No edits to save', 'popup-maker' ),
					} );

					return false;
				}

				const historyIndex = select.getCurrentEditHistoryIndex( id );
				const editedPopup = select.getEditedPopup( id );

				if ( ! editedPopup ) {
					// dispatch.failResolution(
					// 	action,
					// 	__( 'No edits to save', 'popup-maker' ),
					// 	operation
					// );

					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Error,
						message: __( 'No edits to save', 'popup-maker' ),
					} );

					return false;
				}

				if ( editedPopup && validate ) {
					const validation = validatePopup( editedPopup );

					if ( true !== validation ) {
						// dispatch.failResolution(
						// 	action,
						// 	validation.message,
						// 	operation,
						// 	validation
						// );

						dispatch( {
							type: CHANGE_ACTION_STATUS,
							actionName: action,
							status: DispatchStatus.Error,
							message: validation.message,
						} );

						// TODO REVIEW: Do we need to handle this with a notice, or can we just get the message from resolution status?
						await dispatch.createErrorNotice( validation.message, {
							id: 'call-to-action-validation-error',
							closeDelay: 5000,
						} );

						return false;
					}
				}

				const result = await dispatch.updatePopup( editedPopup, false );

				if ( result ) {
					// dispatch.finishResolution( action, operation );
					dispatch( {
						type: CHANGE_ACTION_STATUS,
						actionName: action,
						status: DispatchStatus.Success,
					} );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: call to action title.
							__(
								'Call to action "%s" saved successfully.',
								'popup-maker'
							),
							editedPopup.title
						),
						{
							id: 'call-to-action-saved',
							closeDelay: 5000,
						}
					);

					dispatch( {
						type: RECIEVE_RECORD,
						payload: {
							record: result,
						},
					} );

					dispatch( {
						type: SAVE_EDITED_RECORD,
						payload: {
							id,
							historyIndex,
							editedEntity: result,
						},
					} );

					return true;
				}

				return false;
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// eslint-disable-next-line no-console
				console.error( 'Save failed:', error );

				await dispatch.createErrorNotice( errorMessage );

				// dispatch.failResolution(
				// 	action,
				// 	error instanceof Error ? error.message : 'Failed to save',
				// 	operation
				// );

				dispatch( {
					type: CHANGE_ACTION_STATUS,
					actionName: action,
					status: DispatchStatus.Error,
					message: errorMessage,
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
	 * @param {Partial<EditablePopup>} values The editor values.
	 * @return {Promise<void>}
	 */
	updateEditorValues:
		( values: PartialEditablePopup ): ThunkAction< void > =>
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
					const entity = await fetchFromApi< Popup< 'edit' > >(
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
						},
					} );
				}

				dispatch( {
					type: EDITOR_CHANGE_ID,
					payload: {
						editorId,
					},
				} );
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
	 * @param {Notice[ 'status' ]}  status  Notice status.
	 * @param {Notice[ 'content' ]} content Notice content.
	 * @param {Notice}              options Notice options.
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
	 * @param {string}                             content Notice content.
	 * @param {Omit<Notice, 'status' | 'content'>} options Notice options.
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
	 * @param {string}                             content Notice content.
	 * @param {Omit<Notice, 'status' | 'content'>} options Notice options.
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
	 * @param {string} id Notice ID.
	 */
	removeNotice:
		(
			/**
			 * Notice ID.
			 */
			id: string
		): ThunkAction =>
		async ( { registry } ) => {
			registry
				.dispatch( noticesStore )
				.removeNotice( id, NOTICE_CONTEXT );
		},

	/**
	 * Remove all notices for a given context.
	 *
	 * @param {string[]} ids Notice IDs.
	 */
	removeAllNotices:
		(
			/**
			 * Notice IDs.
			 */
			ids?: string[]
		): ThunkAction =>
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
	 * @param {PopupsStore[ 'ActionNames' ]} actionName Action name to change status of.
	 * @param {Statuses}                     status     New status.
	 * @param {string|undefined}             message    Optional error message.
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
				actionName,
				status,
				message,
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
	 * @param {number | string} id        The entity ID.
	 * @param {string}          operation The operation.
	 */
	invalidateResolution:
		( id: number | string, operation: string = 'fetch' ): ThunkAction =>
		( { dispatch } ) => {
			dispatch( {
				type: INVALIDATE_RESOLUTION,
				payload: {
					id,
					operation,
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
