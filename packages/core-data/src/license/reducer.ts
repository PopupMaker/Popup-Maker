import { ACTION_TYPES } from './constants';

import type {
	License,
	LicenseConnect,
	LicenseState,
	LicenseStore,
} from './types';
import type { Statuses } from '../constants';

const {
	ACTIVATE_LICENSE,
	CONNECT_SITE,
	DEACTIVATE_LICENSE,
	REMOVE_LICENSE,
	UPDATE_LICENSE_KEY,
	CHECK_LICENSE_STATUS,
	HYDRATE_LICENSE_DATA,
	CHANGE_ACTION_STATUS,
	LICENSE_FETCH_ERROR,
} = ACTION_TYPES;

interface ActionPayloadTypes {
	type: keyof typeof ACTION_TYPES;
	license: License;
	licenseKey: License[ 'key' ];
	licenseStatus: License[ 'status' ];
	connectInfo: LicenseConnect;
	// Boilerplate.
	actionName: LicenseStore[ 'ActionNames' ];
	status: Statuses;
	message: string;
}

const reducer = (
	state: LicenseState,
	{
		type,
		license,
		licenseKey,
		licenseStatus,
		connectInfo,
		// Boilerplate
		actionName,
		status,
		message,
	}: ActionPayloadTypes
) => {
	switch ( type ) {
		case ACTIVATE_LICENSE:
		case DEACTIVATE_LICENSE:
		case CHECK_LICENSE_STATUS:
			return {
				...state,
				license: {
					...state.license,
					status: licenseStatus,
				},
			};

		case CONNECT_SITE:
			return {
				...state,
				license: {
					...state.license,
					status: licenseStatus,
				},
				connectInfo,
			};

		case UPDATE_LICENSE_KEY:
			return {
				...state,
				license: {
					...state.license,
					key: licenseKey,
					status: licenseStatus,
				},
			};

		case REMOVE_LICENSE:
			return {
				...state,
				license: {
					key: '',
					status: {},
				},
			};

		case HYDRATE_LICENSE_DATA:
			return {
				...state,
				license,
			};

		case LICENSE_FETCH_ERROR:
			return {
				...state,
				error: message,
			};

		case CHANGE_ACTION_STATUS:
			return {
				...state,
				dispatchStatus: {
					...state.dispatchStatus,
					[ actionName ]: {
						...state?.dispatchStatus?.[ actionName ],
						status,
						error: message,
					},
				},
			};

		default:
			return state;
	}
};

export default reducer;
