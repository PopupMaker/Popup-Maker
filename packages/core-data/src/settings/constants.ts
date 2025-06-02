import { applyFilters } from '@wordpress/hooks';

import type { State } from './reducer';
import type { Settings } from './types';

export const STORE_NAME = 'popup-maker/settings';

export const UPDATE = 'UPDATE';
export const STAGE_CHANGES = 'STAGE_CHANGES';
export const SAVE_CHANGES = 'SAVE_CHANGES';
export const HYDRATE = 'HYDRATE';
export const CHANGE_ACTION_STATUS = 'CHANGE_ACTION_STATUS';
export const INVALIDATE_RESOLUTION = 'INVALIDATE_RESOLUTION';
export const SETTINGS_FETCH_ERROR = 'SETTINGS_FETCH_ERROR';

export const ACTION_TYPES: {
	UPDATE: typeof UPDATE;
	STAGE_CHANGES: typeof STAGE_CHANGES;
	SAVE_CHANGES: typeof SAVE_CHANGES;
	HYDRATE: typeof HYDRATE;
	CHANGE_ACTION_STATUS: typeof CHANGE_ACTION_STATUS;
	INVALIDATE_RESOLUTION: typeof INVALIDATE_RESOLUTION;
	SETTINGS_FETCH_ERROR: typeof SETTINGS_FETCH_ERROR;
} = {
	UPDATE,
	STAGE_CHANGES,
	SAVE_CHANGES,
	HYDRATE,
	CHANGE_ACTION_STATUS,
	INVALIDATE_RESOLUTION,
	SETTINGS_FETCH_ERROR,
};

/**
 * Default settings.
 *
 * NOTE: These should match the defaults in PHP.
 * Update get_default_settings function.
 */
export const defaultValues: Settings =
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

/**
 * Prefill settings from window global varis if set.
 */
const { currentSettings = defaultValues } = popupMakerCoreData ?? {};

export const initialState: State = {
	settings: currentSettings,
	unsavedChanges: {},
	resolutionState: {},
};
