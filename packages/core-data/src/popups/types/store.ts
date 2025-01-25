import type {
	ReduxStoreConfig,
	StoreDescriptor as StoreDescriptorType,
} from '@wordpress/data/src/types';

import type reducer from '../reducer';
import type actions from '../actions';
import type selectors from '../selectors';
import type { STORE_NAME } from '../constants';

import type { StoreThunkContext } from '../../types';
import type { ReducerAction } from '../reducer';

/**
 * The shape of your store's "State" is typically what your reducer returns.
 */
export type StoreState = ReturnType< typeof reducer >;

/**
 * Actions object is `typeof actions` (your `./actions.ts`).
 */
export type StoreActions = typeof actions;

/**
 * Selectors object is `typeof selectors` (your `./selectors.ts`).
 */
export type StoreSelectors = typeof selectors;

/**
 * Build a ReduxStoreConfig that references your state, actions, and selectors.
 */
export interface StoreConfig
	extends ReduxStoreConfig< StoreState, StoreActions, StoreSelectors > {
	// Optionally add `resolvers`, `controls`, etc. if you have them.
}

/**
 * Now define a "Store Descriptor" that references this config.
 */
export interface StoreDescriptor extends StoreDescriptorType< StoreConfig > {
	name: typeof STORE_NAME;
}

/**
 * Define the ThunkArgs / ThunkAction shape.
 */
export type ThunkContext = StoreThunkContext< StoreDescriptor >;

/**
 * Base Redux action shape
 */
export type DispatchAction = ReducerAction;

/**
 * Define the ThunkAction shape.
 */
export type ThunkAction< R > = ( context: ThunkContext ) => Promise< R > | R;
