import type { OmitFirstArgs, RemoveReturnTypes } from '../types';

export type LicenseKey = string;

export type LicenseStatus = {
	success: boolean;
	license:
		| 'valid'
		| 'deactivated'
		| 'invalid'
		| 'expired'
		| 'disabled'
		| 'site_inactive'
		| 'item_name_mismatch'
		| 'no_activations_left';
	license_limit: number;
	site_count: number;
	expires: string | Date;
	activations_left: number;
	price_id?: number | string | boolean;
	error?: string;
	error_message?: string;
};

export type License = {
	key: LicenseKey;
	status: LicenseStatus;
};

export type LicenseConnect = {
	url: string;
	back_url: string;
};

export type LicenseStatusResponse = {
	status: LicenseStatus;
};

export type LicenseActivationResponse = LicenseStatusResponse & {
	connectInfo?: LicenseConnect;
};

export type LicenseState = {
	license: License;
	connectInfo?: LicenseConnect;
	// Boilerplate
	dispatchStatus?: {
		[ Property in LicenseStore[ 'ActionNames' ] ]?: {
			status: string;
			error: string;
		};
	};
	error?: string;
};

export interface LicenseStore {
	StoreKey:
		| 'popup-paker/license'
		| typeof import('./index').LICENSE_STORE
		| typeof import('./index').licenseStore;
	State: LicenseState;
	Actions: RemoveReturnTypes< typeof import('./actions') >;
	Selectors: OmitFirstArgs< typeof import('./selectors') >;
	ActionNames: keyof LicenseStore[ 'Actions' ];
	SelectorNames: keyof LicenseStore[ 'Selectors' ];
}
