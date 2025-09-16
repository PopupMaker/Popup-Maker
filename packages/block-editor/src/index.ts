import './block-extensions';
import './formats';

import {
	callToActionStore,
	popupStore,
	licenseStore,
	settingsStore,
	urlSearchStore,
} from '@popup-maker/core-data';

import { register } from '@wordpress/data';

import type { Popup } from '@popup-maker/core-data';

declare global {
	interface Window {
		popupMaker: {
			globalVars: {
				assetUrl: string;
				adminUrl: string;
				pluginUrl: string;
			};
		};

		popupMakerBlockEditor: {
			popups: Popup[];
			popupTriggerExcludedBlocks?: string[];
			permissions: {
				manage_settings: boolean;
				edit_restrictions: boolean;
				view_block_controls: boolean;
				edit_block_controls: boolean;
				[ key: string ]: boolean;
			};
		};
	}
}

/* Broken @wordpress/data type overrides */
declare module '@wordpress/data' {
	// eslint-disable-next-line @typescript-eslint/no-shadow
	function createRegistry(
		storeConfigs?: Object,
		parent?: Object | null
	): {
		registerGenericStore: Function;
		registerStore: Function;
		subscribe: Function;
		select: Function;
		dispatch: Function;
		register: Function;
	};
}

// Register our cstom data stores with WordPress core editor data.
register( callToActionStore );
register( licenseStore );
register( settingsStore );
register( popupStore );
register( urlSearchStore );
