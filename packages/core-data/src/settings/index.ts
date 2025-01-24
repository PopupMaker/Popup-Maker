export { default as settingsStore } from './store';

export {
	defaultValues as defaultSettings,
	STORE_NAME as SETTINGS_STORE,
} from './constants';

export type {
	StoreDescriptor as SettingsStore,
	StoreState as SettingsStoreState,
} from './types';
