import { ACTION_TYPES, initialState } from './constants';
import type { State } from './types';

const { EDITOR_CHANGE_ID } = ACTION_TYPES;

type ActionPayloadTypes = {
	type: typeof EDITOR_CHANGE_ID;
	editorId?: State[ 'editorId' ] | undefined;
};

const reducer = (
	state: State = initialState,
	{ type, editorId }: ActionPayloadTypes
) => {
	switch ( type ) {
		case EDITOR_CHANGE_ID:
			return {
				...state,
				editorId,
			};

		default:
			return state;
	}
};

export default reducer;
