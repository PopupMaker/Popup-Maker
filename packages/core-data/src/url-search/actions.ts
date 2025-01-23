import { __ } from '@wordpress/i18n';

import { DispatchStatus } from '../constants';
import { getErrorMessage } from '../utils';

import { ACTION_TYPES } from './constants';
import { fetchLinkSuggestions } from './utils';

import type { DispatchStatuses } from '../constants';
import type { ReducerAction } from './reducer';
import type {
	SearchOptions,
	StoreActionNames,
	ThunkAction,
	WPLinkSearchResult,
} from './types';

const { SEARCH_REQUEST, SEARCH_SUCCESS, SEARCH_ERROR, CHANGE_ACTION_STATUS } =
	ACTION_TYPES;

/**
 * Set query search text.
 */
export const updateSuggestions =
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
	async ( { dispatch } ) => {
		const actionName = 'updateSuggestions';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

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

			const errorMessage = __( 'No results returned', 'popup-paker' );

			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				errorMessage
			);

			dispatch.searchError( queryText, errorMessage );
		} catch ( error ) {
			const errorMessage = getErrorMessage( error );

			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				errorMessage
			);
			dispatch.searchError( queryText, errorMessage );
		}
	};

/**
 * Populate search results.
 */
export const searchRequest = (
	/**
	 * Query text.
	 */
	queryText: string
): ReducerAction => {
	return {
		type: SEARCH_REQUEST,
		queryText,
	} as ReducerAction;
};

/**
 * Populate search results.
 */
export const searchSuccess = (
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
		queryText,
		results,
	} as ReducerAction;
};

/**
 * Generate a search error action.
 */
export const searchError = (
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
		queryText,
		error,
	};
};

/**
 * Change status of a dispatch action request.
 */
export const changeActionStatus = (
	/**
	 * Action name.
	 */
	actionName: StoreActionNames,
	/**
	 * Status.
	 */
	status: DispatchStatuses,
	/**
	 * Message.
	 */
	message?: string | undefined
) => {
	if ( message ) {
		// eslint-disable-next-line no-console
		console.log( actionName, message );
	}

	return {
		type: CHANGE_ACTION_STATUS,
		actionName,
		status,
		message,
	} as ReducerAction;
};
