import type {
	ReduxStoreConfig,
	// StoreDescriptor,
} from '@wordpress/data/src/types';

import type {
	// ReduxStoreConfig,
	StoreDescriptor,
} from '@wordpress/data';

import type createNoticeActions from './notice-actions';
import type createNoticeSelectors from './notice-selectors';
import type { ThunkArgs } from '../types';

/**
 * Return type of createNoticeSelectors
 */
export type NoticeSelectors = ReturnType< typeof createNoticeSelectors >;

/**
 * Return type of createNoticeActions
 */
export type NoticeActions = ReturnType< typeof createNoticeActions >;

export type NoticeStoreConfig = ReduxStoreConfig<
	any,
	NoticeActions,
	NoticeSelectors
>;

export interface NoticesStore extends StoreDescriptor {
	select: ReturnType< typeof wpSelect< S > > & typeof wpSelect ;
	dispatch: NoticeActions;
}

export type NoticeStoreContext = ThunkArgs< NoticesStore >;

/**
 * Type guard to check if a store has notice functionality.
 * This checks if the store has both notice selectors and actions.
 */
export const storeHasNotices = (
	context: ThunkArgs
): context is NoticeStoreContext => {
	return (
		'select' in context &&
		'hasContextNotices' in context.select &&
		'getNoticeById' in context.select &&
		'dispatch' in context &&
		'createNotice' in context.dispatch
	);
};
