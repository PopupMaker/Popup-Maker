import { ACTION_TYPES, initialState } from './constants';

import type { DispatchStatuses, ResolutionState } from '../constants';
import type { EditorId, Notice, GetRecordsHttpQuery } from '../types';
import type { CallToAction, EditableCta } from './types';
import type { Operation } from 'fast-json-patch';

const {
	RECEIVE_RECORD,
	RECEIVE_RECORDS,
	RECEIVE_QUERY_RECORDS,
	PURGE_RECORD,
	PURGE_RECORDS,
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
 * The shape of the state for the call to actions store.
 */
export type State = {
	/**
	 * The call to actions by ID.
	 */
	byId: Record< number, CallToAction< 'edit' > >;

	/**
	 * The IDs of all the call to actions.
	 */
	allIds: number[];

	/**
	 * The queries for the call to actions.
	 */
	queries?: Record< string, number[] >;

	/**
	 * The ID of the editor.
	 */
	editorId: number | undefined;

	/**
	 * The edited entities.
	 */
	editedEntities: Record< number, EditableCta >;

	/**
	 * The edit history for each call to action.
	 *
	 * Each edit is an object with the same shape as the editable entity, but without the `id` property.
	 */
	editHistory: Record< number, Operation[][] >;

	/**
	 * The index of the current edit for each call to action.
	 */
	editHistoryIndex: Record< number, number >;

	/**
	 * The resolution state for each operation.
	 */
	resolutionState: Record< string | number, ResolutionState >;

	/**
	 * The notices for the call to actions.
	 */
	notices: Record< string, Notice >;
};

type BaseAction = {
	type: keyof typeof ACTION_TYPES;
	payload?: Record< string, any >;
};

export type RecieveRecordAction = BaseAction & {
	type: typeof RECEIVE_RECORD;
	payload: {
		record: CallToAction< 'edit' >;
	};
};

export type RecieveRecordsAction = BaseAction & {
	type: typeof RECEIVE_RECORDS;
	payload: {
		records: CallToAction< 'edit' >[];
	};
};

export type RecieveQueryRecordsAction = BaseAction & {
	type: typeof RECEIVE_QUERY_RECORDS;
	payload: {
		query: GetRecordsHttpQuery;
		records: CallToAction< 'edit' >[];
	};
};

export type PurgeRecordAction = BaseAction & {
	type: typeof PURGE_RECORD;
	payload: {
		id: CallToAction[ 'id' ];
	};
};

export type PurgeRecordsAction = BaseAction & {
	type: typeof PURGE_RECORDS;
	payload: {
		ids: CallToAction[ 'id' ][];
	};
};

export type ChangeEditorAction = BaseAction & {
	type: typeof EDITOR_CHANGE_ID;
	payload: {
		editorId: EditorId;
	};
};

export type StartEditingRecordAction = BaseAction & {
	type: typeof START_EDITING_RECORD;
	payload: {
		id: number;
		editableEntity: EditableCta;
		setEditorId: boolean;
	};
};

export type EditRecordAction = BaseAction & {
	type: typeof EDIT_RECORD;
	payload: {
		id: number;
		edits: Operation[];
	};
};

export type UndoEditRecordAction = BaseAction & {
	type: typeof UNDO_EDIT_RECORD;
	payload: {
		id: number;
		steps: number;
	};
};

export type RedoEditRecordAction = BaseAction & {
	type: typeof REDO_EDIT_RECORD;
	payload: {
		id: number;
		steps: number;
	};
};

export type ResetEditRecordAction = BaseAction & {
	type: typeof RESET_EDIT_RECORD;
	payload: {
		id: number;
	};
};

export type SaveEditedRecordAction = BaseAction & {
	type: typeof SAVE_EDITED_RECORD;
	payload: {
		id: number;
		historyIndex: number;
		editedEntity: EditableCta;
	};
};

export type ChangeActionStatusAction = BaseAction & {
	type: typeof CHANGE_ACTION_STATUS;
	payload: {
		actionName: string;
		status: DispatchStatuses;
		message?: string;
	};
};

// export type StartResolutionAction = BaseAction & {
// 	type: typeof START_RESOLUTION;
// 	payload: {
// 		id: number | string;
// 		operation: string;
// 	};
// };

// export type FinishResolutionAction = BaseAction & {
// 	type: typeof FINISH_RESOLUTION;
// 	payload: {
// 		id: number | string;
// 		operation: string;
// 	};
// };

// export type FailResolutionAction = BaseAction & {
// 	type: typeof FAIL_RESOLUTION;
// 	payload: {
// 		id: number | string;
// 		operation: string;
// 		error: string;
// 		extra?: Record< string, any >;
// 	};
// };

export type InvalidateResolutionAction = BaseAction & {
	type: typeof INVALIDATE_RESOLUTION;
	payload: {
		id: number | string;
		operation: string;
	};
};

export type ReducerAction =
	| RecieveRecordAction
	| RecieveRecordsAction
	| RecieveQueryRecordsAction
	| PurgeRecordAction
	| PurgeRecordsAction
	| ChangeEditorAction
	| StartEditingRecordAction
	| EditRecordAction
	| UndoEditRecordAction
	| RedoEditRecordAction
	| ResetEditRecordAction
	| SaveEditedRecordAction
	| ChangeActionStatusAction
	| InvalidateResolutionAction;

export const reducer = (
	state = initialState,
	action: ReducerAction
): State => {
	switch ( action.type ) {
		case RECEIVE_RECORD: {
			const { record } = action.payload;

			return {
				...state,
				byId: {
					...state.byId,
					[ record.id ]: record,
				},
				allIds: state.allIds.includes( record.id )
					? state.allIds
					: [ ...state.allIds, record.id ],
			};
		}

		case RECEIVE_RECORDS:
		case RECEIVE_QUERY_RECORDS: {
			const { records, query = false } = action.payload;

			// Add the new records to the byId object.
			const byId = records.reduce<
				Record< number, CallToAction< 'edit' > >
			>(
				( acc, record ) => ( {
					...acc,
					[ record.id ]: record,
				} ),
				state.byId
			);

			// Add the new records to the allIds array.
			const allIds = Array.from(
				new Set( [ ...state.allIds, ...records.map( ( r ) => r.id ) ] )
			);

			return {
				...state,
				allIds,
				byId,
				queries: query
					? {
							...state.queries,
							[ JSON.stringify( query ) ]: records.map(
								( r ) => r.id
							),
					  }
					: state.queries,
			};
		}

		case PURGE_RECORDS:
		case PURGE_RECORD: {
			const { ids = [], id = null } = action.payload;

			if ( id && id > 0 ) {
				ids.push( id );
			}

			if ( ids.length === 0 ) {
				return state;
			}

			// Remove the entity from the allIds array.
			const allIds = state.allIds.filter(
				( _id ) => ! ids.includes( _id )
			);

			// Remove the entity from the byId object.
			const byId = Object.fromEntries(
				Object.entries( state.byId ).filter(
					( [ _id ] ) => ! ids.includes( _id )
				)
			);

			// Remove the entity from the editedEntities object.
			const editedEntities = Object.fromEntries(
				Object.entries( state.editedEntities ).filter(
					( [ _id ] ) => ! ids.includes( _id )
				)
			);

			// Remove the entity from the editHistory object.
			const editHistory = Object.fromEntries(
				Object.entries( state.editHistory ).filter(
					( [ _id ] ) => ! ids.includes( _id )
				)
			);

			// Remove the entity from the editHistoryIndex object.
			const editHistoryIndex = Object.fromEntries(
				Object.entries( state.editHistoryIndex ).filter(
					( [ _id ] ) => ! ids.includes( _id )
				)
			);

			return {
				...state,
				byId,
				allIds,
				editedEntities,
				editHistory,
				editHistoryIndex,
			};
		}

		case EDITOR_CHANGE_ID: {
			const { editorId } = action.payload;

			return {
				...state,
				editorId,
			};
		}

		case START_EDITING_RECORD: {
			const { id, editableEntity, setEditorId } = action.payload;

			const newState = {
				...state,
				editedEntities: {
					...state.editedEntities,
					[ id ]: editableEntity,
				},
			};

			if ( ! setEditorId ) {
				return newState;
			}

			return {
				...newState,
				editorId: id,
			};
		}

		case EDIT_RECORD: {
			const { id, edits } = action.payload;

			const editHistory = state.editHistory[ id ] ?? [];
			const currentIndex = state.editHistoryIndex[ id ] ?? -1;

			// If we're not at the end of history, we need to clear future edits
			const newEditHistory =
				currentIndex < editHistory.length - 1
					? editHistory.slice( 0, currentIndex + 1 )
					: editHistory;

			return {
				...state,
				editHistory: {
					...state.editHistory,
					[ id ]: [ ...newEditHistory, edits ],
				},
				editHistoryIndex: {
					...state.editHistoryIndex,
					[ id ]: newEditHistory.length, // Points to the new edit
				},
			};
		}

		case UNDO_EDIT_RECORD: {
			const { id, steps = 1 } = action.payload;

			const currentIndex = state.editHistoryIndex[ id ] ?? -1;
			const newIndex = Math.max( -1, currentIndex - steps );

			return {
				...state,
				editHistoryIndex: {
					...state.editHistoryIndex,
					[ id ]: newIndex,
				},
			};
		}

		case REDO_EDIT_RECORD: {
			const { id, steps } = action.payload;

			const currentIndex = state.editHistoryIndex[ id ] ?? -1;
			// Check if we have a history and if there are edits to redo
			const maxIndex = ( state.editHistory[ id ]?.length ?? 0 ) - 1;
			const newIndex =
				maxIndex >= 0
					? Math.min( maxIndex, currentIndex + steps )
					: currentIndex;

			return {
				...state,
				editHistoryIndex: {
					...state.editHistoryIndex,
					[ id ]: newIndex,
				},
			};
		}

		case SAVE_EDITED_RECORD: {
			const { id, historyIndex, editedEntity } = action.payload;

			// Get all edits up to current index
			const remainingEdits = state.editHistory[ id ].slice(
				historyIndex + 1
			);

			return {
				...state,
				editedEntities: {
					...state.editedEntities,
					[ id ]: editedEntity,
				},
				editHistory: {
					...state.editHistory,
					[ id ]: remainingEdits,
				},
				editHistoryIndex: {
					...state.editHistoryIndex,
					[ id ]: -1,
				},
			};
		}

		case RESET_EDIT_RECORD: {
			const { id } = action.payload;

			return {
				...state,
				// Remove all edit history for this record.
				editedEntities: Object.fromEntries(
					Object.entries( state.editedEntities ).filter(
						( [ _id ] ) => Number( _id ) !== id
					)
				),
				editHistory: Object.fromEntries(
					Object.entries( state.editHistory ).filter(
						( [ _id ] ) => Number( _id ) !== id
					)
				),
				editHistoryIndex: Object.fromEntries(
					Object.entries( state.editHistoryIndex ).filter(
						( [ _id ] ) => Number( _id ) !== id
					)
				),
			};
		}

		case CHANGE_ACTION_STATUS: {
			const { actionName, status, message } = action.payload;

			return {
				...state,
				resolutionState: {
					...state.resolutionState,
					[ actionName ]: {
						status,
						error: message,
					},
				},
			};
		}

		// case START_RESOLUTION: {
		// 	const { id, operation } = action.payload;

		// 	return {
		// 		...state,
		// 		resolutionState: {
		// 			...state.resolutionState,
		// 			[ operation ]: {
		// 				...state.resolutionState?.[ operation ],
		// 				[ id ]: {
		// 					status: DispatchStatus.Resolving,
		// 					timestamp: Date.now(),
		// 				},
		// 			},
		// 		},
		// 	};
		// }

		// case FINISH_RESOLUTION: {
		// 	const { id, operation } = action.payload;

		// 	return {
		// 		...state,
		// 		resolutionState: {
		// 			...state.resolutionState,
		// 			[ operation ]: {
		// 				...state.resolutionState?.[ operation ],
		// 				[ id ]: {
		// 					status: DispatchStatus.Success,
		// 					timestamp: Date.now(),
		// 				},
		// 			},
		// 		},
		// 	};
		// }

		// case FAIL_RESOLUTION: {
		// 	const { id, operation, error, extra } = action.payload;

		// 	return {
		// 		...state,
		// 		resolutionState: {
		// 			...state.resolutionState,
		// 			[ operation ]: {
		// 				...state.resolutionState?.[ operation ],
		// 				[ id ]: {
		// 					status: DispatchStatus.Error,
		// 					error: error,
		// 					extra: extra,
		// 					timestamp: Date.now(),
		// 				},
		// 			},
		// 		},
		// 	};
		// }

		case INVALIDATE_RESOLUTION: {
			const { id, operation } = action.payload;

			return {
				...state,
				resolutionState: {
					...state.resolutionState,
					[ operation ]: {
						...state.resolutionState?.[ operation ],
						[ id ]: undefined,
					},
				},
			};
		}

		default:
			return state;
	}
};

export default reducer;
