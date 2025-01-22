import { createReduxStore } from '@wordpress/data';

import reducer from './reducer';
import actions from './actions';
import selectors from './selectors';
import { STORE_NAME } from './constants';

import type { StoreState, StoreActions, StoreSelectors } from './types';

/**
 * Generate store config.
 */
export const storeConfig = () => ( {
	reducer,
	actions,
	selectors,
} );

/**
 * Store definition for the code data namespace.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
 */
const store = createReduxStore< StoreState, StoreActions, StoreSelectors >(
	STORE_NAME,
	storeConfig()
);

export default store;
