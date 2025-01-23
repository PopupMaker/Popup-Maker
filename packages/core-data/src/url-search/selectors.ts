import { createSelector } from '@wordpress/data';

import { DispatchStatus } from '../constants';

import type { State } from './reducer';
import type { StoreActionNames, WPLinkSearchResult } from './types';

/**
 * Get search results for link suggestions.
 */
export const getSuggestions = createSelector(
	( state: State ): WPLinkSearchResult[] => state.searchResults || [],
	( state: State ) => [ state.searchResults ]
);

/**
 * Get current status for dispatched action.
 */
export const getDispatchStatus = (
	state: State,
	/**
	 * Action name to check.
	 */
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
 */
export const isDispatching = createSelector(
	(
		state: State,
		/**
		 * Action name to check.
		 */
		actionNames: StoreActionNames | StoreActionNames[]
	): boolean => {
		if ( ! Array.isArray( actionNames ) ) {
			return (
				getDispatchStatus( state, actionNames ) ===
				DispatchStatus.Resolving
			);
		}

		let dispatching = false;

		for ( let i = 0; actionNames.length > i; i++ ) {
			dispatching =
				getDispatchStatus( state, actionNames[ i ] ) ===
				DispatchStatus.Resolving;

			if ( dispatching ) {
				return true;
			}
		}

		return dispatching;
	},
	( state: State, actionNames: StoreActionNames | StoreActionNames[] ) => [
		state.dispatchStatus,
		actionNames,
	]
);

/**
 * Check if action has finished dispatching.
 */
export const hasDispatched = createSelector(
	(
		state: State,
		/**
		 * Action name to check.
		 */
		actionName: StoreActionNames
	): boolean => {
		const status = getDispatchStatus( state, actionName );

		return !! (
			status &&
			(
				[ DispatchStatus.Success, DispatchStatus.Error ] as string[]
			 ).indexOf( status ) >= 0
		);
	},
	( state: State, actionName: StoreActionNames ) => [
		state.dispatchStatus,
		actionName,
	]
);

/**
 * Get dispatch action error if exists.
 */
export const getDispatchError = (
	state: State,
	/**
	 * Action name to check.
	 */
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.error;
