import { __ } from '@wordpress/i18n';

import { DispatchStatus } from '../constants';
import { getErrorMessage, fetchFromApi } from '../utils';

import { ACTION_TYPES } from './constants';
import { apiPath } from './utils';

import type {
	License,
	LicenseActivationResponse,
	LicenseKey,
	LicenseStatusResponse,
	StoreActionNames,
	ThunkAction,
} from './types';

const {
	ACTIVATE_LICENSE,
	CONNECT_SITE,
	DEACTIVATE_LICENSE,
	UPDATE_LICENSE_KEY,
	REMOVE_LICENSE,
	CHECK_LICENSE_STATUS,
	HYDRATE_LICENSE_DATA,
	CHANGE_ACTION_STATUS,
} = ACTION_TYPES;

/**
 * Change status of a dispatch action request.
 */
export const changeActionStatus = (
	actionName: StoreActionNames,
	status: DispatchStatus,
	message?: string | undefined
): {
	type: string;
	actionName: StoreActionNames;
	status: DispatchStatus;
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

/**
 * Activate license.
 */
export const activateLicense =
	( licenseKey?: LicenseKey ): ThunkAction =>
	async ( { dispatch } ) => {
		const actionName = 'activateLicense';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const result = await fetchFromApi< LicenseActivationResponse >(
				apiPath( 'activate' ),
				{
					method: 'POST',
					data: { licenseKey },
				}
			);

			if ( result ) {
				const { status, connectInfo } = result;

				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				if ( connectInfo !== undefined ) {
					dispatch( {
						type: CONNECT_SITE,
						licenseStatus: status,
						connectInfo,
					} );
				}

				dispatch( {
					type: ACTIVATE_LICENSE,
					licenseStatus: status,
				} );

				return;
			}

			// if execution arrives here, then thing didn't update in the state so return
			// action object that will add an error to the state about this.
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, license were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Deactivate license.
 */
export const deactivateLicense =
	(): ThunkAction =>
	async ( { dispatch } ) => {
		const actionName = 'deactivateLicense';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const result = await fetchFromApi< LicenseStatusResponse >(
				apiPath( 'deactivate' ),
				{
					method: 'POST',
				}
			);

			if ( result ) {
				// thing was successfully updated so return the action object that will
				// update the saved thing in the state.
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				dispatch( {
					type: DEACTIVATE_LICENSE,
					licenseStatus: result.status,
				} );

				return;
			}

			// if execution arrives here, then thing didn't update in the state so return
			// action object that will add an error to the state about this.
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, license were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Check license status.
 */
export const checkLicenseStatus =
	(): ThunkAction =>
	async ( { dispatch } ) => {
		const actionName = 'checkLicenseStatus';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const result = await fetchFromApi< LicenseStatusResponse >(
				apiPath( 'status' ),
				{
					method: 'POST',
				}
			);

			if ( result ) {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				dispatch( {
					type: CHECK_LICENSE_STATUS,
					licenseStatus: result.status,
				} );

				return;
			}

			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, license were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Change license key.
 */
export const updateLicenseKey =
	( licenseKey: LicenseKey ): ThunkAction =>
	async ( { select, dispatch } ) => {
		const actionName = 'updateLicenseKey';

		const currentKey = select.getLicenseKey();

		if ( currentKey === licenseKey ) {
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'The license key is the same as the current one.',
					'popup-maker'
				)
			);

			return;
		}

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const result = await fetchFromApi< LicenseStatusResponse >(
				apiPath(),
				{
					method: 'POST',
					data: { licenseKey },
				}
			);

			if ( result ) {
				// thing was successfully updated so return the action object that will
				// update the saved thing in the state.
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				dispatch( {
					type: UPDATE_LICENSE_KEY,
					licenseKey,
					licenseStatus: result.status,
				} );

				return;
			}

			// if execution arrives here, then thing didn't update in the state so return
			// action object that will add an error to the state about this.
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, license were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Remove license.
 */
export const removeLicense =
	(): ThunkAction =>
	async ( { dispatch } ) => {
		const actionName = 'removeLicense';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const result = await fetchFromApi< boolean >( apiPath(), {
				method: 'DELETE',
			} );

			if ( result ) {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				dispatch( {
					type: REMOVE_LICENSE,
				} );

				return;
			}

			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, license were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Activate pro version if installed.
 */
export const activatePro =
	(): ThunkAction =>
	async ( { dispatch } ) => {
		const actionName = 'activatePro';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const result = await fetchFromApi< boolean >(
				apiPath( 'activate-pro' ),
				{
					method: 'POST',
				}
			);

			if ( result ) {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				return;
			}

			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, license were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			// returning an action object that will save the update error to the state.
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Hydrate license data.
 */
export const hydrate = (
	license: License
): { type: string; license: License } => {
	return {
		type: HYDRATE_LICENSE_DATA,
		license,
	};
};
