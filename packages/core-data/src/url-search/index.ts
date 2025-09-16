import type {
	CurriedSelectorsOf,
	DispatchReturn,
} from '@wordpress/data/src/types';

export { default as urlSearchStore } from './store';

export { STORE_NAME as URL_SEARCH_STORE } from './constants';

export type * from './types/url-search';

import type { StoreDescriptor, StoreState } from './types/store';

type Selectors = CurriedSelectorsOf< StoreDescriptor >;
type Actions = DispatchReturn< StoreDescriptor >;

export type {
	StoreDescriptor as UrlSearchStore,
	StoreState as UrlSearchStoreState,
	Selectors as UrlSearchStoreSelectors,
	Actions as UrlSearchStoreActions,
};
