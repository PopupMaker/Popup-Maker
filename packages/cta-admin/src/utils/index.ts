import type { PopupMakerGlobalVars } from '@popup-maker/types';

/**
 * Get the Popup Maker global variables
 */
export const getGlobalVars = (): Partial< PopupMakerGlobalVars > => {
	if ( typeof window === 'undefined' || ! window.popupMaker.globalVars ) {
		// Provide fallback values when running in block editor or SSR
		return {
			version: '1.0.0',
			wpVersion: 6.5,
			pluginUrl: '',
			assetsUrl: '',
			adminUrl: '',
			nonce: '',
			permissions: {
				edit_ctas: false,
				edit_popups: false,
				edit_popup_themes: false,
				mange_settings: false,
			},
		};
	}
	return window.popupMaker.globalVars;
};
