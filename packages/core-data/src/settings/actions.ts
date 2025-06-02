import { __ } from '@popup-maker/i18n';

import { DispatchStatus } from '../constants';
import { getErrorMessage, fetchFromApi } from '../utils';

import { ACTION_TYPES } from './constants';
import { apiPath } from './utils';

import type { ReducerAction } from './reducer';
import type { Settings, ThunkAction } from './types';

const {
	UPDATE,
	SAVE_CHANGES,
	STAGE_CHANGES,
	HYDRATE,
	CHANGE_ACTION_STATUS,
	INVALIDATE_RESOLUTION,
} = ACTION_TYPES;

/*****************************************************
 * SECTION: Settings actions
 *****************************************************/
const settingsActions = {
	/**
	 * Update settings.
	 *
	 * @param {Partial<Settings>} settings Settings to update.
	 *
	 * @return {ThunkAction} The action.
	 */
	updateSettings:
		(
			/**
			 * Settings to update.
			 */
			settings: Partial< Settings >
		): ThunkAction =>
		async ( { dispatch, resolveSelect } ) => {
			const actionName = 'updateSettings';

			try {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Resolving
				);

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
						payload: {
							settings: result,
						},
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
		},

	/**
	 * Save staged/unsaved changes.
	 *
	 * @param {Partial<Settings>} settings Settings to save.
	 *
	 * @return {ThunkAction} The action.
	 */
	saveSettings:
		(
			/**
			 * Settings to save.
			 */
			settings?: Partial< Settings >
		): ThunkAction =>
		async ( { dispatch, resolveSelect } ) => {
			const actionName = 'saveSettings';

			try {
				dispatch.changeActionStatus(
					actionName,
					DispatchStatus.Resolving
				);

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
						payload: {
							settings: result,
						},
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
		},

	/**
	 * Stage unsaved changes.
	 *
	 * @param {Partial<Settings>} settings Settings to stage.
	 *
	 * @return {ReducerAction} The action.
	 */
	stageUnsavedChanges: (
		/**
		 * Settings to stage.
		 */
		settings: Partial< Settings >
	): ReducerAction => {
		return {
			type: STAGE_CHANGES,
			payload: {
				settings,
			},
		} as ReducerAction;
	},

	/**
	 * Hydrate settings.
	 *
	 * @param {Settings} settings Settings to hydrate.
	 *
	 * @return {ReducerAction} The action.
	 */
	hydrate: (
		/**
		 * Settings to hydrate.
		 */
		settings: Settings
	): ReducerAction => {
		return {
			type: HYDRATE,
			payload: {
				settings,
			},
		} as ReducerAction;
	},
};

/*****************************************************
 * SECTION: Resolution actions
 *****************************************************/
const resolutionActions = {
	/**
	 * Change status of a dispatch action request.
	 *
	 * @param {CallToActionsStore[ 'ActionNames' ]} actionName Action name to change status of.
	 * @param {Statuses}                            status     New status.
	 * @param {string|undefined}                    message    Optional error message.
	 * @return {Object} Action object.
	 */
	changeActionStatus:
		(
			actionName: string,
			status: DispatchStatus,
			message?: string | { message: string; [ key: string ]: any }
		): ThunkAction =>
		( { dispatch } ) => {
			if ( message ) {
				// eslint-disable-next-line no-console
				console.log( actionName, message );
			}

			dispatch( {
				type: CHANGE_ACTION_STATUS,
				actionName,
				status,
				message,
			} );
		},

	/**
	 * Start resolution for an entity.
	 */
	// startResolution:
	// 	( id: number | string, operation: string = 'fetch' ) =>
	// 	( { dispatch } ) => {
	// 		console.log( 'startResolution', id, operation );
	// 		dispatch( {
	// 			type: START_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				operation,
	// 			},
	// 		} );
	// 	},

	/**
	 * Finish resolution for an entity.
	 */
	// finishResolution:
	// 	( id: number | string, operation: string = 'fetch' ) =>
	// 	( { dispatch } ) => {
	// 		dispatch( {
	// 			type: FINISH_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				operation,
	// 			},
	// 		} );
	// 	},

	/**
	 * Fail resolution for an entity.
	 */
	// failResolution:
	// 	(
	// 		id: number | string,
	// 		error: string,
	// 		operation: string = 'fetch',
	// 		extra?: Record< string, any >
	// 	): ThunkAction =>
	// 	( { dispatch } ) => {
	// 		dispatch( {
	// 			type: FAIL_RESOLUTION,
	// 			payload: {
	// 				id,
	// 				error,
	// 				operation,
	// 				extra,
	// 			},
	// 		} );
	// 	},

	/**
	 * Invalidate resolution for an entity.
	 *
	 * @param {number | string} id The entity ID.
	 * @return {Promise<void>}
	 */
	invalidateResolution:
		( id: number | string ): ThunkAction =>
		( { dispatch } ) => {
			dispatch( {
				type: INVALIDATE_RESOLUTION,
				payload: {
					id,
				},
			} );
		},
};

const actions = {
	// Settings actions
	...settingsActions,
	// Resolution state actions
	...resolutionActions,
};

export default actions;
