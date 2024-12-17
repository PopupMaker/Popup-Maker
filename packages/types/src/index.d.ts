declare global {
	interface Window {
		popupMaker: {
			globalVars: {
				assetUrl: string;
				adminUrl: string;
				pluginUrl: string;
			};
		};
	}
}
