import { createReduxStore } from '@wordpress/data';
import type { Context } from '@wordpress/core-data';
import { STORE_NAME, defaultValues } from './constants';

import reducer from './reducer';
import * as customActions from './actions';
import * as customSelectors from './selectors';

import {
	createNoticeActions,
	createNoticeSelectors,
	createPostTypeActions,
	createPostTypeSelectors,
} from '../utils';

import type { CallToAction, State as CallToActionsState } from './types';

/**
 * Generate notice & entityactions.
 */
const entityActions =
	createPostTypeActions< CallToAction< Context > >( 'pum_cta' );
const noticeActions = createNoticeActions( 'pum-cta-editor' );

/**
 * Generate entity & notice selectors.
 */
const entitySelectors =
	createPostTypeSelectors< CallToAction< Context > >( 'pum_cta' );
const noticeSelectors = createNoticeSelectors( 'pum-cta-editor' );

/**
 * Generate store config.
 */
const storeConfig = () => ( {
	reducer,
	actions: {
		...entityActions,
		...noticeActions,
		...customActions,
	},
	selectors: {
		...entitySelectors,
		...noticeSelectors,
		...customSelectors,
	},
} );

/**
 * Store definition for the code data namespace.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
 */
const store = createReduxStore( STORE_NAME, storeConfig() );

export default store;

export type { CallToActionsState };

export * from './types';
export * from './validation';
export {
	STORE_NAME as CALL_TO_ACTIONS_STORE,
	defaultValues as defaultCtaValues,
	store as callToActionStore,
	/**
	 * @deprecated Use defaultCtaValues instead.
	 */
	defaultValues as callToActionDefaults,
};
