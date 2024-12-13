// import type { Query } from '@popup-maker/rule-engine';
import type { Statuses } from '../constants';
import type { OmitFirstArgs, RemoveReturnTypes } from '../types';

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

export type EditorId = 'new' | number | undefined;

/**
 * The settings for a popup.
 *
 * This should be kept in sync with the settings in the PHP code.
 *
 * @see /classes/Model/Popup.php
 * @see /includes/functions/install.php:get_default_popup_settings()
 */
export interface PopupSettings {
	conditions: Query;
	[ key: string ]: any;
}

export interface BasePopup {
	id: number;
	title: string | { raw: string; rendered: string };
	// content: string | { raw: string; rendered: string };
	description?: string;
	status: 'publish' | 'draft' | 'pending' | 'trash';
	priority: number;
	settings: PopupSettings;
	[ key: string ]: any;
}

export interface Popup extends BasePopup {
	// content: string;
}

export interface ApiPopup extends BasePopup {
	excerpt: string | { raw: string; rendered: string };
	// title: { raw: string; rendered: string };
	// content: { raw: string; rendered: string };
}

export type PopupStatuses = Popup[ 'status' ] | 'all' | string;

export type AppNotice = {
	id: string;
	message: string;
	type: 'success' | 'error' | 'warning' | 'info';
	isDismissible?: boolean;
	closeDelay?: number;
};

export type PopupsState = {
	popups: Popup[];
	editor: {
		id?: EditorId;
		values?: Popup;
	};
	notices: AppNotice[];
	// Boilerplate
	dispatchStatus?: {
		[ Property in PopupsStore[ 'ActionNames' ] ]?: {
			status: Statuses;
			error: string;
		};
	};
	error?: string;
};

export interface PopupsStore {
	StoreKey:
		| 'popup-maker/popups'
		| typeof import('../popups/index').POPUP_STORE
		| typeof import('../popups/index').popupsStore;
	State: PopupsState;
	Actions: RemoveReturnTypes< typeof import('../popups/actions') >;
	Selectors: OmitFirstArgs< typeof import('../popups/selectors') >;
	ActionNames: keyof PopupsStore[ 'Actions' ];
	SelectorNames: keyof PopupsStore[ 'Selectors' ];
}
