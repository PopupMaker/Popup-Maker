import { applyFilters } from '@wordpress/hooks';

import type { Settings, SettingsState } from './types';

export const STORE_NAME = 'popup-paker/settings';

export const ACTION_TYPES = {
	UPDATE: 'UPDATE',
	STAGE_CHANGES: 'STAGE_CHANGES',
	SAVE_CHANGES: 'SAVE_CHANGES',
	HYDRATE: 'HYDRATE',
	CHANGE_ACTION_STATUS: 'CHANGE_ACTION_STATUS',
	SETTINGS_FETCH_ERROR: 'SETTINGS_FETCH_ERROR',
};

/**
 * Default settings.
 *
 * NOTE: These should match the defaults in PHP.
 * Update get_default_settings function.
 */
export const settingsDefaults: Settings =
	/**
	 * Filter the default settings.
	 *
	 * @param {Settings} settings Default settings.
	 *
	 * @return {Settings} Default settings.
	 */
	applyFilters( 'popupMaker.defaultSettings', {
		permissions: {
			// Block Controls
			view_block_controls: 'edit_posts',
			edit_block_controls: 'edit_posts',
			// Restrictions
			edit_restrictions: 'manage_options',
			// Settings
			manage_settings: 'manage_options',
		},
	} ) as Settings;

const { currentSettings = settingsDefaults } = popupMakerCoreData;

export const initialState: SettingsState = {
	settings: currentSettings,
	unsavedChanges: {},
};
