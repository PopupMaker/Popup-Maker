import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

import { ACTION_TYPES, NOTICE_CONTEXT } from './constants';

import type { EditorId, Notice } from '../types';
import type {
	Popup,
	ThunkAction,
	EditablePopup,
	PartialEditablePopup,
} from './types';
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
	START_RESOLUTION,
	FINISH_RESOLUTION,
	FAIL_RESOLUTION,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

/*****************************************************
 * SECTION: Entity actions
 *****************************************************/
const entityActions = {
	/**
	 * Create a new entity record. Values sent to the server immediately.
	 *
	 * @param {Editable} entity The entity to create.
	 * @param {Function} validate An optional validation function.
	 * @returns {Promise<Editable | boolean>} The created entity or false if validation fails.
	 */
	createPopup:
		(
			popup: Partial< EditablePopup >,
			validate: boolean = true
		): ThunkAction< EditablePopup | false > =>
		async ( { dispatch } ) => {
			const action = 'createPopup';
			const operation = 'POST';

			try {
				dispatch.startResolution( action, operation );

				const { id, ...newPopup } = popup;

				if ( validate ) {
					const validation = validatePopup( newPopup );

					if ( true !== validation ) {
						dispatch.failResolution(
							action,
							validation.message,
							operation,
							validation
						);
						// TODO REVIEW: Do we need to handle this with a notice, or can we just get the message from resolution status?
						await dispatch.createErrorNotice( validation.message, {
							id: 'popup-validation-error',
							closeDelay: 5000,
						} );

						return false;
					}
				}

				const result = await fetchFromApi< EditablePopup >( `/popups`, {
					method: 'POST',
					data: newPopup,
				} );

				if ( result ) {
					dispatch.finishResolution( action, operation );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: popup title.
							__(
								'Popup "%s" saved successfully.',
								'popup-maker'
							),
							result?.title
						),
						{
							id: 'popup-saved',
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

				dispatch.failResolution(
					action,
					__(
						'An error occurred, popup was not saved.',
						'popup-maker'
					),
					operation
				);
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// Mark resolution failed.
				dispatch.failResolution( 'createPopup', errorMessage, 'POST' );

				// Generate a generic error notice.
				dispatch.createErrorNotice( errorMessage );

				throw error;
			}

			return false;
		},

	/**
	 * Update an existing entity record. Values sent to the server immediately.
	 *
	 * @param {Partial<EditablePopup>&{id:EditablePopup[ 'id' ]}} entity The entity to update.
	 * @param {Function} validate An optional validation function.
	 * @returns {Promise<T | boolean>} The updated entity or false if validation fails.
	 */
	updatePopup:
		(
			popup: Partial< EditablePopup > & { id: number },
			validate: boolean = true
		): ThunkAction< Popup< 'edit' > | false > =>
		async ( { select, dispatch } ) => {
			const action = 'updatePopup';
			const operation = 'POST';

			try {
				dispatch.startResolution( action, operation );

				if ( validate ) {
					const validation = validatePopup( popup );

					if ( true !== validation ) {
						dispatch.failResolution(
							action,
							validation.message,
							operation,
							validation
						);
						// TODO REVIEW: Do we need to handle this with a notice, or can we just get the message from resolution status?
						await dispatch.createErrorNotice( validation.message, {
							id: 'popup-validation-error',
							closeDelay: 5000,
						} );

						return false;
					}
				}

				const canonicalPopup = await select.getPopup( popup.id );

				if ( ! canonicalPopup ) {
					dispatch.failResolution(
						action,
						__( 'Popup not found', 'popup-maker' ),
						operation
					);
					return false;
				}

				// TODO REVIEW: Test the return types of each of these calls so we can be sure.
				const result = await fetchFromApi< Popup< 'edit' > >(
					`/popups/${ canonicalPopup.id }`,
					{
						method: 'POST',
						data: popup,
					}
				);

				if ( result ) {
					dispatch.finishResolution( action, operation );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: popup title.
							__(
								'Popup "%s" updated successfully.',
								'popup-maker'
							),
							result?.title
						),
						{
							id: 'popup-saved',
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

				dispatch.failResolution(
					action,
					__(
						'An error occurred, popup was not saved.',
						'popup-maker'
					),
					operation
				);
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				// Mark resolution failed.
				dispatch.failResolution( action, errorMessage, operation );

				// Generate a generic error notice.
				dispatch.createErrorNotice( errorMessage );

				throw error;
			}

			return false;
		},

	/**
	 * Delete an existing entity record.
	 *
	 * @param {number} id The entity ID.
	 * @param {boolean} forceDelete Whether to force the deletion.
	 * @returns {Promise<boolean>} Whether the deletion was successful.
	 */
	deleteRecord:
		( id: number, forceDelete: boolean = false ): ThunkAction< boolean > =>
		async ( { dispatch } ) => {
			const action = 'deletePopup';
			const operation = 'DELETE';

			try {
				await dispatch.startResolution( action, operation );

				// Get the canonical directly from server to verify it exists.
				// TODO REVIEW: Test this.
				const canonicalPopup = await fetchFromApi< Popup< 'edit' > >(
					`/popups/${ id }?context=edit`
				);

				if ( ! canonicalPopup ) {
					dispatch.failResolution(
						action,
						__( 'Popup not found', 'popup-maker' ),
						operation
					);

					return false;
				}

				const force = forceDelete ? '?force=true' : '';

				const result = await fetchFromApi< boolean >(
					`/popups/${ id }${ force }`,
					{
						method: 'DELETE',
					}
				);

				if ( result ) {
					dispatch.finishResolution( action, operation );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: popup title.
							__(
								'Popup "%s" deleted successfully.',
								'popup-maker'
							),
							canonicalPopup?.title
						),
						{
							id: 'popup-deleted',
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
				await dispatch.failResolution( action, operation );
				await dispatch.createErrorNotice(
					error instanceof Error
						? error.message
						: __( 'Failed to delete entity' )
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
	 * @param {number} id The entity ID.
	 * @param {Partial<EditablePopup>} edit The edits to apply.
	 * @returns {Promise<boolean>} Whether the edit was successful.
	 */
	editRecord:
		( id: number, edits: Partial< EditablePopup > ): ThunkAction =>
		async ( { select, dispatch } ) => {
			const action = 'editRecord';
			const operation = 'POST';

			try {
				dispatch.startResolution( action, operation );

				let canonicalPopup: EditablePopup | undefined;

				if ( select.hasEditedEntity( id ) ) {
					canonicalPopup = select.getEditedEntity( id );
				} else {
					canonicalPopup = await fetchFromApi< Popup< 'edit' > >(
						`/popups/${ id }?context=edit`
					).then( ( result ) =>
						// Convert to editable entity if found.
						result
							? editableEntity< Popup< 'edit' > >( result )
							: undefined
					);
					if ( ! canonicalPopup ) {
						dispatch.failResolution(
							action,
							__( 'Popup not found', 'popup-maker' ),
							operation
						);
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

				dispatch.finishResolution( action, operation );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				console.error( 'Edit failed:', error );

				await dispatch.createErrorNotice( errorMessage );

				dispatch.failResolution(
					action,
					errorMessage,
					operation,
					error as Record< string, any >
				);
			}
		},

	/**
	 * Save an edited entity record.
	 *
	 * @param {number} id The entity ID.
	 * @param {Function} validate An optional validation function.
	 * @returns {Promise<boolean>} Whether the save was successful.
	 */
	saveEditedRecord:
		( id: number, validate: boolean = true ): ThunkAction< boolean > =>
		async ( { select, dispatch } ) => {
			const action = 'saveRecord';
			const operation = 'POST';

			try {
				dispatch.startResolution( action, operation );

				if ( ! select.hasEdits( id ) ) {
					dispatch.failResolution(
						action,
						__( 'No edits to save', 'popup-maker' ),
						operation
					);

					return false;
				}

				const historyIndex = select.getCurrentEditHistoryIndex( id );
				const editedPopup = select.getEditedPopup( id );

				if ( ! editedPopup ) {
					dispatch.failResolution(
						action,
						__( 'No edits to save', 'popup-maker' ),
						operation
					);

					return false;
				}

				if ( editedPopup && validate ) {
					const validation = validatePopup( editedPopup );

					if ( true !== validation ) {
						dispatch.failResolution(
							action,
							validation.message,
							operation,
							validation
						);
						// TODO REVIEW: Do we need to handle this with a notice, or can we just get the message from resolution status?
						await dispatch.createErrorNotice( validation.message, {
							id: 'popup-validation-error',
							closeDelay: 5000,
						} );

						return false;
					}
				}

				const result = await dispatch.updatePopup( editedPopup, false );

				if ( result ) {
					dispatch.finishResolution( action, operation );

					dispatch.createSuccessNotice(
						sprintf(
							// translators: %s: popup title.
							__(
								'Popup "%s" saved successfully.',
								'popup-maker'
							),
							editedPopup.title
						),
						{
							id: 'popup-saved',
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

				console.error( 'Save failed:', error );

				await dispatch.createErrorNotice( errorMessage );

				dispatch.failResolution(
					action,
					error instanceof Error ? error.message : 'Failed to save',
					operation
				);

				throw error;
			}
		},

	/**
	 * Undo the last action.
	 *
	 * @returns {Promise<void>}
	 */
	undo:
		( id: number, steps: number = 1 ): ThunkAction =>
		async ( { select, dispatch } ) => {
			let popupId = id > 0 ? id : select.getEditorId();

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
		},

	/**
	 * Redo the last action.
	 *
	 * @returns {Promise<void>}
	 */
	redo:
		( id: number, steps: number = 1 ): ThunkAction =>
		async ( { select, dispatch } ) => {
			let popupId = id > 0 ? id : select.getEditorId();

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
		},

	/**
	 * Reset the edits for an entity record.
	 *
	 * @param {number} id The entity ID.
	 * @returns {Promise<void>}
	 */
	resetRecordEdits:
		( id: number ): ThunkAction =>
		async ( { select, dispatch } ) => {
			let popupId = id > 0 ? id : select.getEditorId();

			if ( typeof popupId === 'undefined' ) {
				return;
			}

			dispatch( {
				type: RESET_EDIT_RECORD,
				payload: {
					id: popupId,
				},
			} );
		},

	updateEditorValues:
		( values: PartialEditablePopup ): ThunkAction< void > =>
		async ( { dispatch, select } ) => {
			const editorId = select.getEditorId();

			if ( typeof editorId === 'undefined' ) {
				return;
			}

			dispatch.editRecord( editorId, values );
		},

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

	resetEditorValues:
		(): ThunkAction< void > =>
		async ( { dispatch, select } ) => {
			const editorId = select.getEditorId();

			if ( typeof editorId === 'undefined' ) {
				return;
			}

			dispatch.resetRecordEdits( editorId );
		},

	// Refactored changeEditorId using thunk and our selectors
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
						`/popups/${ editorId }?context=edit`
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
				const ids = notices.map( ( notice ) => notice.id );
				registry
					.dispatch( noticesStore )
					.removeNotices( ids, NOTICE_CONTEXT );
			}
		},
};

/*****************************************************
 * SECTION: Resolution actions
 *****************************************************/
const resolutionActions = {
	/**
	 * Start resolution for an entity.
	 */
	startResolution:
		( id: number | string, operation: string = 'fetch' ) =>
		( { dispatch } ) => {
			dispatch( {
				type: START_RESOLUTION,
				payload: {
					id,
					operation,
				},
			} );
		},

	/**
	 * Finish resolution for an entity.
	 */
	finishResolution:
		( id: number | string, operation: string = 'fetch' ) =>
		( { dispatch } ) => {
			dispatch( {
				type: FINISH_RESOLUTION,
				payload: {
					id,
					operation,
				},
			} );
		},

	/**
	 * Fail resolution for an entity.
	 */
	failResolution:
		(
			id: number | string,
			error: string,
			operation: string = 'fetch',
			extra?: Record< string, any >
		) =>
		( { dispatch } ) => {
			dispatch( {
				type: FAIL_RESOLUTION,
				payload: {
					id,
					error,
					operation,
					extra,
				},
			} );
		},

	/**
	 * Invalidate resolution for an entity.
	 */
	invalidateResolution:
		( id: number | string, operation: string = 'fetch' ) =>
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
