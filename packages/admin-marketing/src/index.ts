import './styles.scss';

import $ from 'jquery';

declare global {
	interface Window {
		popupMaker: {
			globalVars: {
				assetsUrl: string;
			};
		};
	}
}

const { assetsUrl } = window.popupMaker.globalVars;

// Initiate when ready.
$( () => {
	$( 'a[href*="pum-settings#go-pro"]' ).css( {
		color: '#1dbe61',
	} );

	$( '#menu-posts-popup.wp-menu-open .wp-menu-image' ).css( {
		backgroundImage: `url('${ assetsUrl }images/mark-light.svg')`,
	} );
} );
