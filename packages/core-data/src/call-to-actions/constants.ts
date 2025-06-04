import type { State } from './reducer';
import type { EditableCta } from './types';

export const STORE_NAME = 'popup-maker/call-to-actions';
export const NOTICE_CONTEXT = 'pum-cta-editor';

// Entity actions.
export const RECEIVE_RECORD = 'RECEIVE_RECORD';
export const RECEIVE_RECORDS = 'RECEIVE_RECORDS';
export const RECEIVE_QUERY_RECORDS = 'RECEIVE_QUERY_RECORDS';
export const RECEIVE_ERROR = 'RECEIVE_ERROR';
export const PURGE_RECORD = 'PURGE_RECORD';
export const PURGE_RECORDS = 'PURGE_RECORDS';

// Editor actions.
export const EDITOR_CHANGE_ID = 'EDITOR_CHANGE_ID';
export const EDIT_RECORD = 'EDIT_RECORD';
export const START_EDITING_RECORD = 'START_EDITING_RECORD';
export const SAVE_EDITED_RECORD = 'SAVE_EDITED_RECORD';
export const UNDO_EDIT_RECORD = 'UNDO_EDIT_RECORD';
export const REDO_EDIT_RECORD = 'REDO_EDIT_RECORD';
export const RESET_EDIT_RECORD = 'RESET_EDIT_RECORD';

// Resolution actions.
export const CHANGE_ACTION_STATUS = 'CHANGE_ACTION_STATUS';
// Resolution actions.
export const START_RESOLUTION = 'START_RESOLUTION';
export const FINISH_RESOLUTION = 'FINISH_RESOLUTION';
export const FAIL_RESOLUTION = 'FAIL_RESOLUTION';
export const INVALIDATE_RESOLUTION = 'INVALIDATE_RESOLUTION';

export const ACTION_TYPES: {
	// Entity actions
	RECEIVE_RECORD: typeof RECEIVE_RECORD;
	RECEIVE_RECORDS: typeof RECEIVE_RECORDS;
	RECEIVE_QUERY_RECORDS: typeof RECEIVE_QUERY_RECORDS;
	RECEIVE_ERROR: typeof RECEIVE_ERROR;
	PURGE_RECORD: typeof PURGE_RECORD;
	PURGE_RECORDS: typeof PURGE_RECORDS;

	// Editor actions
	EDITOR_CHANGE_ID: typeof EDITOR_CHANGE_ID;
	EDIT_RECORD: typeof EDIT_RECORD;
	START_EDITING_RECORD: typeof START_EDITING_RECORD;
	SAVE_EDITED_RECORD: typeof SAVE_EDITED_RECORD;
	UNDO_EDIT_RECORD: typeof UNDO_EDIT_RECORD;
	REDO_EDIT_RECORD: typeof REDO_EDIT_RECORD;
	RESET_EDIT_RECORD: typeof RESET_EDIT_RECORD;
	// Resolution actions
	CHANGE_ACTION_STATUS: typeof CHANGE_ACTION_STATUS;
	START_RESOLUTION: typeof START_RESOLUTION;
	FINISH_RESOLUTION: typeof FINISH_RESOLUTION;
	FAIL_RESOLUTION: typeof FAIL_RESOLUTION;
	INVALIDATE_RESOLUTION: typeof INVALIDATE_RESOLUTION;
} = {
	RECEIVE_RECORD,
	RECEIVE_RECORDS,
	RECEIVE_QUERY_RECORDS,
	RECEIVE_ERROR,
	PURGE_RECORD,
	PURGE_RECORDS,
	EDITOR_CHANGE_ID,
	EDIT_RECORD,
	START_EDITING_RECORD,
	SAVE_EDITED_RECORD,
	UNDO_EDIT_RECORD,
	REDO_EDIT_RECORD,
	RESET_EDIT_RECORD,
	CHANGE_ACTION_STATUS,
	START_RESOLUTION,
	FINISH_RESOLUTION,
	FAIL_RESOLUTION,
	INVALIDATE_RESOLUTION,
};

/**
 * Initial state for the call to actions store.
 */
export const initialState: State = {
	byId: {},
	allIds: [],
	queries: {},
	editorId: undefined,
	editedEntities: {},
	editHistory: {},
	editHistoryIndex: {},
	resolutionState: {},
	notices: {},
	errors: {
		global: null,
		byId: {},
	},
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
export const defaultValues: EditableCta = {
	id: 0,
	uuid: '',
	slug: '',
	title: '',
	content: '',
	excerpt: '',
	status: 'draft',
	settings: {
		type: 'link',
		url: '',
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
