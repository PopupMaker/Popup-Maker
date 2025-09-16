import domReady from '@wordpress/dom-ready';

// import './lib/call-to-action';

import * as blocks from './lib';

declare global {
	interface Window {
		popupMakerBlockLibrary: {
			homeUrl: string;
		};
		typenow: string;
	}
}

domReady( () => {
	Object.values( blocks ).forEach( ( { init } ) => {
		init();
	} );
} );
