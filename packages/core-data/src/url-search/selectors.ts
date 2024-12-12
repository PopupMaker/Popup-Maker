import { Status } from '../constants';

import type {
	URLSearchState,
	URLSearchStore,
	WPLinkSearchResult,
} from './types';

/**
 * Get search results for link suggestions.
 *
 * @param {URLSearchState} state Current state.
 * @return {WPLinkSearchResult[]} Array of link search results.
 */
export const getSuggestions = ( state: URLSearchState ): WPLinkSearchResult[] =>
	state.searchResults || [];

/**
 * Get current status for dispatched action.
 *
 * @param {URLSearchState}                state      Current state.
 * @param {URLSearchStore['ActionNames']} actionName Action name to check.
 *
 * @return {string} Current status for dispatched action.
 */
export const getDispatchStatus = (
	state: URLSearchState,
	actionName: URLSearchStore[ 'ActionNames' ]
): string | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
 *
 * @param {URLSearchState}                                                state       Current state.
 * @param {URLSearchStore['ActionNames']|URLSearchStore['ActionNames'][]} actionNames Action name or array of names to check.
 *
 * @return {boolean} True if is dispatching.
 */
export const isDispatching = (
	state: URLSearchState,
	actionNames:
		| URLSearchStore[ 'ActionNames' ]
		| URLSearchStore[ 'ActionNames' ][]
): boolean => {
	if ( ! Array.isArray( actionNames ) ) {
		return getDispatchStatus( state, actionNames ) === Status.Resolving;
	}

	let dispatching = false;

	for ( let i = 0; actionNames.length > i; i++ ) {
		dispatching =
			getDispatchStatus( state, actionNames[ i ] ) === Status.Resolving;

		if ( dispatching ) {
			return true;
		}
	}

	return dispatching;
};

/**
 * Check if action has finished dispatching.
 *
 * @param {URLSearchState}                state      Current state.
 * @param {URLSearchStore['ActionNames']} actionName Action name to check.
 *
 * @return {boolean} True if dispatched.
 */
export const hasDispatched = (
	state: URLSearchState,
	actionName: URLSearchStore[ 'ActionNames' ]
): boolean => {
	const status = getDispatchStatus( state, actionName );

	return !! (
		status &&
		( [ Status.Success, Status.Error ] as string[] ).indexOf( status ) >= 0
	);
};

/**
 * Get dispatch action error if esists.
 *
 * @param {URLSearchState}                state      Current state.
 * @param {URLSearchStore['ActionNames']} actionName Action name to check.
 *
 * @return {string|undefined} Current error message.
 */
export const getDispatchError = (
	state: URLSearchState,
	actionName: URLSearchStore[ 'ActionNames' ]
): string | undefined => state?.dispatchStatus?.[ actionName ]?.error;
