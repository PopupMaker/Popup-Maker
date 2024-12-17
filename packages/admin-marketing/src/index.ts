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
	$( 'a[href="edit.php?post_type=popup&page=pum-extensions"]' ).css( {
		color: '#a0d468',
	} );

	$( '#menu-posts-popup.wp-menu-open .wp-menu-image' ).css( {
		backgroundImage: `url('${ assetsUrl }images/mark-light.svg')`,
	} );
} );
