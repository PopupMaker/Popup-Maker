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
 */
export const getLicenseData = ( state: State ): License => state.license;

/**
 * Get license key.
 */
export const getLicenseKey = ( state: State ): LicenseKey => {
	const { key } = getLicenseData( state );
	return key;
};

/**
 * Get license status.
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
 */
export const getConnectInfo = ( state: State ): LicenseConnect | undefined =>
	state.connectInfo;

/**
 * Get current status for dispatched action.
 */
export const getDispatchStatus = (
	state: State,
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
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
 */
export const getDispatchError = (
	state: State,
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.error;
