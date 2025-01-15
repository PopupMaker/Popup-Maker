import { createReduxStore } from '@wordpress/data';
import { controls as wpControls } from '@wordpress/data-controls';

import sharedControls from '../controls';
import * as actions from './actions';
import { initialState, STORE_NAME } from './constants';
import localControls from './controls';
import reducer from './reducer';
import * as selectors from './selectors';

const storeConfig = () => ( {
	initialState,
	selectors,
	actions,
	reducer,
	controls: { ...wpControls, ...sharedControls, ...localControls },
} );

const store = createReduxStore( STORE_NAME, storeConfig() );

export * from './types';
export { STORE_NAME as URL_SEARCH_STORE };
export { store as urlSearchStore };
