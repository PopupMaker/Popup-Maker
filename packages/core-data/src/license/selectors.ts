import { createSelector } from '@wordpress/data';

import { DispatchStatus } from '../constants';
import { licenseStatusDefaults } from './constants';

import type { State } from './reducer';
import type {
	License,
	LicenseKey,
	LicenseStatus,
	LicenseConnect,
	StoreActionNames,
} from './types';

/**
 * Get license
 *
 * @param {State} state State.
 *
 * @return {License} License.
 */
export const getLicenseData = ( state: State ): License => state.license;

/**
 * Get license key.
 *
 * @param {State} state State.
 *
 * @return {LicenseKey} License key.
 */
export const getLicenseKey = ( state: State ): LicenseKey => {
	const { key } = getLicenseData( state );
	return key;
};

/**
 * Get license status.
 *
 * @param {State} state State.
 *
 * @return {LicenseStatus} License status.
 */
export const getLicenseStatus = createSelector(
	( state: State ): LicenseStatus => {
		const { status } = getLicenseData( state );

		return {
			...licenseStatusDefaults,
			...status,
		};
	},
	( state: State ) => [ state.license.status ]
);

/**
 * Get connect info for pro upgrade.
 *
 * @param {State} state State.
 *
 * @return {LicenseConnect | undefined} Connect info.
 */
export const getConnectInfo = ( state: State ): LicenseConnect | undefined =>
	state.connectInfo;

/**
 * Get current status for dispatched action.
 *
 * @param {State}            state      State.
 * @param {StoreActionNames} actionName Action name.
 *
 * @return {string | undefined} Status.
 */
export const getDispatchStatus = (
	state: State,
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
 *
 * @param {State}                                 state       State.
 * @param {StoreActionNames | StoreActionNames[]} actionNames Action name or array of action names.
 *
 * @return {boolean} True if action is dispatching, false otherwise.
 */
export const isDispatching = createSelector(
	(
		state: State,
		actionNames: StoreActionNames | StoreActionNames[]
	): boolean => {
		if ( ! Array.isArray( actionNames ) ) {
			return (
				getDispatchStatus( state, actionNames ) ===
				DispatchStatus.Resolving
			);
		}

		let dispatching = false;

		for ( let i = 0; actionNames.length > i; i++ ) {
			dispatching =
				getDispatchStatus( state, actionNames[ i ] ) ===
				DispatchStatus.Resolving;

			if ( dispatching ) {
				return true;
			}
		}

		return dispatching;
	},
	( state: State, actionNames: StoreActionNames | StoreActionNames[] ) => [
		state.dispatchStatus,
		actionNames,
	]
);

/**
 * Check if action has finished dispatching.
 *
 * @param {State}            state      State.
 * @param {StoreActionNames} actionName Action name.
 *
 * @return {boolean} True if action has finished dispatching, false otherwise.
 */
export const hasDispatched = createSelector(
	( state: State, actionName: StoreActionNames ): boolean => {
		const status = getDispatchStatus( state, actionName );

		return !! (
			status &&
			(
				[ DispatchStatus.Success, DispatchStatus.Error ] as string[]
			 ).indexOf( status ) >= 0
		);
	},
	( state: State, actionName: StoreActionNames ) => [
		state.dispatchStatus,
		actionName,
	]
);

/**
 * Get dispatch action error if esists.
 *
 * @param {State}            state      State.
 * @param {StoreActionNames} actionName Action name.
 *
 * @return {string | undefined} Error.
 */
export const getDispatchError = (
	state: State,
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.error;
