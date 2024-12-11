import './styles.scss';

import $ from 'jquery';

import markLight from '../../../assets/images/mark-light.svg';

// Initiate when ready.
$( () => {
	$( 'a[href="edit.php?post_type=popup&page=pum-extensions"]' ).css( {
		color: '#a0d468',
	} );

	$( '#menu-posts-popup.wp-menu-open .wp-menu-image' ).css( {
		backgroundImage: 'url(' + markLight + ')',
	} );
} );
