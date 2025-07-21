import type {
	CurriedSelectorsOf,
	DispatchReturn,
} from '@wordpress/data/src/types';

export { default as licenseStore } from './store';

export {
	defaultValues as defaultLicenseValues,
	STORE_NAME as LICENSE_STORE,
} from './constants';

export type * from './types/licenses';

import type { StoreDescriptor, StoreState } from './types/store';

type Selectors = CurriedSelectorsOf< StoreDescriptor >;
type Actions = DispatchReturn< StoreDescriptor >;

export type {
	StoreDescriptor as LicenseStore,
	StoreState as LicenseStoreState,
	Selectors as LicenseStoreSelectors,
	Actions as LicenseStoreActions,
};
