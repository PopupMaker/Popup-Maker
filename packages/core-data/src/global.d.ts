import type { SettingsStoreState } from './settings';

/**
 * Global window augmentations
 */
declare global {
	const wpApiSettings: WPAPISettings;

	const popupMakerCoreData: PopupMakerCoreData;

	interface Window {
		wpApiSettings?: WPAPISettings;
		popupMakerCoreData?: PopupMakerCoreData;
	}
}

type PopupMakerCoreData = {
	currentSettings: SettingsStoreState[ 'settings' ];
};

type WPAPISettings = {
	root: string;
	nonce: string;
};

export {};
