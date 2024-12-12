import { __ } from '@wordpress/i18n';

import { fetch } from '../controls';
import { getErrorMessage } from '../utils';
import { hydrate } from './actions';
import { ACTION_TYPES } from './constants';
import { getResourcePath } from './utils';

import type { Settings } from './types';

const { SETTINGS_FETCH_ERROR } = ACTION_TYPES;

export function* getSettings() {
	// catch any request errors.
	try {
		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const { settings }: { settings: Settings } = yield fetch(
			getResourcePath(),
			{
				method: 'GET',
			}
		);

		if ( settings ) {
			return hydrate( settings );
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		return {
			type: SETTINGS_FETCH_ERROR,
			message: __(
				'An error occurred, settings were not loaded.',
				'popup-paker'
			),
		};
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return {
			type: SETTINGS_FETCH_ERROR,
			message: getErrorMessage( error ),
		};
	}
}
