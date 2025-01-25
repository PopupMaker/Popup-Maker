import type {
	ReduxStoreConfig,
	StoreDescriptor as StoreDescriptorType,
} from '@wordpress/data/src/types';

import type { StoreThunkContext } from '../types';
import type createNoticeActions from './notice-actions';
import type createNoticeSelectors from './notice-selectors';

/**
 * Notice object from WordPress core.
 */
export interface WPNotice {
	/**
	 * Unique identifier of notice.
	 */
	id: string;
	/**
	 * Status of notice, one of `success`, `info`, `error`, or `warning`.
	 * Defaults to `info`.
	 */
	status?: 'warning' | 'success' | 'error' | 'info' | string;
	/**
	 * Notice message.
	 */
	content?: string;
	/**
	 * Audibly announced message text used by assistive technologies.
	 */
	spokenMessage?: string;
	/**
	 * Notice message as raw HTML. Intended to serve primarily for compatibility of
	 * server-rendered notices, and SHOULD NOT be used for notices. It is subject to
	 * removal without notice.
	 */
	__unstableHTML?: string;
	/**
	 * Whether the notice can be dismissed by user.
	 * Defaults to `true`.
	 */
	isDismissible?: boolean;
	/**
	 * Type of notice, typically one of `default` or `snackbar`.
	 * Defaults to `default`.
	 */
	type?: string;
	/**
	 * Whether the notice content should be announced to screen readers.
	 * Defaults to `true`.
	 */
	speak?: boolean;
	/**
	 * User actions to present with notice.
	 */
	actions?: NoticeAction[];
}

/**
 * Notice object from WordPress core.
 */
export interface Notice extends WPNotice {
	/**
	 * Notice context. Unused?.
	 */
	context?: string | undefined;
	/**
	 * Notice icon.
	 */
	icon?: string | undefined;
	/**
	 * Notice explicit dismiss.
	 */
	explicitDismiss?: boolean | undefined;
	/**
	 * Notice on dismiss.
	 */
	onDismiss?: Function | undefined;
	/**
	 * Notice close delay.
	 */
	closeDelay?: number | undefined;
}

/**
 * WP notice action.
 */
export type NoticeAction = {
	/**
	 * Notice action label.
	 */
	label: string;
	/**
	 * Notice action url.
	 */
	url: string | null;
	/**
	 * Notice action onClick.
	 */
	onClick: Function | null;
};

export type StoreSelectors = ReturnType< typeof createNoticeSelectors >;

export type StoreActions = ReturnType< typeof createNoticeActions >;

export type StoreConfig = ReduxStoreConfig< any, StoreActions, StoreSelectors >;

export interface StoreDescriptor extends StoreDescriptorType< StoreConfig > {}

/**
 * Define the ThunkArgs / ThunkAction shape.
 */
export type ThunkContext = StoreThunkContext< StoreDescriptor >;

/**
 * Define the ThunkAction shape.
 */
export type ThunkAction< R = void > = (
	context: ThunkContext
) => Promise< R > | R;

/**
 * Type guard to check if a store has notice functionality.
 * This checks if the store has both notice selectors and actions.
 */
export const storeHasNotices = (
	context: StoreThunkContext< any >
): context is ThunkContext => {
	return (
		'select' in context &&
		'hasContextNotices' in context.select &&
		'getNoticeById' in context.select &&
		'dispatch' in context &&
		'createNotice' in context.dispatch
	);
};
