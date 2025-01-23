import { __ } from '@wordpress/i18n';

import { getErrorMessage, fetchFromApi } from '../utils';
import { LICENSE_FETCH_ERROR } from './constants';
import { apiPath } from './utils';

import type { License, LicenseKey, LicenseStatus, ThunkAction } from './types';

export const getLicenseData =
	(): ThunkAction =>
	async ( { dispatch } ) => {
		try {
			const results = await fetchFromApi< License >( apiPath(), {
				method: 'GET',
			} );

			if ( results ) {
				dispatch.hydrate( results );
				return;
			}

			dispatch( {
				type: LICENSE_FETCH_ERROR,
				message: __(
					'An error occurred, license data was not loaded.',
					'popup-paker'
				),
			} );
		} catch ( error ) {
			dispatch( {
				type: LICENSE_FETCH_ERROR,
				message: getErrorMessage( error ),
			} );
		}
	};

export const getLicenseKey =
	(): ThunkAction< LicenseKey > =>
	async ( { resolveSelect } ) => {
		const { key = '' } = ( await resolveSelect.getLicenseData() ) ?? {};

		return key;
	};

export const getLicenseStatus =
	(): ThunkAction< LicenseStatus > =>
	async ( { resolveSelect } ) => {
		const { status } = ( await resolveSelect.getLicenseData() ) ?? {};

		return status;
	};
