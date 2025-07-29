import type {
	CurriedSelectorsOf,
	DispatchReturn,
} from '@wordpress/data/src/types';

export { default as callToActionStore } from './store';
export * from './validation';

export {
	defaultValues as defaultCtaValues,
	STORE_NAME as CALL_TO_ACTION_STORE,
	NOTICE_CONTEXT,
} from './constants';

export type * from './types/posttype';

import type { StoreDescriptor, StoreState } from './types/store';

type Selectors = CurriedSelectorsOf< StoreDescriptor >;
type Actions = DispatchReturn< StoreDescriptor >;

export type {
	StoreDescriptor as CallToActionStore,
	StoreState as CallToActionStoreState,
	Selectors as CallToActionStoreSelectors,
	Actions as CallToActionStoreActions,
};
