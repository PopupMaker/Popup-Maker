import { DispatchStatus } from '../constants';
import { defaultValues } from './constants';
import { createSelector } from '@wordpress/data';

import type { State } from './reducer';
import type { Settings, StoreActionNames } from './types';

/**
 * Get setting by name.
 *
 * @param {State} state The state.
 *
 * @return {Settings} The settings.
 */
export const getSettings = ( state: State ): Settings => state.settings;

/**
 * Get setting by name.
 *
 * @param {State} state        The state.
 * @param {K}     name         The setting name.
 * @param {D}     defaultValue The default value.
 *
 * @return {Settings[K] | D} The setting value.
 */
export const getSetting = <
	K extends keyof Settings,
	D extends Settings[ K ] | undefined | false,
>(
	state: State,
	/**
	 * Setting name.
	 */
	name: K,
	/**
	 * Default value if not already set.
	 */
	defaultValue: D
): Settings[ K ] | D => {
	const settings = getSettings( state );

	return settings[ name ] ?? defaultValue;
};

/**
 * Gets object of unsaved settings changes.
 *
 * @param {State} state The state.
 *
 * @return {State[ 'unsavedChanges' ]} The unsaved changes.
 */
export const getUnsavedChanges = (
	state: State
): State[ 'unsavedChanges' ] => {
	return state?.unsavedChanges ?? {};
};

/**
 * Check if there are any unsaved changes.
 */
export const hasUnsavedChanges = createSelector(
	( state: State ): boolean => {
		return Object.keys( state?.unsavedChanges ?? {} ).length > 0;
	},
	( state: State ) => [ state.unsavedChanges ]
);

/**
 * Get required cap/permission for given capability.
 */
export const getReqPermission = createSelector(
	< T extends keyof Settings[ 'permissions' ] >(
		state: State,
		/**
		 * Capability to check for.
		 */
		cap: T
	): string => {
		const permissions = getSetting(
			state,
			'permissions',
			defaultValues.permissions
		);

		const defaultPermission = 'manage_options';

		const permission = permissions[ cap ];

		return typeof permission === 'string' ? permission : defaultPermission;
	},
	( state: State, cap: keyof Settings[ 'permissions' ] ) => [
		state.settings.permissions,
		cap,
	]
);

/**
 * Get current status for dispatched action.
 *
 * @param {State}            state      The state.
 * @param {StoreActionNames} actionName The action name.
 *
 * @return {string | undefined} The status.
 */
export const getDispatchStatus = (
	state: State,
	/**
	 * Action name to check.
	 */
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.status;

/**
 * Check if action is dispatching.
 */
export const isDispatching = createSelector(
	(
		state: State,
		/**
		 * Action name or array of names to check.
		 */
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
 * @param {State}            state      The state.
 * @param {StoreActionNames} actionName The action name.
 *
 * @return {boolean} Whether the action has finished dispatching.
 */
export const hasDispatched = (
	state: State,
	/**
	 * Action name to check.
	 */
	actionName: StoreActionNames
): boolean => {
	const status = getDispatchStatus( state, actionName );

	return !! (
		status &&
		(
			[ DispatchStatus.Success, DispatchStatus.Error ] as string[]
		 ).indexOf( status ) >= 0
	);
};

/**
 * Get dispatch action error if esists.
 *
 * @param {State}                        state      Current state.
 * @param {SettingsStore['ActionNames']} actionName Action name to check.
 *
 * @return {string|undefined} Current error message.
 */
export const getDispatchError = (
	state: State,
	/**
	 * Action name to check.
	 */
	actionName: StoreActionNames
): string | undefined => state?.dispatchStatus?.[ actionName ]?.error;
