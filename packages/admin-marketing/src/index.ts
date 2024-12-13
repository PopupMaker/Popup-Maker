import './styles.scss';

import $ from 'jquery';

declare global {
	interface Window {
		popupMaker: {
			globalVars: {
				assetUrl: string;
			};
		};
	}
}

const { assetUrl } = window.popupMaker.globalVars;

// Initiate when ready.
$( () => {
	$( 'a[href="edit.php?post_type=popup&page=pum-extensions"]' ).css( {
		color: '#a0d468',
	} );

	$( '#menu-posts-popup.wp-menu-open .wp-menu-image' ).css( {
		backgroundImage: `url('${ assetUrl }images/mark-light.svg')`,
	} );
} );
