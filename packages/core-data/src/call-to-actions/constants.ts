import type { AppNotice } from '../types';
import type { CallToAction, CallToActionsState } from './types';

export const STORE_NAME = 'popup-maker/call-to-actions';

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
	CALL_TO_ACTIONS_FETCH_ERROR: 'CALL_TO_ACTIONS_FETCH_ERROR',
};

export const initialState: CallToActionsState = {
	callToActions: [],
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
 * Default values for a new call to action.
 *
 * This should be kept in sync with the settings in the PHP code.
 *
 * @see /classes/Models/CallToAction.php
 * @see /includes/namespaced/default-values.php
 */
export const callToActionDefaults: CallToAction = {
	id: 0,
	uuid: '',
	title: '',
	slug: '',
	// content: '',
	description: '',
	status: 'draft',
	settings: {
		type: 'link',
	},
};
