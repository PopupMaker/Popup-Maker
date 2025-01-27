import { __ } from '@popup-maker/i18n';

import { DispatchStatus, DispatchStatuses } from '../constants';
import { getErrorMessage, fetchFromApi } from '../utils';

import { ACTION_TYPES } from './constants';
import { apiPath } from './utils';

import type { ReducerAction } from './reducer';
import type { Settings, StoreActionNames, ThunkAction } from './types';

const { UPDATE, SAVE_CHANGES, STAGE_CHANGES, HYDRATE, CHANGE_ACTION_STATUS } =
	ACTION_TYPES;

/**
 * Change status of a dispatch action request.
 */
export const changeActionStatus = (
	actionName: StoreActionNames,
	status: DispatchStatuses,
	message?: string | undefined
) => {
	if ( message ) {
		// eslint-disable-next-line no-console
		console.log( actionName, message );
	}

	return {
		type: CHANGE_ACTION_STATUS,
		actionName,
		status,
		message,
	} as ReducerAction;
};

/**
 * Update settings.
 */
export const updateSettings =
	(
		/**
		 * Settings to update.
		 */
		settings: Partial< Settings >
	): ThunkAction =>
	async ( { dispatch, resolveSelect } ) => {
		const actionName = 'updateSettings';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const currentSettings = await resolveSelect.getSettings();

			const result = await fetchFromApi< Settings >( apiPath(), {
				method: 'PUT',
				data: {
					settings: { ...currentSettings, ...settings },
				},
			} );

			if ( result ) {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				dispatch( {
					type: UPDATE,
					settings: result,
				} as ReducerAction );

				return;
			}

			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, settings were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Save staged/unsaved changes.
 */
export const saveSettings =
	(
		/**
		 * Settings to save.
		 */
		settings?: Partial< Settings >
	): ThunkAction =>
	async ( { dispatch, resolveSelect } ) => {
		const actionName = 'saveSettings';

		try {
			dispatch.changeActionStatus( actionName, DispatchStatus.Resolving );

			const currentSettings = await resolveSelect.getSettings();

			const unsavedChanges = await resolveSelect.getUnsavedChanges();

			const result = await fetchFromApi< Settings >( apiPath(), {
				method: 'PUT',
				data: {
					settings: {
						...currentSettings,
						...unsavedChanges,
						...settings,
					},
				},
			} );

			if ( result ) {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Success
				);

				dispatch( {
					type: SAVE_CHANGES,
					settings: result,
				} as ReducerAction );

				return;
			}

			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				__(
					'An error occurred, settings were not saved.',
					'popup-maker'
				)
			);
		} catch ( error ) {
			dispatch.changeActionStatus(
				actionName,
				DispatchStatus.Error,
				getErrorMessage( error )
			);
		}
	};

/**
 * Stage unsaved changes.
 */
export const stageUnsavedChanges = (
	/**
	 * Settings to stage.
	 */
	settings: Partial< Settings >
): ReducerAction => {
	return {
		type: STAGE_CHANGES,
		settings,
	} as ReducerAction;
};

/**
 * Hydrate settings.
 */
export const hydrate = (
	/**
	 * Settings to hydrate.
	 */
	settings: Settings
): ReducerAction => {
	return {
		type: HYDRATE,
		settings,
	} as ReducerAction;
};
