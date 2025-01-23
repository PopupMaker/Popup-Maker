import type {
	ReduxStoreConfig,
	StoreDescriptor as StoreDescriptorType,
} from '@wordpress/data/src/types';

import type reducer from '../reducer';
import type * as actions from '../actions';
import type * as selectors from '../selectors';
import type * as resolvers from '../resolvers';
import type { STORE_NAME, ACTION_TYPES } from '../constants';

import type { StoreThunkContext } from '../../types';

/**
 * Base Redux action shape
 */
export interface DispatchAction {
	type: ( typeof ACTION_TYPES )[ keyof typeof ACTION_TYPES ];
	[ key: string ]: any;
}

/**
 * The shape of your store's "State" is typically what your reducer returns.
 */
export type StoreState = ReturnType< typeof reducer >;

/**
 * Actions object is `typeof actions` (your `./actions.ts`).
 */
export type StoreActions = typeof actions;

/**
 * Resolvers object is `typeof resolvers` (your `./resolvers.ts`).
 */
export type StoreResolvers = typeof resolvers;

/**
 * Action names.
 */
export type StoreActionNames = keyof StoreActions;

/**
 * Selectors object is `typeof selectors` (your `./selectors.ts`).
 */
export type StoreSelectors = typeof selectors;

/**
 * Build a ReduxStoreConfig that references your state, actions, and selectors.
 */
export interface StoreConfig
	extends ReduxStoreConfig< StoreState, StoreActions, StoreSelectors > {
	resolvers: StoreResolvers;
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
 * Define the ThunkAction shape.
 */
export type ThunkAction< R = void > = (
	context: ThunkContext
) => Promise< R > | R;
