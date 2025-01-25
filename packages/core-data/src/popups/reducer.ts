import { ACTION_TYPES, initialState } from './constants';

import type { EditorId } from '../types';

const { EDITOR_CHANGE_ID } = ACTION_TYPES;

/**
 * The shape of the state for the call to actions store.
 */
export type State = {
	editorId?: EditorId;
};

type BaseAction = {
	type: keyof typeof ACTION_TYPES;
};

type ChangeEditorAction = BaseAction & {
	type: typeof EDITOR_CHANGE_ID;
	editorId: EditorId;
};

export type ReducerAction = ChangeEditorAction;

const reducer = ( state: State = initialState, action: ReducerAction ) => {
	switch ( action.type ) {
		case EDITOR_CHANGE_ID:
			return {
				...state,
				editorId: action.editorId,
			};

		default:
			return state;
	}
};

export default reducer;
