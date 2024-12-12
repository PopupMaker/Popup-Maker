import type { URLSearchState } from './types';

export const STORE_NAME = 'popup-maker/url-search';

export const ACTION_TYPES = {
	SEARCH_REQUEST: 'SEARCH_REQUEST',
	SEARCH_SUCCESS: 'SEARCH_SUCCESS',
	SEARCH_ERROR: 'SEARCH_ERROR',
	UPDATE_SUGGESTIONS: 'UPDATE_SUGGESTIONS',
	// Boilerplate.
	CHANGE_ACTION_STATUS: 'CHANGE_ACTION_STATUS',
};

export const initialState: URLSearchState = {
	currentQuery: '',
	searchResults: [],
	queries: {},
};
