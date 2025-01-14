import { __ } from '@wordpress/i18n';

import { Status } from '../constants';
import { getErrorMessage } from '../utils';
import { ACTION_TYPES } from './constants';
import { fetchLinkSuggestions } from './controls';

import type { Statuses } from '../constants';
import type {
	SearchOptions,
	URLSearchStore,
	WPLinkSearchResult,
} from './types';

const { SEARCH_REQUEST, SEARCH_SUCCESS, SEARCH_ERROR, CHANGE_ACTION_STATUS } =
	ACTION_TYPES;

/**
 * Set query search text.
 *
 * @param {string}                    queryText     Query text.
 * @param {SearchOptions[]|undefined} searchOptions Search options.
 * @return {Generator} Action object.
 */
export function* updateSuggestions(
	queryText: string,
	searchOptions?: SearchOptions
): Generator {
	const actionName = 'updateSuggestions';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		yield searchRequest( queryText );

		const results = ( yield fetchLinkSuggestions(
			queryText,
			searchOptions
		) ) as WPLinkSearchResult[];

		if ( results ) {
			yield changeActionStatus( actionName, Status.Success );

			return searchSuccess( queryText, results );
		}

		const errorMessage = __( 'No results returned', 'popup-paker' );

		yield changeActionStatus( actionName, Status.Error, errorMessage );

		return searchError( queryText, errorMessage );
	} catch ( error ) {
		const errorMessage = getErrorMessage( error );

		yield changeActionStatus( actionName, Status.Error, errorMessage );
		return searchError( queryText, errorMessage );
	}
}

/**
 * Populate search results.
 *
 * @param {string} queryText Query text.
 * @return {Object} Action object.
 */
export function searchRequest( queryText: string ): {
	type: string;
	queryText: string;
} {
	return {
		type: SEARCH_REQUEST,
		queryText,
	};
}

/**
 * Populate search results.
 *
 * @param {string}               queryText Query text.
 * @param {WPLinkSearchResult[]} results   Search results.
 * @return {Object} Action object.
 */
export function searchSuccess(
	queryText: string,
	results: WPLinkSearchResult[]
): { type: string; queryText: string; results: WPLinkSearchResult[] } {
	return {
		type: SEARCH_SUCCESS,
		queryText,
		results,
	};
}

/**
 * Generate a search error action.
 *
 * @param {string} queryText Query text.
 * @param {string} error     Error message.
 * @return {Object} Action object.
 */
export function searchError(
	queryText: string,
	error: string
): { type: string; queryText: string; error: string } {
	return {
		type: SEARCH_ERROR,
		queryText,
		error,
	};
}

/**
 * Change status of a dispatch action request.
 *
 * @param {URLSearchStore[ 'ActionNames' ]} actionName Action name to change status of.
 * @param {Statuses}                        status     New status.
 * @param {string | undefined}              message    Optional error message.
 * @return {Object} Action object.
 */
export const changeActionStatus = (
	actionName: URLSearchStore[ 'ActionNames' ],
	status: Statuses,
	message?: string | undefined
): {
	type: string;
	actionName: URLSearchStore[ 'ActionNames' ];
	status: Statuses;
	message?: string;
} => {
	if ( message ) {
		// eslint-disable-next-line no-console
		console.log( actionName, message );
	}

	return {
		type: CHANGE_ACTION_STATUS,
		actionName,
		status,
		message,
	};
};
