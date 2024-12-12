import { doAction } from '@wordpress/hooks';
import { createRegistry } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import {
	popupsStore,
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
registry.register( licenseStore );
registry.register( settingsStore );
registry.register( popupsStore );
registry.register( urlSearchStore );

// On document ready
document.addEventListener( 'DOMContentLoaded', () => {
	// Allow other scripts to hook into the registry.
	doAction( 'popup-maker.data.registry', registry );
} );

export { registry };
