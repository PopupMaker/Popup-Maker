import { createReduxStore } from '@wordpress/data';

import reducer from './reducer';
import * as actions from './actions';
import * as resolvers from './resolvers';
import * as selectors from './selectors';
import { initialState, STORE_NAME } from './constants';

import type { StoreActions, StoreSelectors, StoreState } from './types';

const storeConfig = () => ( {
	initialState,
	reducer,
	actions,
	selectors,
	resolvers,
} );

/**
 * Store definition for the License data namespace.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
 */
const store = createReduxStore< StoreState, StoreActions, StoreSelectors >(
	STORE_NAME,
	storeConfig()
);

export default store;
