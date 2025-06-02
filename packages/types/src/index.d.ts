/**
 * Global types for Popup Maker
 */

/**
 * The permissions for the current user.
 */
export interface PopupMakerPermissions {
	/**
	 * Whether the user can edit call to actions.
	 */
	edit_ctas: boolean;
	/**
	 * Whether the user can edit popups.
	 */
	edit_popups: boolean;
	/**
	 * Whether the user can edit popup themes.
	 */
	edit_popup_themes: boolean;
	/**
	 * Whether the user can manage settings.
	 */
	mange_settings: boolean;
}

/**
 * The global variables for the Popup Maker plugin.
 */
export interface PopupMakerGlobalVars {
	/**
	 * The version of the Popup Maker plugin.
	 */
	version: string;
	/**
	 * The version of the WordPress installation.
	 */
	wpVersion: number;
	/**
	 * The URL to the assets directory.
	 */
	assetsUrl: string;
	/**
	 * The URL to the admin directory.
	 */
	adminUrl: string;
	/**
	 * The URL to the plugin directory.
	 */
	pluginUrl: string;
	/**
	 * The nonce for the current request.
	 */
	nonce: string;
	/**
	 * The permissions for the current user.
	 */
	permissions: PopupMakerPermissions;
	/**
	 * Whether the Pro version is installed.
	 */
	isProInstalled?: '1' | '';
	/**
	 * Whether the Pro version is activated.
	 */
	isProActivated?: '1' | '';
}

/**
 * The window object for the Popup Maker plugin.
 *
 * window.popupMaker
 */
export interface PopupMakerWindow {
	/**
	 * The global variables for the Popup Maker plugin.
	 */
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

type Tab = {
	/**
	 * The key of the tab.
	 */
	name: string;
	/**
	 * The label of the tab.
	 */
	title: string;
	/**
	 * The class name to apply to the tab button.
	 */
	className?: string;
	/**
	 * The icon used for the tab button.
	 */
	icon?: IconType;
	/**
	 * Determines if the tab button should be disabled.
	 */
	disabled?: boolean;
} & Record< any, any >;

export interface TabComponent< TabProps extends any = unknown > extends Tab {
	name: string;
	title: string | JSX.Element;
	badge?: string | JSX.Element;
	className: string;
	pageTitle: string;
	heading: string;
	/**
	 * Predefined component to render in the tab, propless.
	 *
	 * @deprecated Use Component instead.
	 */
	comp?: () => JSX.Element;
	/**
	 * The component to render in the tab, can be passed tab defined tab props.
	 */
	Component?: React.ComponentType< TabProps >;
	onClick?: () => void | false;
}

export interface ComponentTab< TabProps extends any = unknown > extends Tab {
	name: string;
	title: string | JSX.Element;
	badge?: string | JSX.Element;
	className?: string;
	pageTitle?: string;
	heading?: string;
	/**
	 * The component to render in the tab, can be passed tab defined tab props.
	 */
	Component: React.ComponentType< TabProps >;
	onClick?: () => void | false;
}
