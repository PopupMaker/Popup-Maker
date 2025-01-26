import type { Popup } from './types';
import type { State } from './reducer';
import type { Updatable } from '@wordpress/core-data/src/entity-types';

export const STORE_NAME = 'popup-maker/popups';

const EDITOR_CHANGE_ID = 'EDITOR_CHANGE_ID';

export const ACTION_TYPES: {
	EDITOR_CHANGE_ID: typeof EDITOR_CHANGE_ID;
} = {
	EDITOR_CHANGE_ID,
};

/**
 * Initial state for the call to actions store.
 */
export const initialState: State = {
	editorId: undefined,
};

/**
 * Default values for a new popup.
 *
 * This should be kept in sync with the settings in the PHP code.
 *
 * @see /classes/Model/Popup.php
 * @see /includes/functions/install.php:get_default_popup_settings()
 */
export const defaultValues: Updatable< Popup< 'edit' > > = {
	id: 0,
	uuid: '',
	slug: '',
	title: '',
	content: '',
	excerpt: '',
	status: 'draft',
	settings: {
		conditions: {
			logicalOperator: 'or',
			items: [],
		},
	},
	// Required Post fields
	date: null,
	date_gmt: null,
	guid: '',
	link: '',
	modified: '',
	modified_gmt: '',
	type: 'pum_popup',
	author: 0,
	generated_slug: '',
	permalink_template: '',
	password: '',
	featured_media: 0,
	comment_status: 'open',
	ping_status: 'open',
	format: 'standard',
	meta: {},
	sticky: false,
	template: '',
	categories: [],
	tags: [],
};
