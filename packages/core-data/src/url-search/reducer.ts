import type { DispatchStatuses, ResolutionState } from '../constants';
import { ACTION_TYPES, initialState } from './constants';

import type { WPLinkSearchResult, URLSearchQuery } from './types';

const {
	SEARCH_ERROR,
	SEARCH_REQUEST,
	SEARCH_SUCCESS,
	CHANGE_ACTION_STATUS,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

/**
 * The state of the URL search store.
 */
export type State = {
	/**
	 * The current query.
	 */
	currentQuery?: string;
	/**
	 * The search results.
	 */
	searchResults?: WPLinkSearchResult[];
	/**
	 * The queries.
	 */
	queries: Record< URLSearchQuery[ 'text' ], URLSearchQuery >;
	/**
	 * The error.
	 */
	error?: string;

	/**
	 * The resolution state for each operation.
	 */
	resolutionState: Record< string | number, ResolutionState >;
};

/**
 * The base action for the URL search store.
 */
export type BaseAction = {
	type: keyof typeof ACTION_TYPES;
	payload?: Record< string, any >;
};

/**
 * The action for the URL search store.
 */
export type SearchRequestAction = BaseAction & {
	type: typeof SEARCH_REQUEST;
	payload: {
		queryText: string;
	};
};

/**
 * The action for the URL search store.
 */
export type SearchSuccessAction = BaseAction & {
	type: typeof SEARCH_SUCCESS;
	payload: {
		queryText: string;
		results: WPLinkSearchResult[];
	};
};

/**
 * The action for the URL search store.
 */
export type SearchErrorAction = BaseAction & {
	type: typeof SEARCH_ERROR;
	payload: {
		queryText: string;
		error: string;
	};
};

export type ChangeActionStatusAction = BaseAction & {
	type: typeof CHANGE_ACTION_STATUS;
	actionName: string;
	status: DispatchStatuses;
	message?: string;
};

export type InvalidateResolutionAction = BaseAction & {
	type: typeof INVALIDATE_RESOLUTION;
	payload: {
		id: number | string;
		operation: string;
	};
};

/**
 * The action for the URL search store.
 */
export type ReducerAction =
	| SearchRequestAction
	| SearchSuccessAction
	| SearchErrorAction
	| ChangeActionStatusAction
	| InvalidateResolutionAction;

export const reducer = (
	state: State = initialState,
	action: ReducerAction
): State => {
	switch ( action.type ) {
		case SEARCH_REQUEST: {
			const { queryText } = action.payload;

			return {
				...state,
				currentQuery: queryText,
			};
		}

		case SEARCH_SUCCESS: {
			const { queryText, results } = action.payload;

			if ( state.currentQuery === queryText ) {
				return {
					...state,
					searchResults: results,
				};
			}
			return state;
		}

		case SEARCH_ERROR: {
			const { queryText, error } = action.payload;

			if ( state.currentQuery === queryText ) {
				return {
					...state,
					error,
				};
			}
			return state;
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
