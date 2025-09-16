import type {
	CurriedSelectorsOf,
	DispatchReturn,
} from '@wordpress/data/src/types';

export { default as settingsStore } from './store';

export {
	defaultValues as defaultSettings,
	STORE_NAME as SETTINGS_STORE,
} from './constants';

import type { StoreDescriptor, StoreState } from './types';

type Selectors = CurriedSelectorsOf< StoreDescriptor >;
type Actions = DispatchReturn< StoreDescriptor >;

export type {
	StoreDescriptor as SettingsStore,
	StoreState as SettingsStoreState,
	Selectors as SettingsStoreSelectors,
	Actions as SettingsStoreActions,
};
