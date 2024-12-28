import { doAction } from '@wordpress/hooks';
import { createRegistry } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import {
	callToActionStore,
	popupStore,
	licenseStore,
	settingsStore,
	urlSearchStore,
} from '@popup-maker/core-data';

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

const registry = createRegistry( {} );

registry.register( coreStore );
registry.register( callToActionStore );
registry.register( licenseStore );
registry.register( settingsStore );
registry.register( popupStore );
registry.register( urlSearchStore );

// On document ready
document.addEventListener( 'DOMContentLoaded', () => {
	// Allow other scripts to hook into the registry.
	doAction( 'popup-maker.data.registry', registry );
} );

export { registry };
