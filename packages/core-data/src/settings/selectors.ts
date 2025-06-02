import { createSelector } from '@wordpress/data';

import { DispatchStatus } from '../constants';
import { defaultValues } from './constants';

import type { State } from './reducer';
import type { Settings } from './types';

const settingsSelectors = {
	/**
	 * Get setting by name.
	 *
	 * @param {State} state The state.
	 *
	 * @return {Settings} The settings.
	 */
	getSettings: createSelector(
		( state: State ) => state.settings,
		( state: State ) => [ state.settings ]
	),

	/**
	 * Get setting by name.
	 *
	 * @param {State} state        The state.
	 * @param {K}     name         The setting name.
	 * @param {D}     defaultValue The default value.
	 *
	 * @return {Settings[K] | D} The setting value.
	 */
	getSetting: createSelector(
		<
			K extends keyof Settings,
			D extends Settings[ K ] | undefined | false,
		>(
			state: State,
			name: K,
			defaultValue: D
		) => {
			const settings = settingsSelectors.getSettings( state );

			return settings[ name ] ?? defaultValue;
		},
		<
			K extends keyof Settings,
			D extends Settings[ K ] | undefined | false,
		>(
			state: State,
			name: K,
			defaultValue: D
		) => [ state.settings, name, defaultValue ]
	),

	/**
	 * Gets object of unsaved settings changes.
	 *
	 * @param {State} state The state.
	 *
	 * @return {State[ 'unsavedChanges' ]} The unsaved changes.
	 */
	getUnsavedChanges: createSelector(
		( state: State ) => state.unsavedChanges,
		( state: State ) => [ state.unsavedChanges ]
	),

	/**
	 * Check if there are any unsaved changes.
	 */
	hasUnsavedChanges: createSelector(
		( state: State ): boolean => {
			return Object.keys( state?.unsavedChanges ?? {} ).length > 0;
		},
		( state: State ) => [ state.unsavedChanges ]
	),

	/**
	 * Get required cap/permission for given capability.
	 */
	getReqPermission: createSelector(
		< T extends keyof Settings[ 'permissions' ] >(
			state: State,
			/**
			 * Capability to check for.
			 */
			cap: T
		): string => {
			const permissions = settingsSelectors.getSetting(
				state,
				'permissions',
				defaultValues.permissions
			);

			const defaultPermission = 'manage_options';

			const permission = permissions[ cap ];

			return typeof permission === 'string'
				? permission
				: defaultPermission;
		},
		( state: State, cap: keyof Settings[ 'permissions' ] ) => [
			state.settings.permissions,
			cap,
		]
	),
};

/*****************************************************
 * SECTION: Resolution state selectors
 *****************************************************/
const resolutionSelectors = {
	/**
	 * Get resolution state for a specific entity.
	 */
	getResolutionState: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = state.resolutionState?.[ id ];

			// If no resolution state exists, return idle
			if ( ! resolutionState ) {
				return {
					status: DispatchStatus.Idle,
				};
			}

			return resolutionState;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if a resolution is idle.
	 */
	isIdle: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Idle;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity is currently being resolved.
	 */
	isResolving: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Resolving;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity resolution has completed successfully.
	 */
	hasResolved: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Success;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity resolution has failed.
	 */
	hasFailed: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Error;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Get the error for a failed resolution.
	 */
	getResolutionError: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.error;
		},
		( _state: State, id: number | string ) => [ id ]
	),
};

const selectors = {
	// Settings selectors
	...settingsSelectors,
	// Resolution state selectors
	...resolutionSelectors,
};

export default selectors;
