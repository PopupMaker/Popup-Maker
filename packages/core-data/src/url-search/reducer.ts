import { ACTION_TYPES, initialState } from './constants';

import type { DispatchStatuses } from '../constants';
import type {
	StoreActionNames,
	WPLinkSearchResult,
	URLSearchQuery,
} from './types';

const { SEARCH_ERROR, SEARCH_REQUEST, SEARCH_SUCCESS, CHANGE_ACTION_STATUS } =
	ACTION_TYPES;

export type State = {
	currentQuery?: string;
	searchResults?: WPLinkSearchResult[];
	queries: Record< URLSearchQuery[ 'text' ], URLSearchQuery >;
	// Boilerplate
	dispatchStatus?: {
		[ Property in StoreActionNames ]?: {
			status: string;
			error: string;
		};
	};
	error?: string;
};

type BaseAction = {
	type: keyof typeof ACTION_TYPES;
};

type SearchRequestAction = BaseAction & {
	type: typeof SEARCH_REQUEST;
	queryText: string;
};

type SearchSuccessAction = BaseAction & {
	type: typeof SEARCH_SUCCESS;
	queryText: string;
	results: WPLinkSearchResult[];
};

type SearchErrorAction = BaseAction & {
	type: typeof SEARCH_ERROR;
	queryText: string;
	error: string;
};

type ChangeActionStatusAction = BaseAction & {
	type: typeof CHANGE_ACTION_STATUS;
	actionName: StoreActionNames;
	status: DispatchStatuses;
	message: string;
};

export type ReducerAction =
	| SearchRequestAction
	| SearchSuccessAction
	| SearchErrorAction
	| ChangeActionStatusAction;

const reducer = ( state: State = initialState, action: ReducerAction ) => {
	switch ( action.type ) {
		case SEARCH_REQUEST:
			return {
				...state,
				currentQuery: action.queryText,
			};

		case SEARCH_SUCCESS:
			if ( state.currentQuery === action.queryText ) {
				return {
					...state,
					searchResults: action.results,
				};
			}
			return state;

		case SEARCH_ERROR:
			if ( state.currentQuery === action.queryText ) {
				return {
					...state,
					error: action.error,
				};
			}
			return state;

		case CHANGE_ACTION_STATUS:
			return {
				...state,
				dispatchStatus: {
					...state.dispatchStatus,
					[ action.actionName ]: {
						...( state?.dispatchStatus?.[ action.actionName ] ??
							{} ),
						status: action.status,
						error: action.message,
					},
				},
			};

		default:
			return state;
	}
};

export default reducer;
