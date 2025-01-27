import { ACTION_TYPES, initialState } from './constants';

import type { DispatchStatuses } from '../constants';
import type { EditorId, Notice, GetRecordsHttpQuery } from '../types';
import type { CallToAction, EditableCta, CtaEdit } from './types';

const {
	RECIEVE_RECORD,
	RECIEVE_RECORDS,
	RECIEVE_QUERY_RECORDS,
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

export type ResolutionState = {
	status: DispatchStatuses;
	error?: string;
	timestamp?: number;
};

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
	editHistory: Record< number, CtaEdit[] >;

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
	type: typeof RECIEVE_RECORD;
	payload: {
		record: CallToAction;
	};
};

export type RecieveRecordsAction = BaseAction & {
	type: typeof RECIEVE_RECORDS;
	payload: {
		records: CallToAction< 'edit' >[];
	};
};

export type RecieveQueryRecordsAction = BaseAction & {
	type: typeof RECIEVE_QUERY_RECORDS;
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
	};
};

export type EditRecordAction = BaseAction & {
	type: typeof EDIT_RECORD;
	payload: {
		id: number;
		edits: CtaEdit;
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
	actionName: string;
	status: DispatchStatuses;
	message?: string;
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
	| ChangeEditorAction
	| StartEditingRecordAction
	| EditRecordAction
	| UndoEditRecordAction
	| RedoEditRecordAction
	| ResetEditRecordAction
	| SaveEditedRecordAction
	| ChangeActionStatusAction
	| InvalidateResolutionAction;

const reducer = ( state: State = initialState, action: ReducerAction ) => {
	switch ( action.type ) {
		case RECIEVE_RECORD: {
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

		case RECIEVE_RECORDS:
		case RECIEVE_QUERY_RECORDS: {
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
							[ JSON.stringify( query ) ]: records,
					  }
					: state.queries,
			};
		}

		case PURGE_RECORD: {
			const { id: entityId } = action.payload;

			// Remove the entity from the allIds array.
			const allIds = state.allIds.filter( ( id ) => id !== entityId );

			// Remove the entity from the byId object.
			const { [ entityId ]: _1, ...byId } = state.byId;

			// Remove the entity from the editedEntities object.
			const { [ entityId ]: _2, ...editedEntities } =
				state.editedEntities;

			// Remove the entity from the editHistory object.
			const { [ entityId ]: _3, ...editHistory } = state.editHistory;

			// Remove the entity from the editHistoryIndex object.
			const { [ entityId ]: _4, ...editHistoryIndex } =
				state.editHistoryIndex;

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
			const { id, editableEntity } = action.payload;

			return {
				...state,
				editedEntities: {
					...state.editedEntities,
					[ id ]: editableEntity,
				},
				editHistory: {
					...state.editHistory,
					[ id ]: [],
				},
				editHistoryIndex: {
					...state.editHistoryIndex,
					[ id ]: 0,
				},
			};
		}

		case EDIT_RECORD: {
			const { id, edits } = action.payload;

			const editHistory = state.editHistory[ id ] ?? [];
			const newEditHistory = [ ...editHistory, edits ];

			return {
				...state,
				editHistory: {
					...state.editHistory,
					[ id ]: newEditHistory,
				},
				editHistoryIndex: {
					...state.editHistoryIndex,
					[ id ]: newEditHistory.length - 1,
				},
			};
		}

		case UNDO_EDIT_RECORD: {
			const { id, steps = 1 } = action.payload;

			const currentIndex = state.editHistoryIndex[ id ] ?? 0;
			const newIndex = Math.max( 0, currentIndex - steps );

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

			const currentIndex = state.editHistoryIndex[ id ] ?? 0;
			const newIndex = Math.min(
				state.editHistory[ id ].length - 1,
				currentIndex + steps
			);

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

			const remainingEdits = state.editHistory[ id ].slice(
				historyIndex + 1
			);

			// If no edits, remove the edited entity & edit history.
			if ( remainingEdits.length === 0 ) {
				const { [ id ]: _1, ...editedEntities } = state.editedEntities;
				const { [ id ]: _2, ...editHistory } = state.editHistory;
				const { [ id ]: _3, ...editHistoryIndex } =
					state.editHistoryIndex;

				return {
					...state,
					editedEntities,
					editHistory,
					editHistoryIndex,
				};
			}

			return {
				...state,
				editedEntities: {
					...state.editedEntities,
					// Replace the edited entity with the new entity.
					[ id ]: editedEntity,
				},
				editHistory: {
					...state.editHistory,
					// Trim the edit history to the current index.
					[ id ]: remainingEdits,
				},
				editHistoryIndex: {
					// Reset the edit history index to 0.
					...state.editHistoryIndex,
					[ id ]: 0,
				},
			};
		}

		case RESET_EDIT_RECORD: {
			const { id } = action.payload;

			const { [ id ]: _1, ...editedEntities } = state.editedEntities;
			const { [ id ]: _2, ...editHistory } = state.editHistory;
			const { [ id ]: _3, ...editHistoryIndex } = state.editHistoryIndex;

			return {
				...state,
				editedEntities,
				editHistory,
				editHistoryIndex,
			};
		}

		case CHANGE_ACTION_STATUS: {
			const { actionName, status, message } = action;

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
