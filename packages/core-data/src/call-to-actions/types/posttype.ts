import type { Context, BaseEntityRecords } from '@wordpress/core-data';

/**
 * Declare a custom post type for custom post type.
 */
declare module '@wordpress/core-data' {
	export namespace BaseEntityRecords {
		export interface CallToAction< C extends Context >
			extends BaseEntity< C > {
			/**
			 * Custom uuid field.
			 */
			uuid: string;
			/**
			 * Custom settings field.
			 */
			settings: CallToActionSettings;
		}
	}
}

/**
 * The call to action interface.
 */
export interface CallToAction< C extends Context = Context >
	extends BaseEntityRecords.CallToAction< C > {}

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
 * The statuses for a your custom type.
 */
export type CallToActionStatuses = CallToAction[ 'status' ] | 'all';

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
