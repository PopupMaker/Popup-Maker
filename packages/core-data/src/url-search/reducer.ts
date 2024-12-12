import { ACTION_TYPES, initialState } from './constants';

import type { Statuses } from '../constants';
import type {
	URLSearchState,
	URLSearchStore,
	WPLinkSearchResult,
} from './types';

const { SEARCH_ERROR, SEARCH_REQUEST, SEARCH_SUCCESS, CHANGE_ACTION_STATUS } =
	ACTION_TYPES;

interface ActionPayloadTypes {
	type: keyof typeof ACTION_TYPES;
	queryText: string;
	results: WPLinkSearchResult[];
	error: string;
	// Boilerplate.
	actionName: URLSearchStore[ 'ActionNames' ];
	status: Statuses;
	message: string;
}

const reducer = (
	state: URLSearchState = initialState,
	{
		type,
		queryText,
		results,
		error,
		// Boilerplate
		actionName,
		status,
		message,
	}: ActionPayloadTypes
) => {
	switch ( type ) {
		case SEARCH_REQUEST:
			return {
				...state,
				currentQuery: queryText,
			};

		case SEARCH_SUCCESS:
			if ( state.currentQuery === queryText ) {
				return {
					searchResults: results,
				};
			}
			return state;

		case SEARCH_ERROR:
			if ( state.currentQuery === queryText ) {
				return {
					...state,
					error,
				};
			}
			return state;

		case CHANGE_ACTION_STATUS:
			return {
				...state,
				dispatchStatus: {
					...state.dispatchStatus,
					[ actionName ]: {
						...( state?.dispatchStatus?.[ actionName ] ?? {} ),
						status,
						error: message,
					},
				},
			};

		default:
			return state;
	}
};

export default reducer;
