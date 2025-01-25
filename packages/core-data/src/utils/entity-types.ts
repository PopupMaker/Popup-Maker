import type {
	ReduxStoreConfig,
	StoreDescriptor as StoreDescriptorType,
} from '@wordpress/data/src/types';

import type * as actions from './entity-actions';
import type * as selectors from './entity-selectors';
import type { StoreThunkContext } from '../types';

export type StoreState = {};
export type StoreActions = typeof actions;
export type StoreActionNames = keyof StoreActions;
export type StoreSelectors = typeof selectors;

export interface StoreConfig
	extends ReduxStoreConfig< StoreState, StoreActions, StoreSelectors > {}

export interface StoreDescriptor extends StoreDescriptorType< StoreConfig > {}

export type ThunkContext< T extends StoreDescriptor = StoreDescriptor > =
	StoreThunkContext< T >;

export type ThunkAction< R extends any = void > = (
	context: ThunkContext< StoreDescriptor >
) => Promise< R > | R;
