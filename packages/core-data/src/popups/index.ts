import type {
	CurriedSelectorsOf,
	DispatchReturn,
} from '@wordpress/data/src/types';

export { default as popupStore } from './store';
export * from './validation';

export {
	defaultValues as defaultPopupValues,
	STORE_NAME as POPUP_STORE,
} from './constants';

export type * from './types/posttype';

import type { StoreDescriptor, StoreState } from './types/store';

type Selectors = CurriedSelectorsOf< StoreDescriptor >;
type Actions = DispatchReturn< StoreDescriptor >;

export type {
	StoreDescriptor as PopupStore,
	StoreState as PopupStoreState,
	Selectors as PopupStoreSelectors,
	Actions as PopupStoreActions,
};
