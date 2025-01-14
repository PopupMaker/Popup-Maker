import type { LicenseStatus, LicenseState, License } from './types';

export const STORE_NAME = 'popup-maker/license';

export const ACTION_TYPES = {
	ACTIVATE_LICENSE: 'ACTIVATE_LICENSE',
	CONNECT_SITE: 'CONNECT_SITE',
	DEACTIVATE_LICENSE: 'DEACTIVATE_LICENSE',
	REMOVE_LICENSE: 'REMOVE_LICENSE',
	UPDATE_LICENSE_KEY: 'UPDATE_LICENSE_KEY',
	CHECK_LICENSE_STATUS: 'CHECK_LICENSE_STATUS',
	CHANGE_ACTION_STATUS: 'CHANGE_ACTION_STATUS',
	HYDRATE_LICENSE_DATA: 'HYDRATE_LICENSE_DATA',
	LICENSE_FETCH_ERROR: 'LICENSE_FETCH_ERROR',
};

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
export const licenseDefaults: License = {
	key: '',
	status: licenseStatusDefaults,
};

export const initialState: LicenseState = {
	license: licenseDefaults,
};
