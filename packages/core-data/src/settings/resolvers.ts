import { __ } from '@popup-maker/i18n';

import { fetchFromApi, getErrorMessage } from '../utils';
import { SETTINGS_FETCH_ERROR } from './constants';
import { apiPath } from './utils';

import type { Settings, ThunkAction } from './types';

const settingsResolvers = {
	getSettings:
		(): ThunkAction =>
		async ( { dispatch, registry } ) => {
			try {
				const settings = await fetchFromApi< Settings >( apiPath(), {
					method: 'GET',
				} );

				registry.batch( () => {
					if ( settings ) {
						dispatch.hydrate( settings );
					}

					dispatch( {
						type: SETTINGS_FETCH_ERROR,
						message: __(
							'An error occurred, settings were not loaded.',
							'popup-maker'
						),
					} );
				} );
			} catch ( error ) {
				dispatch( {
					type: SETTINGS_FETCH_ERROR,
					message: getErrorMessage( error ),
				} );
			}
		},
};

export default settingsResolvers;
