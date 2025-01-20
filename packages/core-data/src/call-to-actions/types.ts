import type {
	Context,
	BaseEntityRecords as WPBaseEntityRecords,
} from '@wordpress/core-data';

import type { EditorId } from '../types';

declare module '@wordpress/core-data' {
	export namespace BaseEntityRecords {
		export interface CallToAction< C extends Context >
			extends BaseEntity< C > {
			uuid: string;
			settings: CallToActionSettings;
		}
	}
}

/**
 * The call to action interface.
 */
export interface CallToAction< C extends Context = 'view' >
	extends WPBaseEntityRecords.CallToAction< C > {}

/**
 * The settings for a call to action.
 *
 * This should be kept in sync with the settings in the PHP code.
 *
 * @see /classes/Models/CallToAction.php
 * @see /includes/namespaced//default-values.php
 */
export interface CallToActionBaseSettings {
	type: keyof CallToActionTypes | '';
	[ key: string ]: any;
}

/**
 * The types for a call to action.
 */
export interface CallToActionTypes {
	link: 'link';
}

/**
 * The settings for a link call to action.
 */
export interface CallToActionLinkSettings extends CallToActionBaseSettings {
	type: 'link';
	url?: string;
}

/**
 * The settings for a call to action.
 *
 * TODO refactor this to not specify each, but allow for any extender of basesettings.
 */
export type CallToActionSettings =
	| CallToActionLinkSettings
	| CallToActionBaseSettings;

/**
 * The shape of a call to action from the API.
 */
export interface ApiCallToAction extends CallToAction {
	uuid: string;
	settings: CallToActionSettings;
}

/**
 * The statuses for a call to action.
 */
export type CallToActionStatuses = CallToAction[ 'status' ] | 'all' | string;

/**
 * The shape of the state for the call to actions store.
 */
export type State = {
	editorId?: EditorId;
};
