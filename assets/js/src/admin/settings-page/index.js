/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
import './editor.scss';
import './license-key-enhancements';
import './license-status-polling';
import './pro-upgrade-flow';

( function ( $ ) {
	'use strict';

	window.PUM_Admin = window.PUM_Admin || {};

	// Initiate when ready.
	$( function () {
		// Universal tab deep linking - check hash and activate corresponding tab
		function switchToHashTab() {
			const hash = window.location.hash.replace( '#', '' );
			if ( hash ) {
				// Try direct tab ID first (e.g. #pum-settings_licenses)
				let $tab = $( `a[href="#${ hash }"]` );
				// If not found, try with pum-settings_ prefix (e.g. #go-pro -> pum-settings_go-pro)
				if ( ! $tab.length ) {
					$tab = $( `a[href="#pum-settings_${ hash }"]` );
				}
				// Activate the tab if found
				if ( $tab.length ) {
					setTimeout( () => $tab.trigger( 'click' ), 100 );
				}
			}
		}

		var $container = $( '#pum-settings-container' ),
			args = pum_settings_editor.form_args || {},
			values = pum_settings_editor.current_values || {};

		if ( $container.length ) {
			$container.find( '.pum-no-js' ).hide();
			PUM_Admin.forms.render( args, values, $container );

			// Check hash on page load
			switchToHashTab();

			// Listen for hash changes (Go Pro link clicks while on settings page)
			$( window ).on( 'hashchange', switchToHashTab );

			// Clear hash when any tab is clicked to allow Go Pro link to work when already on #go-pro
			$container.on(
				'click',
				'.pum-tabs-container a[href^="#"]',
				function () {
					setTimeout( () => {
						if ( window.location.hash ) {
							history.replaceState(
								null,
								null,
								window.location.pathname +
									window.location.search
							);
						}
					}, 50 );
				}
			);
		}
	} );
} )( jQuery );
