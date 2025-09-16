export type LicenseKey = string | undefined;

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
