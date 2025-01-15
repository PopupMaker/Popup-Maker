import { createReduxStore } from '@wordpress/data';
import { controls as wpControls } from '@wordpress/data-controls';

import localControls from '../controls';
import * as actions from './actions';
import { initialState, settingsDefaults, STORE_NAME } from './constants';
import reducer from './reducer';
import * as resolvers from './resolvers';
import * as selectors from './selectors';

const storeConfig = () => ( {
	initialState,
	selectors,
	actions,
	reducer,
	resolvers,
	controls: { ...wpControls, ...localControls },
} );

const store = createReduxStore( STORE_NAME, storeConfig() );

export * from './types';
export { STORE_NAME as SETTINGS_STORE };
export { store as settingsStore };
export { settingsDefaults };
