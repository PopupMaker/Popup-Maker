import { Status } from '../constants';
import { licenseStatusDefaults } from './constants';

import type {
	License,
	LicenseKey,
	LicenseStatus,
	LicenseState,
	LicenseStore,
	LicenseConnect,
} from './types';

/**
 *
 * @param {LicenseState} state Current state.
 * @return {License}           License data.
 */
export const getLicenseData = ( state: LicenseState ): License => state.license;

/**
 * Get license key.
 *
 * @param {LicenseState} state Current state.
 * @return {LicenseKey} Current license key.
 */
export const getLicenseKey = ( state: LicenseState ): LicenseKey => {
	const { key } = getLicenseData( state );
	return key;
};

/**
 * Get license status.
 *
 * @param {LicenseState} state LicenseState Current state.
 * @return {LicenseStatus} Current license status.
 */
export const getLicenseStatus = ( state: LicenseState ): LicenseStatus => {
	const { status } = getLicenseData( state );

	return {
		...licenseStatusDefaults,
		...status,
	};
};

/**
 * Get connect info for pro upgrade.
 *
 * @param {LicenseState} state LicenseState Current state.
 * @return {LicenseConnect|undefined} Current license status.
 */
export const getConnectInfo = (
	state: LicenseState
): LicenseConnect | undefined => state.connectInfo;

/**
 * Get current status for dispatched action.
 *
 * @param {LicenseState}                state      Current state.
 * @param {LicenseStore['ActionNames']} actionName Action name to check.
 *
 * @return {string} Current status for dispatched action.
 */
export const getDispatchStatus = (
	state: LicenseState,
	actionName: LicenseStore[ 'ActionNames' ]
): string | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
 *
 * @param {LicenseState}                                              state       Current state.
 * @param {LicenseStore['ActionNames']|LicenseStore['ActionNames'][]} actionNames Action name or array of names to check.
 *
 * @return {boolean} True if is dispatching.
 */
export const isDispatching = (
	state: LicenseState,
	actionNames: LicenseStore[ 'ActionNames' ] | LicenseStore[ 'ActionNames' ][]
): boolean => {
	if ( ! Array.isArray( actionNames ) ) {
		return getDispatchStatus( state, actionNames ) === Status.Resolving;
	}

	let dispatching = false;

	for ( let i = 0; actionNames.length > i; i++ ) {
		dispatching =
			getDispatchStatus( state, actionNames[ i ] ) === Status.Resolving;

		if ( dispatching ) {
			return true;
		}
	}

	return dispatching;
};

/**
 * Check if action has finished dispatching.
 *
 * @param {LicenseState}                state      Current state.
 * @param {LicenseStore['ActionNames']} actionName Action name to check.
 *
 * @return {boolean} True if dispatched.
 */
export const hasDispatched = (
	state: LicenseState,
	actionName: LicenseStore[ 'ActionNames' ]
): boolean => {
	const status = getDispatchStatus( state, actionName );

	return !! (
		status &&
		( [ Status.Success, Status.Error ] as string[] ).indexOf( status ) >= 0
	);
};

/**
 * Get dispatch action error if esists.
 *
 * @param {LicenseState}                state      Current state.
 * @param {LicenseStore['ActionNames']} actionName Action name to check.
 *
 * @return {string|undefined} Current error message.
 */
export const getDispatchError = (
	state: LicenseState,
	actionName: LicenseStore[ 'ActionNames' ]
): string | undefined => state?.dispatchStatus?.[ actionName ]?.error;
