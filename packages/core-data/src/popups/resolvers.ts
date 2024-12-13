import { __, sprintf } from '@wordpress/i18n';

import { fetch } from '../controls';
import { appendUrlParams, getErrorMessage } from '../utils';
import { hydrate } from './actions';
import { ACTION_TYPES } from './constants';
import { convertApiPopup, getResourcePath } from './utils';

import type { Popup, ApiPopup } from './types';

const { UPDATE, POPUPS_FETCH_ERROR } = ACTION_TYPES;

/**
 * Resolves get popups requests from the server.
 *
 * @return {Generator} Action object to hydrate store.
 */
export function* getPopups(): Generator {
	// catch any request errors.
	try {
		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const popups: ApiPopup[] = yield fetch(
			appendUrlParams( getResourcePath(), {
				status: [ 'any', 'trash', 'auto-draft' ],
				per_page: 100,
				context: 'edit',
			} )
		);

		if ( popups ) {
			// Parse popups, replacing title & content with the API context versions.
			const parsedPopups = popups.map( convertApiPopup );

			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			return hydrate( parsedPopups );
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		return {
			type: POPUPS_FETCH_ERROR,
			message: __(
				'An error occurred, popups were not loaded.',
				'popup-maker'
			),
		};
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return {
			type: POPUPS_FETCH_ERROR,
			message: getErrorMessage( error ),
		};
	}
}

/**
 * Resolves get popups requests from the server.
 *
 * @param {number} popupId
 *
 * @return {Generator} Action object to update single popup store.
 */
export function* getPopup( popupId: Popup[ 'id' ] ): Generator {
	// catch any request errors.
	try {
		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const popup: ApiPopup = yield fetch(
			appendUrlParams( getResourcePath( popupId ), {
				context: 'edit',
			} )
		);

		if ( popup ) {
			return {
				type: UPDATE,
				popup: convertApiPopup( popup ),
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		return {
			type: POPUPS_FETCH_ERROR,
			message: sprintf(
				/* translators: 1: popup id */
				__(
					`An error occurred, popup %d were not loaded.`,
					'popup-maker'
				),
				popupId
			),
		};
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return {
			type: POPUPS_FETCH_ERROR,
			message: getErrorMessage( error ),
		};
	}
}
