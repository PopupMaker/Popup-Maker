import domReady from '@wordpress/dom-ready';

// import './lib/call-to-action';

import * as blocks from './lib';

domReady( () => {
	Object.values( blocks ).forEach( ( { init } ) => {
		init();
	} );
} );
