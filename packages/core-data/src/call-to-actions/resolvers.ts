import { __, sprintf } from '@wordpress/i18n';

import { fetch } from '../controls';
import { appendUrlParams, getErrorMessage } from '../utils';
import { hydrate } from './actions';
import { ACTION_TYPES } from './constants';
import { convertApiCallToAction, getResourcePath } from './utils';

import type { CallToAction, ApiCallToAction } from './types';

const { UPDATE, CALL_TO_ACTIONS_FETCH_ERROR } = ACTION_TYPES;

/**
 * Resolves get call to action requests from the server.
 *
 * @return {Generator} Action object to hydrate store.
 */
export function* getCallToActions(): Generator {
	// catch any request errors.
	try {
		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const callToActions = ( yield fetch(
			appendUrlParams( getResourcePath(), {
				status: [ 'any', 'trash', 'auto-draft' ],
				per_page: 100,
				context: 'edit',
			} )
		) ) as ApiCallToAction[];

		if ( callToActions ) {
			// Parse call to actions, replacing title & content with the API context versions.
			const parsedCallToActions = callToActions.map(
				convertApiCallToAction
			);

			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			return hydrate( parsedCallToActions );
		}

		// if execution arrives here, then call to action didn't update in the state so return
		// action object that will add an error to the state about this.
		return {
			type: CALL_TO_ACTIONS_FETCH_ERROR,
			message: __(
				'An error occurred, call to actions were not loaded.',
				'popup-maker'
			),
		};
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return {
			type: CALL_TO_ACTIONS_FETCH_ERROR,
			message: getErrorMessage( error ),
		};
	}
}

/**
 * Resolves get call to action requests from the server.
 *
 * @param {number} callToActionId Call to action ID.
 *
 * @return {Generator} Action object to update single call to action store.
 */
export function* getCallToAction(
	callToActionId: CallToAction[ 'id' ]
): Generator {
	// catch any request errors.
	try {
		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const callToAction = ( yield fetch(
			appendUrlParams( getResourcePath( callToActionId ), {
				context: 'edit',
			} )
		) ) as ApiCallToAction;

		if ( callToAction ) {
			return {
				type: UPDATE,
				callToAction: convertApiCallToAction( callToAction ),
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		return {
			type: CALL_TO_ACTIONS_FETCH_ERROR,
			message: sprintf(
				/* translators: 1: call to action id */
				__(
					`An error occurred, call to action %d were not loaded.`,
					'popup-maker'
				),
				callToActionId
			),
		};
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return {
			type: CALL_TO_ACTIONS_FETCH_ERROR,
			message: getErrorMessage( error ),
		};
	}
}
