import type {
	Context,
	BaseEntityRecords,
	Updatable,
} from '@wordpress/core-data';

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
export interface Popup< C extends Context = Context >
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

/**
 * The statuses for a your custom type.
 */
export type PopupStatuses = Popup[ 'status' ] | 'all';

/**
 * The types for a popup.
 */
export interface PopupTypes {
	link: 'link';
}

/**
 * The settings for a link popup.
 */
export interface PopupLinkSettings extends PopupBaseSettings {
	type: 'link';
	url?: string;
}

/**
 * The settings for a popup.
 *
 * TODO refactor this to not specify each, but allow for any extender of basesettings.
 */
export type PopupSettings = PopupLinkSettings | PopupBaseSettings;

/**
 * The editable popup with an ID.
 */
export type EditablePopup = Updatable< Popup< 'edit' > >;

/**
 * The partial editable popup with a required ID.
 */
export type PartialEditablePopup = Partial< EditablePopup > & { id: number };

/**
 * The edits for a popup without an ID.
 *
 * Used for edits that are not yet saved.
 */
export type PopupEdit = Omit< PartialEditablePopup, 'id' >;


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
