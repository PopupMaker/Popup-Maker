import type { OmitFirstArgs, RemoveReturnTypes } from '../types';

export type PermissionValue = string | false;

export interface Settings {
	permissions: {
		// Block Controls
		view_block_controls: PermissionValue;
		edit_block_controls: PermissionValue;
		// Restrictions
		edit_restrictions: PermissionValue;
		// Settings
		manage_settings: PermissionValue;
		// Extendable
		[ key: string ]: PermissionValue;
	};
}

export type SettingsState = {
	settings: Settings;
	unsavedChanges?: Partial< Settings >;
	// Boilerplate
	dispatchStatus?: {
		[ Property in SettingsStore[ 'ActionNames' ] ]?: {
			status: string;
			error: string;
		};
	};
	error?: string;
};

export interface SettingsStore {
	StoreKey:
		| 'popup-maker/settings'
		| typeof import('../settings/index').SETTINGS_STORE
		| typeof import('../settings/index').settingsStore;
	State: SettingsState;
	Actions: RemoveReturnTypes< typeof import('../settings/actions') >;
	Selectors: OmitFirstArgs< typeof import('../settings/selectors') >;
	ActionNames: keyof SettingsStore[ 'Actions' ];
	SelectorNames: keyof SettingsStore[ 'Selectors' ];
}
