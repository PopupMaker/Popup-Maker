import { createReduxStore } from '@wordpress/data';
import { controls as wpControls } from '@wordpress/data-controls';

import sharedControls from '../controls';
import * as actions from './actions';
import { initialState, STORE_NAME } from './constants';
import localControls from './controls';
import reducer from './reducer';
import * as selectors from './selectors';
import type { URLSearchStore } from './types';

const storeConfig = () => ( {
	initialState,
	selectors,
	actions,
	reducer,
	controls: { ...wpControls, ...sharedControls, ...localControls },
} );

const store = createReduxStore( STORE_NAME, storeConfig() );

type S = URLSearchStore;

declare module '@wordpress/data' {
	// @ts-ignore
	export function select( key: S[ 'StoreKey' ] ): S[ 'Selectors' ];
	// @ts-ignore
	export function dispatch( key: S[ 'StoreKey' ] ): S[ 'Actions' ];
	// @ts-ignore
	export function useDispatch( key: S[ 'StoreKey' ] ): S[ 'Actions' ];
}

export { STORE_NAME as URL_SEARCH_STORE, store as urlSearchStore };

export * from './types';
