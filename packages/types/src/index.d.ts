declare global {
	interface Window {
		popupMaker: {
			globalVars: {
				version: string;
				wpVersion: number;
				assetUrl: string;
				adminUrl: string;
				pluginUrl: string;
				permissions: {
					edit_ctas: boolean;
					edit_popups: boolean;
					edit_popup_themes: boolean;
					mange_settings: boolean;
				};
				isProInstalled?: '1' | '';
				isProActivated?: '1' | '';
			};
		};
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
