import type { Statuses } from '../constants';

import { STORE_NAME } from './constants';
import type {
	OmitFirstArgs,
	RemoveReturnTypes,
	EditorId,
	AppNotice,
} from '../types';

export interface CallToActionTypes {
	link: 'link';
}

/**
 * The settings for a popup.
 *
 * This should be kept in sync with the settings in the PHP code.
 *
 * @see /classes/Models/CallToAction.php
 * @see /includes/namespaced//default-values.php
 */
export interface CallToActionBaseSettings {
	type?: keyof CallToActionTypes;
	[ key: string ]: any;
}

export interface CallToActionLinkSettings extends CallToActionBaseSettings {
	type: 'link';
	url?: string;
}

export type CallToActionSettings =
	| CallToActionLinkSettings
	| CallToActionBaseSettings;

export interface ApiFormat {
	raw: string;
	rendered: string;
}

export interface BaseCallToAction {
	id: number;
	slug: string;
	title: string;
	description?: string;
	status: 'publish' | 'draft' | 'pending' | 'trash';
	settings: CallToActionSettings;
}

export interface CallToAction extends BaseCallToAction {}

export interface ApiCallToAction {
	id: number;
	title: string | ApiFormat;
	excerpt: string | ApiFormat;
	status: 'publish' | 'draft' | 'pending' | 'trash';
	settings: CallToActionSettings;
}

export const isApiFormat = ( value: unknown ): value is ApiFormat => {
	return (
		typeof value === 'object' &&
		value !== null &&
		'raw' in value &&
		'rendered' in value
	);
};

export type CallToActionStatuses = CallToAction[ 'status' ] | 'all' | string;

export type CallToActionsState = {
	callToActions: CallToAction[];
	editor: {
		id?: EditorId;
		values?: CallToAction;
	};
	notices: AppNotice[];
	// Boilerplate
	dispatchStatus?: {
		[ Property in CallToActionsStore[ 'ActionNames' ] ]?: {
			status: Statuses;
			error: string;
		};
	};
	error?: string;
};

export interface CallToActionsStore {
	StoreKey:
		| 'popup-maker/call-to-actions'
		| typeof STORE_NAME
		| typeof import('./index').callToActionStore;
	State: CallToActionsState;
	Actions: RemoveReturnTypes< typeof import('./actions') >;
	Selectors: OmitFirstArgs< typeof import('./selectors') >;
	ActionNames: keyof CallToActionsStore[ 'Actions' ];
	SelectorNames: keyof CallToActionsStore[ 'Selectors' ];
}
