import type { AppNotice, Popup, PopupsState } from './types';

export const STORE_NAME = 'popup-maker/popups';

export const ACTION_TYPES = {
	CREATE: 'CREATE',
	UPDATE: 'UPDATE',
	DELETE: 'DELETE',
	HYDRATE: 'HYDRATE',
	ADD_NOTICE: 'ADD_NOTICE',
	CLEAR_NOTICE: 'CLEAR_NOTICE',
	CLEAR_NOTICES: 'CLEAR_NOTICES',
	EDITOR_CHANGE_ID: 'EDITOR_CHANGE_ID',
	EDITOR_CLEAR_DATA: 'EDITOR_CLEAR_DATA',
	EDITOR_UPDATE_VALUES: 'EDITOR_UPDATE_VALUES',
	CHANGE_ACTION_STATUS: 'CHANGE_ACTION_STATUS',
	POPUPS_FETCH_ERROR: 'POPUPS_FETCH_ERROR',
};

export const initialState: PopupsState = {
	popups: [],
	editor: {},
	notices: [],
};

export const noticeDefaults: AppNotice = {
	id: '',
	message: '',
	type: 'info',
	isDismissible: true,
};

/**
 * Default values for a new popup.
 *
 * This should be kept in sync with the settings in the PHP code.
 *
 * @see /classes/Model/Popup.php
 * @see /includes/functions/install.php:get_default_popup_settings()
 */
export const popupDefaults: Popup = {
	id: 0,
	title: '',
	// content: '',
	description: '',
	status: 'draft',
	priority: 0,
	settings: {
		conditions: {
			logicalOperator: 'or',
			items: [],
		},
	},
};
