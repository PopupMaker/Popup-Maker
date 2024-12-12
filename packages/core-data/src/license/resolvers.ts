import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data-controls';

import { fetch } from '../controls';
import { getErrorMessage } from '../utils';
import { hydrate } from './actions';
import { ACTION_TYPES, STORE_NAME } from './constants';
import { getResourcePath } from './utils';

import type { License } from './types';

const { LICENSE_FETCH_ERROR } = ACTION_TYPES;

export function* getLicenseData() {
	// catch any request errors.
	try {
		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const results: License = yield fetch( getResourcePath(), {
			method: 'GET',
		} );

		if ( results ) {
			return hydrate( results );
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		return {
			type: LICENSE_FETCH_ERROR,
			message: __(
				'An error occurred, license data was not loaded.',
				'popup-paker'
			),
		};
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return {
			type: LICENSE_FETCH_ERROR,
			message: getErrorMessage( error ),
		};
	}
}

export function* getLicenseKey() {
	const { key } = yield select( STORE_NAME, 'getLicenseData' );
	return key;
}

export function* getLicenseStatus() {
	const { status } = yield select( STORE_NAME, 'getLicenseData' );
	return status;
}
