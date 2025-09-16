import { cloneDeep, mergeWith } from 'lodash';
import { compare as jsonpatchCompare } from 'fast-json-patch';

import { __, sprintf } from '@popup-maker/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { decodeEntities } from '@wordpress/html-entities';

import { ACTION_TYPES, NOTICE_CONTEXT } from './constants';

import { DispatchStatus } from '../constants';
import { fetchFromApi, getErrorMessage } from '../utils';
import { validatePopup } from './validation';
import { editableEntity } from './utils';

import type { EditorId, Notice } from '../types';
import type {
	Popup,
	ThunkAction,
	EditablePopup,
	PartialEditablePopup,
} from './types';
import { EditRecordAction } from './reducer';

// import type { EditRecordAction } from './reducer';

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

/*****************************************************
 * SECTION: Entity actions
 *****************************************************/

/**
 * Create a new entity record. Values sent to the server immediately.
 *
 * @param {Editable} popup       The entity to create.
 * @param {boolean}  validate    An optional validation function.
 * @param {boolean}  withNotices Whether to show notices.
 * @return {Promise<Popup< 'edit' > | false>} The created entity or false if validation fails.
 */
export const createPopup =
	(
		popup: Partial< EditablePopup >,
		validate: boolean = true,
		withNotices: boolean = true
	): ThunkAction< Popup< 'edit' > | false > =>
	async ( { dispatch, registry } ) => {
		const action = 'createPopup';

		try {
			dispatch( {
				type: CHANGE_ACTION_STATUS,
				payload: {
					actionName: action,
					status: DispatchStatus.Resolving,
				},
			} );

			const { id, ...newPopup } = popup;

			if ( validate ) {
				const validation = validatePopup( newPopup );

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
						await dispatch.createErrorNotice( validation.message, {
							id: 'popup-validation-error',
						} );
					}

					return false;
				}
			}

			const result = await fetchFromApi< Popup< 'edit' > >(
				`popups?context=edit`,
				{
					method: 'POST',
					data: newPopup,
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
								// translators: %s: popup title.
								__(
									'Popup "%s" saved successfully.',
									'popup-maker'
								),
								decodeEntities( result?.title.rendered )
							),
							{
								id: 'popup-saved',
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
						'An error occurred, popup was not saved.',
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
	};

/**
 * Update an existing entity record. Values sent to the server immediately.
 *
 * @param {PartialEditablePopup} popup       The entity to update.
 * @param {boolean}              validate    An optional validation function.
 * @param {boolean}              withNotices Whether to show notices.
 * @return {Promise<T | boolean>} The updated entity or false if validation fails.
 */
export const updatePopup =
	(
		popup: PartialEditablePopup,
		validate: boolean = true,
		withNotices: boolean = true
	): ThunkAction< Popup< 'edit' > | false > =>
	async ( { select, dispatch, registry } ) => {
		const action = 'updatePopup';

		try {
			dispatch( {
				type: CHANGE_ACTION_STATUS,
				payload: {
					actionName: action,
					status: DispatchStatus.Resolving,
				},
			} );

			if ( validate ) {
				const validation = validatePopup( popup );

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
						await dispatch.createErrorNotice( validation.message, {
							id: 'popup-validation-error',
						} );
					}

					return false;
				}
			}

			const canonicalPopup = await select.getPopup( popup.id );

			if ( ! canonicalPopup ) {
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Error,
						message: __( 'Popup not found', 'popup-maker' ),
					},
				} );

				return false;
			}

			const result = await fetchFromApi< Popup< 'edit' > >(
				`popups/${ canonicalPopup.id }`,
				{
					method: 'POST',
					data: popup,
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
				} );

				if ( withNotices ) {
					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: popup title.
							__(
								'Popup "%s" updated successfully.',
								'popup-maker'
							),
							decodeEntities( result?.title.rendered )
						),
						{
							id: 'popup-saved',
						}
					);
				}

				dispatch( {
					type: RECEIVE_RECORD,
					payload: {
						record: result,
					},
				} );

				return result;
			}

			dispatch( {
				type: CHANGE_ACTION_STATUS,
				payload: {
					actionName: action,
					status: DispatchStatus.Error,
					message: __(
						'An error occurred, popup was not saved.',
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
	};

/**
 * Delete an existing entity record.
 *
 * @param {number}  id          The entity ID.
 * @param {boolean} forceDelete Whether to force the deletion.
 * @param {boolean} withNotices Whether to show notices.
 * @return {Promise<boolean>} Whether the deletion was successful.
 */
export const deletePopup =
	(
		id: number,
		forceDelete: boolean = false,
		withNotices: boolean = true
	): ThunkAction< boolean > =>
	async ( { dispatch, registry } ) => {
		const action = 'deletePopup';

		try {
			dispatch( {
				type: CHANGE_ACTION_STATUS,
				payload: {
					actionName: action,
					status: DispatchStatus.Resolving,
				},
			} );

			// Get the canonical directly from server to verify it exists.
			const canonicalPopup = await fetchFromApi< Popup< 'edit' > >(
				`popups/${ id }?context=edit`
			);

			if ( ! canonicalPopup ) {
				dispatch( {
					type: CHANGE_ACTION_STATUS,
					payload: {
						actionName: action,
						status: DispatchStatus.Error,
						message: __( 'Popup not found', 'popup-maker' ),
					},
				} );

				return false;
			}

			const force = forceDelete ? '?force=true' : '';

			const result = await fetchFromApi< boolean >(
				`popups/${ id }${ force }`,
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
								// translators: %s: popup title.
								__(
									'Popup "%s" deleted successfully.',
									'popup-maker'
								),
								decodeEntities( canonicalPopup?.title.rendered )
							),
							{
								id: 'popup-deleted',
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
									...canonicalPopup,
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
					message: __( 'Popup not found', 'popup-maker' ),
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
	};

/*****************************************************
 * SECTION: Editor actions
 * REVIEW: ALL OF THESE ACTIONS NEED TO BE REFACTORED TO USE THE NEW THUNK ACTIONS.
 *****************************************************/

/**
 * Edit an existing entity record. Values are not sent to the server until save.
 *
 * @param {number}                 id    The entity ID.
 * @param {Partial<EditablePopup>} edits The edits to apply.
 * @return {Promise<boolean>} Whether the edit was successful.
 */
export const editRecord =
	( id: number, edits: Partial< EditablePopup > ): ThunkAction =>
	async ( { select, dispatch, registry } ) => {
		try {
			let canonicalPopup: EditablePopup | undefined;
			const hasEditedEntity = select.hasEditedEntity( id );

			if ( hasEditedEntity ) {
				canonicalPopup = select.getEditedPopup( id ) as EditablePopup;
			} else {
				canonicalPopup = await fetchFromApi<
					Popup< 'edit' > & { _links: any }
				>( `popups/${ id }?context=edit` ).then( ( result ) =>
					// Convert to editable entity if found.
					result
						? editableEntity< Popup< 'edit' > >( result )
						: undefined
				);
				if ( ! canonicalPopup ) {
					return;
				}
			}

			registry.batch( async () => {
				if ( ! hasEditedEntity ) {
					await dispatch( {
						type: START_EDITING_RECORD,
						payload: {
							id,
							editableEntity: canonicalPopup,
						},
					} );
				}

				// Create a new object with the edits deeply merged into the canonical entity
				// First clone the canonical entity, then deep merge the edits
				const editedEntity = mergeWith(
					{},
					cloneDeep( canonicalPopup ),
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
					canonicalPopup ?? {},
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
	};

/**
 * Save an edited entity record.
 *
 * @param {number}  id          The entity ID.
 * @param {boolean} validate    An optional validation function.
 * @param {boolean} withNotices Whether to show notices.
 * @return {Promise<boolean>} Whether the save was successful.
 */
export const saveEditedRecord =
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
			const editedPopup = select.getEditedPopup( id );

			if ( ! editedPopup ) {
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

			if ( editedPopup && validate ) {
				const validation = validatePopup( editedPopup );

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
									id: 'popup-validation-error',
								}
							);
						}
					} );

					return false;
				}
			}

			const result = await dispatch.updatePopup(
				editedPopup,
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
								// translators: %s: popup title.
								__(
									'Popup "%s" saved successfully.',
									'popup-maker'
								),
								decodeEntities( result?.title.rendered )
							),
							{
								id: 'popup-saved',
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
	};

/**
 * Undo the last action.
 *
 * @param {number} id    The entity ID.
 * @param {number} steps The number of steps to undo.
 * @return {Promise<void>}
 */
export const undo =
	( id: number, steps: number = 1 ): ThunkAction =>
	async ( { select, dispatch } ) => {
		const popupId = id > 0 ? id : select.getEditorId();

		if ( typeof popupId === 'undefined' ) {
			return;
		}

		await dispatch( {
			type: UNDO_EDIT_RECORD,
			payload: {
				id: popupId,
				steps,
			},
		} );
	};

/**
 * Redo the last action.
 *
 * @param {number} id    The entity ID.
 * @param {number} steps The number of steps to redo.
 * @return {Promise<void>}
 */
export const redo =
	( id: number, steps: number = 1 ): ThunkAction =>
	async ( { select, dispatch } ) => {
		const popupId = id > 0 ? id : select.getEditorId();

		if ( typeof popupId === 'undefined' ) {
			return;
		}

		await dispatch( {
			type: REDO_EDIT_RECORD,
			payload: {
				id: popupId,
				steps,
			},
		} );
	};

/**
 * Reset the edits for an entity record.
 *
 * @param {number} id The entity ID.
 * @return {Promise<void>}
 */
export const resetRecordEdits =
	( id: number ): ThunkAction =>
	async ( { select, dispatch } ) => {
		const popupId = id > 0 ? id : select.getEditorId();

		if ( typeof popupId === 'undefined' ) {
			return;
		}

		dispatch( {
			type: RESET_EDIT_RECORD,
			payload: {
				id: popupId,
			},
		} );
	};

/**
 * Update the editor values.
 *
 * @param {Partial<EditablePopup>} values The editor values.
 * @return {Promise<void>}
 */
export const updateEditorValues =
	( values: PartialEditablePopup ): ThunkAction< void > =>
	async ( { dispatch, select } ) => {
		const editorId = select.getEditorId();

		if ( typeof editorId === 'undefined' ) {
			return;
		}

		dispatch.editRecord( editorId, values );
	};

/**
 * Save the editor values.
 *
 * @return {Promise<boolean>} Whether the save was successful.
 */
export const saveEditorValues =
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
	};

/**
 * Reset the editor values.
 *
 * @return {Promise<void>}
 */
export const resetEditorValues =
	(): ThunkAction< void > =>
	async ( { dispatch, select } ) => {
		const editorId = select.getEditorId();

		if ( typeof editorId === 'undefined' ) {
			return;
		}

		dispatch.resetRecordEdits( editorId );
	};

/**
 * Change the editor ID.
 *
 * @param {EditorId} editorId The editor ID.
 * @return {Promise<void>}
 */
export const changeEditorId =
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
					`popups/${ editorId }?context=edit`
				);

				if ( ! entity ) {
					dispatch.createErrorNotice(
						__( 'Popup not found', 'popup-maker' )
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
	};

/*****************************************************
 * SECTION: Notice actions
 *****************************************************/

/**
 * Create a notice.
 *
 * @param {Notice[ 'status' ]}  status  The notice status.
 * @param {Notice[ 'content' ]} content The notice content.
 * @param {Notice}              options The notice options.
 * @return {Promise<void>}
 */
export const createNotice =
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
	};

/**
 * Create an error notice.
 *
 * @param {string}                             content The notice content.
 * @param {Omit<Notice, 'status' | 'content'>} options The notice options.
 * @return {Promise<void>}
 */
export const createErrorNotice =
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
	};

/**
 * Create a success notice.
 *
 * @param {string}                             content The notice content.
 * @param {Omit<Notice, 'status' | 'content'>} options The notice options.
 * @return {Promise<void>}
 */
export const createSuccessNotice =
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
		registry.dispatch( noticesStore ).createNotice( 'success', content, {
			...options,
			context: NOTICE_CONTEXT,
		} );
	};

/**
 * Remove a notice for a given context.
 *
 * @param {string} id The notice ID.
 * @return {Promise<void>}
 */
export const removeNotice =
	( id: string ): ThunkAction =>
	async ( { registry } ) => {
		registry.dispatch( noticesStore ).removeNotice( id, NOTICE_CONTEXT );
	};

/**
 * Remove all notices for a given context.
 *
 * @param {string[]} ids The notice IDs.
 * @return {Promise<void>}
 */
export const removeAllNotices =
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
	};

/*****************************************************
 * SECTION: Resolution actions
 *****************************************************/

/**
 * Change status of a dispatch action request.
 *
 * @param {PopupsStore[ 'ActionNames' ]} actionName Action name to change status of.
 * @param {Statuses}                     status     New status.
 * @param {string|undefined}             message    Optional error message.
 * @return {Object} Action object.
 */
export const changeActionStatus =
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
	};

/**
 * Invalidate resolution for an entity.
 *
 * @param {number | string} id The entity ID.
 * @return {Promise<void>}
 */
export const invalidateResolution =
	( id: number | string ): ThunkAction =>
	( { dispatch } ) => {
		dispatch( {
			type: INVALIDATE_RESOLUTION,
			payload: {
				id,
			},
		} );
	};

export default {
	/*****************************************************
	 * SECTION: Entity actions
	 *****************************************************/
	createPopup,
	updatePopup,
	deletePopup,

	/*****************************************************
	 * SECTION: Editor actions
	 *****************************************************/
	editRecord,
	saveEditedRecord,
	undo,
	redo,
	resetRecordEdits,
	updateEditorValues,
	saveEditorValues,
	resetEditorValues,
	changeEditorId,

	/*****************************************************
	 * SECTION: Notice actions
	 *****************************************************/
	createNotice,
	createErrorNotice,
	createSuccessNotice,
	removeNotice,
	removeAllNotices,

	/*****************************************************
	 * SECTION: Resolution actions
	 *****************************************************/
	changeActionStatus,
	invalidateResolution,
};
