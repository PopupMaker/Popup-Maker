import type { CallToAction } from './types';
import type { State } from './reducer';
import type { Updatable } from '@wordpress/core-data/src/entity-types';

export const STORE_NAME = 'popup-maker/call-to-actions';

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
 * Default values for a new notice.
 */
export const noticeDefaults = {
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
export const defaultValues: Updatable< CallToAction< 'edit' > > = {
	id: 0,
	uuid: '',
	slug: '',
	title: '',
	content: '',
	excerpt: '',
	status: 'draft',
	settings: {
		type: 'link',
	},
	// Required Post fields
	date: null,
	date_gmt: null,
	guid: '',
	link: '',
	modified: '',
	modified_gmt: '',
	type: 'pum_cta',
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
