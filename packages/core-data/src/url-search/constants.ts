import type { State } from './reducer';

export const STORE_NAME = 'popup-maker/url-search';

// Search actions.
export const SEARCH_REQUEST = 'SEARCH_REQUEST';
export const SEARCH_SUCCESS = 'SEARCH_SUCCESS';
export const SEARCH_ERROR = 'SEARCH_ERROR';
export const UPDATE_SUGGESTIONS = 'UPDATE_SUGGESTIONS';

// Resolution actions.
export const CHANGE_ACTION_STATUS = 'CHANGE_ACTION_STATUS';
export const INVALIDATE_RESOLUTION = 'INVALIDATE_RESOLUTION';

export const ACTION_TYPES: {
	SEARCH_REQUEST: typeof SEARCH_REQUEST;
	SEARCH_SUCCESS: typeof SEARCH_SUCCESS;
	SEARCH_ERROR: typeof SEARCH_ERROR;
	UPDATE_SUGGESTIONS: typeof UPDATE_SUGGESTIONS;
	CHANGE_ACTION_STATUS: typeof CHANGE_ACTION_STATUS;
	INVALIDATE_RESOLUTION: typeof INVALIDATE_RESOLUTION;
} = {
	SEARCH_REQUEST,
	SEARCH_SUCCESS,
	SEARCH_ERROR,
	UPDATE_SUGGESTIONS,
	CHANGE_ACTION_STATUS,
	INVALIDATE_RESOLUTION,
};

export const initialState: State = {
	currentQuery: '',
	searchResults: [],
	queries: {},
	resolutionState: {},
};
