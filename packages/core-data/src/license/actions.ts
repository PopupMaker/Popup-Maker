import { __ } from '@wordpress/i18n';
import { resolveSelect } from '@wordpress/data';

import { Status } from '../constants';
import { fetch } from '../controls';
import { getErrorMessage } from '../utils';
import { ACTION_TYPES, STORE_NAME } from './constants';
import { getResourcePath } from './utils';

import type { Statuses } from '../constants';

import type {
	License,
	LicenseStatusResponse,
	LicenseActivationResponse,
	LicenseKey,
	LicenseStore,
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
 *
 * @param {LicenseStore[ 'ActionNames' ]} actionName Action name to change status of.
 * @param {Statuses}                      status     New status.
 * @param {string | undefined}            message    Optional error message.
 * @return {Object} Action object.
 */
export const changeActionStatus = (
	actionName: LicenseStore[ 'ActionNames' ],
	status: Statuses,
	message?: string | undefined
): {
	type: string;
	actionName: LicenseStore[ 'ActionNames' ];
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

/**
 * Activate license.
 *
 * @param {LicenseKey|undefined} licenseKey License key to activate.
 * @return {Generator} Action object.
 */
export function* activateLicense( licenseKey?: LicenseKey ): Generator {
	const actionName = 'activateLicense';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const result = ( yield fetch( getResourcePath( 'activate' ), {
			method: 'POST',
			body: { licenseKey },
		} ) ) as LicenseActivationResponse;

		if ( result ) {
			const { status, connectInfo } = result;

			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			if ( connectInfo !== undefined ) {
				return {
					type: CONNECT_SITE,
					licenseStatus: status,
					connectInfo,
				};
			}

			return {
				type: ACTIVATE_LICENSE,
				licenseStatus: status,
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, license were not saved.', 'popup-paker' )
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Deactivate license.
 *
 * @return {Generator} Action object.
 */
export function* deactivateLicense(): Generator {
	const actionName = 'deactivateLicense';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const result = ( yield fetch( getResourcePath( 'deactivate' ), {
			method: 'POST',
		} ) ) as LicenseStatusResponse;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return {
				type: DEACTIVATE_LICENSE,
				licenseStatus: result.status,
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, license were not saved.', 'popup-paker' )
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Check license status.
 *
 * @return {Generator} Action object.
 */
export function* checkLicenseStatus(): Generator {
	const actionName = 'checkLicenseStatus';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const result = ( yield fetch( getResourcePath( 'status' ), {
			method: 'POST',
		} ) ) as LicenseStatusResponse;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return {
				type: CHECK_LICENSE_STATUS,
				licenseStatus: result.status,
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, license were not saved.', 'popup-paker' )
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Change license key.
 *
 * @param {LicenseKey} licenseKey License key to change to.
 * @return {Generator} Action object.
 */
export function* updateLicenseKey( licenseKey: LicenseKey ): Generator {
	const actionName = 'updateLicenseKey';

	const currentKey = yield resolveSelect( STORE_NAME, 'getLicenseKey' );

	if ( currentKey === licenseKey ) {
		return changeActionStatus(
			actionName,
			Status.Error,
			__(
				'The license key is the same as the current one.',
				'popup-paker'
			)
		);
	}

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const result = ( yield fetch( getResourcePath(), {
			method: 'POST',
			body: { licenseKey },
		} ) ) as LicenseStatusResponse;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return {
				type: UPDATE_LICENSE_KEY,
				licenseKey,
				licenseStatus: result.status,
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, license were not saved.', 'popup-paker' )
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Remove license.
 *
 * @return {Generator} Action object.
 */
export function* removeLicense(): Generator {
	const actionName = 'removeLicense';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const result = ( yield fetch( getResourcePath(), {
			method: 'DELETE',
		} ) ) as boolean;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return {
				type: REMOVE_LICENSE,
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, license were not saved.', 'popup-paker' )
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Activate pro version if installed.
 *
 * @return {Generator} Action object.
 */
export function* activatePro(): Generator {
	const actionName = 'activatePro';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		const result = ( yield fetch( getResourcePath( 'activate-pro' ), {
			method: 'POST',
		} ) ) as boolean;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			return changeActionStatus( actionName, Status.Success );
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, license were not saved.', 'popup-paker' )
		);
	} catch ( error ) {
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			getErrorMessage( error )
		);
	}
}

/**
 * Hydrate license data.
 *
 * @param {License} license
 * @return {Object} Action object.
 */
export const hydrate = (
	license: License
): { type: string; license: License } => {
	return {
		type: HYDRATE_LICENSE_DATA,
		license,
	};
};
