/**
 * Defines the core $.popmake callbacks.
 * Version 1.4
 */
( function ( $, document, undefined ) {
	'use strict';

	$.fn.popmake.callbacks = {
		reposition_using: function ( position ) {
			$( this ).css( position );
		},
	};
} )( jQuery, document );
