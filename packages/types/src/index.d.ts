/**
 * Global types for Popup Maker
 */

export interface PopupMakerPermissions {
	edit_ctas: boolean;
	edit_popups: boolean;
	edit_popup_themes: boolean;
	mange_settings: boolean;
}

export interface PopupMakerGlobalVars {
	version: string;
	wpVersion: number;
	assetsUrl: string;
	adminUrl: string;
	pluginUrl: string;
	nonce: string;
	permissions: PopupMakerPermissions;
	isProInstalled?: '1' | '';
	isProActivated?: '1' | '';
}
export interface PopupMakerWindow {
	globalVars: PopupMakerGlobalVars;
}
export interface WordPressWindow {
	oldEditor: {
		initialize: ( id: string, settings: any ) => void;
		remove: ( id: string ) => void;
	};
	blocks?: unknown;
}
declare global {
	interface Window {
		wp: WordPressWindow & Record< string, unknown >;
		popupMaker: PopupMakerWindow & Record< string, unknown >;
	}
}

export type TabComponent = {
	name: string;
	title: string | JSX.Element;
	badge?: string | JSX.Element;
	className: string;
	pageTitle: string;
	heading: string;
	comp?: () => JSX.Element;
	onClick?: () => void | false;
};
