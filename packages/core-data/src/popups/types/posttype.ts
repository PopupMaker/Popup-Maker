import type { Context, BaseEntityRecords } from '@wordpress/core-data';

/**
 * Declare a custom post type for custom post type.
 */
declare module '@wordpress/core-data' {
	export namespace BaseEntityRecords {
		export interface Popup< C extends Context > extends BaseEntity< C > {
			/**
			 * Custom uuid field.
			 */
			uuid: string;
			/**
			 * Custom settings field.
			 */
			settings: PopupSettings;
		}
	}
}

/**
 * The popup interface.
 */
export interface Popup< C extends Context = 'view' >
	extends BaseEntityRecords.Popup< C > {}

/**
 * The settings for a popup.
 *
 * This should be kept in sync with the settings in the PHP code.
 *
 * @see /classes/Models/Popup.php
 * @see /includes/namespaced//default-values.php
 */
export interface PopupBaseSettings {
	conditions: Query;
	[ key: string ]: any;
}

export type PopupSettings = PopupBaseSettings;

/**
 * The statuses for a your custom type.
 */
export type PopupStatuses = Popup[ 'status' ] | 'all';

/* temporary to prevent cyclical dependencies. */
interface BaseItem {
	id: string;
	type: string;
	// These are for React SortableJS.
	selected?: boolean;
	chosen?: boolean;
	filtered?: boolean;
}

interface Query {
	logicalOperator: 'and' | 'or';
	items: Item[];
}

interface RuleItem extends BaseItem {
	type: 'rule';
	name: string;
	options?: {
		[ key: string ]: any;
	};
	notOperand?: boolean;
}

interface GroupItem extends BaseItem {
	type: 'group';
	label: string;
	query: Query;
}

type Item = RuleItem | GroupItem;
/* end temporary */
