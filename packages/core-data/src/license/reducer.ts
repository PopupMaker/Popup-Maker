import { ACTION_TYPES, initialState } from './constants';

import type { DispatchStatuses } from '../constants';
import type {
	License,
	LicenseConnect,
	LicenseStatus,
	StoreActionNames,
} from './types';

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

export type State = {
	license: License;
	connectInfo?: LicenseConnect;
	// Boilerplate
	dispatchStatus?: {
		[ Property in StoreActionNames ]?: {
			status: string;
			error: string;
		};
	};
	error?: string;
};

type BaseAction = {
	type: keyof typeof ACTION_TYPES;
};

type LicenseStatusAction = BaseAction & {
	type:
		| typeof ACTIVATE_LICENSE
		| typeof DEACTIVATE_LICENSE
		| typeof CHECK_LICENSE_STATUS;
	licenseStatus: LicenseStatus;
};

type ConnectSiteAction = BaseAction & {
	type: typeof CONNECT_SITE;
	licenseStatus: LicenseStatus;
	connectInfo: LicenseConnect;
};

type UpdateLicenseKeyAction = BaseAction & {
	type: typeof UPDATE_LICENSE_KEY;
	licenseKey: string;
	licenseStatus: LicenseStatus;
};

type RemoveLicenseAction = BaseAction & {
	type: typeof REMOVE_LICENSE;
};

type HydrateLicenseDataAction = BaseAction & {
	type: typeof HYDRATE_LICENSE_DATA;
	license: License;
};

type LicenseFetchErrorAction = BaseAction & {
	type: typeof LICENSE_FETCH_ERROR;
	message: string;
};

type ChangeActionStatusAction = BaseAction & {
	type: typeof CHANGE_ACTION_STATUS;
	actionName: StoreActionNames;
	status: DispatchStatuses;
	message: string;
};

export type ReducerAction =
	| LicenseStatusAction
	| ConnectSiteAction
	| UpdateLicenseKeyAction
	| RemoveLicenseAction
	| HydrateLicenseDataAction
	| LicenseFetchErrorAction
	| ChangeActionStatusAction;

const reducer = ( state: State = initialState, action: ReducerAction ) => {
	switch ( action.type ) {
		case ACTIVATE_LICENSE:
		case DEACTIVATE_LICENSE:
		case CHECK_LICENSE_STATUS:
			return {
				...state,
				license: {
					...state.license,
					status: action.licenseStatus,
				},
			};
			return state;

		case CONNECT_SITE:
			return {
				...state,
				license: {
					...state.license,
					status: action.licenseStatus,
				},
				connectInfo: action.connectInfo,
			};

		case UPDATE_LICENSE_KEY:
			return {
				...state,
				license: {
					...state.license,
					key: action.licenseKey,
					status: action.licenseStatus,
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
				license: action.license,
			};

		case LICENSE_FETCH_ERROR:
			return {
				...state,
				error: action.message,
			};

		case CHANGE_ACTION_STATUS:
			return {
				...state,
				dispatchStatus: {
					...state.dispatchStatus,
					[ action.actionName ]: {
						...state?.dispatchStatus?.[ action.actionName ],
						status: action.status,
						error: action.message,
					},
				},
			};

		default:
			return state;
	}
};

export default reducer;
