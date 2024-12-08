/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
( function ( $ ) {
	'use strict';

	var colorpicker = {
		init: function () {
			$( '.pum-color-picker' )
				.filter( ':not(.pum-color-picker-initialized)' )
				.addClass( 'pum-color-picker-initialized' )
				.wpColorPicker( {
					change: function ( event, ui ) {
						$( event.target ).trigger( 'colorchange', ui );
					},
					clear: function ( event ) {
						$( event.target )
							.prev()
							.trigger( 'colorchange' )
							.wpColorPicker( 'close' );
					},
					hide: true,
				} );
		},
	};

	// Import this module.
	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.colorpicker = colorpicker;

	$( document )
		.on( 'click', '.iris-palette', function () {
			$( this )
				.parents( '.wp-picker-active' )
				.find( 'input.pum-color-picker' )
				.trigger( 'change' );
		} )
		.on( 'colorchange', function ( event, ui ) {
			var $input = $( event.target ),
				color = '';

			if ( ui !== undefined && ui.color !== undefined ) {
				color = ui.color.toString();
			}

			$input.val( color ).trigger( 'change' );

			if ( $( 'form#post input#post_type' ).val() === 'popup_theme' ) {
				PUM_Admin.utils.debounce(
					PUM_Admin.themeEditor.refresh_preview,
					100
				);
			}
		} )
		.on( 'pum_init', colorpicker.init );
} )( jQuery );
