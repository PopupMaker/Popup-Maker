import './block-extensions';
import './formats';

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
