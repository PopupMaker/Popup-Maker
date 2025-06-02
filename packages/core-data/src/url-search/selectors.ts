import { createSelector } from '@wordpress/data';

import { DispatchStatus } from '../constants';

import type { State } from './reducer';
import type { WPLinkSearchResult } from './types';

/*****************************************************
 * SECTION: Suggestion selectors
 *****************************************************/
const suggestionSelectors = {
	/**
	 * Get search results for link suggestions.
	 */
	getSuggestions: createSelector(
		( state: State ): WPLinkSearchResult[] => state.searchResults || [],
		( state: State ) => [ state.searchResults ]
	),
};

/*****************************************************
 * SECTION: Resolution state selectors
 *****************************************************/
const resolutionSelectors = {
	/**
	 * Get resolution state for a specific entity.
	 */
	getResolutionState: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = state.resolutionState?.[ id ];

			// If no resolution state exists, return idle
			if ( ! resolutionState ) {
				return {
					status: DispatchStatus.Idle,
				};
			}

			return resolutionState;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if a resolution is idle.
	 */
	isIdle: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Idle;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity is currently being resolved.
	 */
	isResolving: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Resolving;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity resolution has completed successfully.
	 */
	hasResolved: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Success;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity resolution has failed.
	 */
	hasFailed: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Error;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Get the error for a failed resolution.
	 */
	getResolutionError: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.error;
		},
		( _state: State, id: number | string ) => [ id ]
	),
};

const selectors = {
	// Suggestion selectors
	...suggestionSelectors,
	// Resolution state selectors
	...resolutionSelectors,
};

export default selectors;
