import './index.scss';
import React from 'react';
import { createRoot } from 'react-dom/client';
import domReady from '@wordpress/dom-ready';

import AddonsApp from './AddonsApp';

domReady( () => {
	const container = document.getElementById( 'popup-maker-addons' );

	if ( container ) {
		createRoot( container ).render( <AddonsApp /> );
	}
} );
