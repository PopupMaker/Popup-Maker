import type {
	ReduxStoreConfig,
	StoreDescriptor as StoreDescriptorType,
} from '@wordpress/data/src/types';

import type createNoticeActions from './notice-actions';
import type createNoticeSelectors from './notice-selectors';
import type { StoreThunkContext } from '../types';

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
