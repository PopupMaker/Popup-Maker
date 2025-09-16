import { __ } from '@popup-maker/i18n';

import { DispatchStatus } from '../constants';
import { getErrorMessage } from '../utils';

import { ACTION_TYPES } from './constants';
import { fetchLinkSuggestions } from './utils';

import type { ReducerAction } from './reducer';
import type { SearchOptions, ThunkAction, WPLinkSearchResult } from './types';

const {
	SEARCH_REQUEST,
	SEARCH_SUCCESS,
	SEARCH_ERROR,
	CHANGE_ACTION_STATUS,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

const searchActions = {
	/**
	 * Set query search text.
	 * @param {string}        queryText     Query text.
	 * @param {SearchOptions} searchOptions Search options.
	 */
	updateSuggestions:
		(
			/**
			 * Query text.
			 */
			queryText: string,
			/**
			 * Search options.
			 */
			searchOptions?: SearchOptions
		): ThunkAction =>
		async ( { dispatch, registry } ) => {
			const actionName = 'updateSuggestions';

			try {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Resolving
				);

				await registry.batch( async () => {
					dispatch.searchRequest( queryText );

					const results = await fetchLinkSuggestions(
						queryText,
						searchOptions
					);

					if ( results ) {
						dispatch.changeActionStatus(
							actionName,
							DispatchStatus.Success
						);

						dispatch.searchSuccess( queryText, results );
					}

					const errorMessage = __(
						'No results returned',
						'popup-maker'
					);

					dispatch.changeActionStatus(
						actionName,
						DispatchStatus.Error,
						errorMessage
					);

					dispatch.searchError( queryText, errorMessage );
				} );
			} catch ( error ) {
				const errorMessage = getErrorMessage( error );

				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Error,
					errorMessage
				);
				dispatch.searchError( queryText, errorMessage );
			}
		},

	/**
	 * Populate search results.
	 *
	 * @param {string} queryText Query text.
	 */
	searchRequest: (
		/**
		 * Query text.
		 */
		queryText: string
	): ReducerAction =>
		( {
			type: SEARCH_REQUEST,
			payload: {
				queryText,
			},
		} ) as ReducerAction,

	/**
	 * Populate search results.
	 *
	 * @param {string}               queryText Query text.
	 * @param {WPLinkSearchResult[]} results   Search results.
	 */
	searchSuccess: (
		/**
		 * Query text.
		 */
		queryText: string,
		/**
		 * Search results.
		 */
		results: WPLinkSearchResult[]
	): ReducerAction => {
		return {
			type: SEARCH_SUCCESS,
			payload: {
				queryText,
				results,
			},
		} as ReducerAction;
	},

	/**
	 * Generate a search error action.
	 *
	 * @param {string} queryText Query text.
	 * @param {string} error     Error message.
	 */
	searchError: (
		/**
		 * Query text.
		 */
		queryText: string,
		/**
		 * Error message.
		 */
		error: string
	): ReducerAction => {
		return {
			type: SEARCH_ERROR,
			payload: {
				queryText,
				error,
			},
		};
	},
};

/*****************************************************
 * SECTION: Resolution actions
 *****************************************************/
const resolutionActions = {
	/**
	 * Change status of a dispatch action request.
	 *
	 * @param {CallToActionsStore[ 'ActionNames' ]} actionName Action name to change status of.
	 * @param {Statuses}                            status     New status.
	 * @param {string|undefined}                    message    Optional error message.
	 * @return {Object} Action object.
	 */
	changeActionStatus:
		(
			actionName: string,
			status: DispatchStatus,
			message?: string | { message: string; [ key: string ]: any }
		): ThunkAction =>
		( { dispatch } ) => {
			if ( message ) {
				// eslint-disable-next-line no-console
				console.log( actionName, message );
			}

			dispatch( {
				type: CHANGE_ACTION_STATUS,
				actionName,
				status,
				message,
			} );
		},

	/**
	 * Start resolution for an entity.
	 */
	// startResolution:
	// 	( id: number | string, operation: string = 'fetch' ) =>
	// 	( { dispatch } ) => {
	// 		console.log( 'startResolution', id, operation );
	// 		dispatch( {
	// 			type: START_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				operation,
	// 			},
	// 		} );
	// 	},

	/**
	 * Finish resolution for an entity.
	 */
	// finishResolution:
	// 	( id: number | string, operation: string = 'fetch' ) =>
	// 	( { dispatch } ) => {
	// 		dispatch( {
	// 			type: FINISH_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				operation,
	// 			},
	// 		} );
	// 	},

	/**
	 * Fail resolution for an entity.
	 */
	// failResolution:
	// 	(
	// 		id: number | string,
	// 		error: string,
	// 		operation: string = 'fetch',
	// 		extra?: Record< string, any >
	// 	): ThunkAction =>
	// 	( { dispatch } ) => {
	// 		dispatch( {
	// 			type: FAIL_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				error,
	// 				operation,
	// 				extra,
	// 			},
	// 		} );
	// 	},

	/**
	 * Invalidate resolution for an entity.
	 *
	 * @param {number | string} id The entity ID.
	 * @return {Promise<void>}
	 */
	invalidateResolution:
		( id: number | string ): ThunkAction =>
		( { dispatch } ) => {
			dispatch( {
				type: INVALIDATE_RESOLUTION,
				payload: {
					id,
				},
			} );
		},
};

const actions = {
	// Search actions
	...searchActions,
	// Resolution actions
	...resolutionActions,
};

export default actions;
