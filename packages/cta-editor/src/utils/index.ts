import { PopupMakerGlobalVars } from '@popup-maker/types';

/**
 * Get the Popup Maker global variables
 */
export const getGlobalVars = (): Partial< PopupMakerGlobalVars > => {
	if ( typeof window === 'undefined' || ! window.popupMaker.globals ) {
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
	return window.popupMaker.globals;
};

/**
 * Check if we're in the block editor context
 */
export const isBlockEditor = (): boolean => {
	return typeof window !== 'undefined' && !! window?.wp?.blocks;
};

/**
 * Check if we're in the classic editor context
 */
export const isClassicEditor = (): boolean => {
	return typeof window !== 'undefined' && !! window?.wp?.oldEditor;
};
