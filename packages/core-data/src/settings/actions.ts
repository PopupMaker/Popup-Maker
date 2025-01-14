import { select } from '@wordpress/data-controls';
import { __ } from '@wordpress/i18n';

import { Status } from '../constants';
import { fetch } from '../controls';
import { getErrorMessage } from '../utils';
import { ACTION_TYPES, STORE_NAME } from './constants';
import { getResourcePath } from './utils';

import type { Statuses } from '../constants';
import type { Settings, SettingsStore } from './types';

const { UPDATE, SAVE_CHANGES, STAGE_CHANGES, HYDRATE, CHANGE_ACTION_STATUS } =
	ACTION_TYPES;

/**
 * Change status of a dispatch action request.
 *
 * @param {SettingsStore[ 'ActionNames' ]} actionName Action name to change status of.
 * @param {Statuses}                       status     New status.
 * @param {string | undefined}             message    Optional error message.
 * @return {Object} Action object.
 */
export const changeActionStatus = (
	actionName: SettingsStore[ 'ActionNames' ],
	status: Statuses,
	message?: string | undefined
): {
	type: string;
	actionName: SettingsStore[ 'ActionNames' ];
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
 * Update settings.
 *
 * @param {Partial< Settings >} settings Object of settings to update.
 * @return {Generator} Action object.
 */
export function* updateSettings( settings: Partial< Settings > ): Generator {
	const actionName = 'updateSettings';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const currentSettings = yield* select( STORE_NAME, 'getSettings' );

		const result = ( yield fetch( getResourcePath(), {
			method: 'PUT',
			body: { settings: { ...currentSettings, ...settings } },
		} ) ) as Settings;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return {
				type: UPDATE,
				settings: result,
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, settings were not saved.', 'popup-maker' )
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
 * Save staged/unsaved changes.
 *
 * @return {Generator} Action object.
 */
export function* saveSettings() {
	const actionName = 'saveSettings';

	try {
		yield changeActionStatus( actionName, Status.Resolving );

		// execution will pause here until the `FETCH` control function's return
		// value has resolved.
		const currentSettings = yield* select( STORE_NAME, 'getSettings' );

		const unsavedChanges = yield* select( STORE_NAME, 'getUnsavedChanges' );

		const result = ( yield fetch( getResourcePath(), {
			method: 'PUT',
			body: { settings: { ...currentSettings, ...unsavedChanges } },
		} ) ) as Settings;

		if ( result ) {
			// thing was successfully updated so return the action object that will
			// update the saved thing in the state.
			yield changeActionStatus( actionName, Status.Success );

			return {
				type: SAVE_CHANGES,
				settings: result,
			};
		}

		// if execution arrives here, then thing didn't update in the state so return
		// action object that will add an error to the state about this.
		// returning an action object that will save the update error to the state.
		return changeActionStatus(
			actionName,
			Status.Error,
			__( 'An error occurred, settings were not saved.', 'popup-maker' )
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
 * Update settings.
 *
 * @param {Partial< Settings >} settings Object of settings to update.
 * @return {Generator} Action object.
 */
export const stageUnsavedChanges = (
	settings: Partial< Settings >
): { type: string; settings: Partial< Settings > } => {
	return {
		type: STAGE_CHANGES,
		settings,
	};
};

/**
 *
 * @param {Settings} settings
 * @return {Object} Action object.
 */
export const hydrate = (
	settings: Settings
): { type: string; settings: Settings } => {
	return {
		type: HYDRATE,
		settings,
	};
};
