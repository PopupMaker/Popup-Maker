import type { State } from './reducer';
import type { License, LicenseStatus } from './types';

export const STORE_NAME = 'popup-maker/license';

export const ACTIVATE_LICENSE = 'ACTIVATE_LICENSE';
export const CONNECT_SITE = 'CONNECT_SITE';
export const DEACTIVATE_LICENSE = 'DEACTIVATE_LICENSE';
export const REMOVE_LICENSE = 'REMOVE_LICENSE';
export const UPDATE_LICENSE_KEY = 'UPDATE_LICENSE_KEY';
export const CHECK_LICENSE_STATUS = 'CHECK_LICENSE_STATUS';
export const CHANGE_ACTION_STATUS = 'CHANGE_ACTION_STATUS';
export const HYDRATE_LICENSE_DATA = 'HYDRATE_LICENSE_DATA';
export const LICENSE_FETCH_ERROR = 'LICENSE_FETCH_ERROR';

export const ACTION_TYPES: {
	ACTIVATE_LICENSE: typeof ACTIVATE_LICENSE;
	CONNECT_SITE: typeof CONNECT_SITE;
	DEACTIVATE_LICENSE: typeof DEACTIVATE_LICENSE;
	REMOVE_LICENSE: typeof REMOVE_LICENSE;
	UPDATE_LICENSE_KEY: typeof UPDATE_LICENSE_KEY;
	CHECK_LICENSE_STATUS: typeof CHECK_LICENSE_STATUS;
	CHANGE_ACTION_STATUS: typeof CHANGE_ACTION_STATUS;
	HYDRATE_LICENSE_DATA: typeof HYDRATE_LICENSE_DATA;
	LICENSE_FETCH_ERROR: typeof LICENSE_FETCH_ERROR;
} = {
	ACTIVATE_LICENSE,
	CONNECT_SITE,
	DEACTIVATE_LICENSE,
	REMOVE_LICENSE,
	UPDATE_LICENSE_KEY,
	CHECK_LICENSE_STATUS,
	CHANGE_ACTION_STATUS,
	HYDRATE_LICENSE_DATA,
	LICENSE_FETCH_ERROR,
};

/**
 * Default license status.
 */
export const licenseStatusDefaults: LicenseStatus = {
	success: false,
	license: 'invalid',
	license_limit: 1,
	site_count: 0,
	expires: '',
	activations_left: 0,
	price_id: 0,
	error: undefined,
	error_message: undefined,
};

/**
 * Default license state.
 */
export const defaultValues: License = {
	key: '',
	status: licenseStatusDefaults,
};

/**
 * Default initial state.
 */
export const initialState: State = {
	license: defaultValues,
};
