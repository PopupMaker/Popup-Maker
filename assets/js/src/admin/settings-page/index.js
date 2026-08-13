/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
import './editor.scss';
import './license-key-enhancements';

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

		const $container = $( '#pum-settings-container' );
		const settingsEditor = window.pum_settings_editor || {};
		const args = settingsEditor.form_args || {};
		const values = settingsEditor.current_values || {};
		const cssViewerConfig = window.pum_css_viewer || {};
		const cssViewerI18n = cssViewerConfig.i18n || {};
		let cssViewerStyles = null;

		function cssViewerErrorMessage( response ) {
			return response &&
				response.responseJSON &&
				response.responseJSON.data
				? response.responseJSON.data.message
				: cssViewerI18n.load_error;
		}

		function showCssViewer( $viewer, styles ) {
			const $output = $viewer.find( '#pum_style_output' );
			const $readableButton = $viewer.find(
				'[data-pum-css-format="readable"]'
			);

			$viewer.find( '#pum_core_styles' ).val( styles.core.minified );
			$viewer.find( '#pum_generated_styles' ).val( styles.generated );

			if ( ! styles.readable_available ) {
				$readableButton
					.prop( 'disabled', true )
					.attr( 'title', cssViewerI18n.readable_unavailable );
			}

			$viewer
				.find( '#show_pum_styles' )
				.attr( 'aria-expanded', 'true' )
				.hide();
			$output.removeAttr( 'hidden' ).hide().slideDown();
		}

		function loadCssViewer( $viewer ) {
			if ( cssViewerStyles ) {
				showCssViewer( $viewer, cssViewerStyles );
				return;
			}

			const $button = $viewer.find( '#show_pum_styles' );
			const $status = $viewer.find( '.pum-css-viewer__status' );

			$button.prop( 'disabled', true ).text( cssViewerI18n.loading );
			$status.attr( 'hidden', true ).removeClass( 'notice notice-error' );

			$.ajax( {
				url: cssViewerConfig.ajax_url,
				method: 'POST',
				data: {
					action: 'pum_get_css_styles',
					nonce: cssViewerConfig.nonce,
				},
			} )
				.done( ( response ) => {
					if ( ! response.success || ! response.data ) {
						$status
							.text( cssViewerI18n.load_error )
							.addClass( 'notice notice-error' )
							.removeAttr( 'hidden' );
						$button
							.prop( 'disabled', false )
							.text( cssViewerI18n.show );
						return;
					}

					cssViewerStyles = response.data;
					showCssViewer( $viewer, cssViewerStyles );
				} )
				.fail( ( response ) => {
					$status
						.text( cssViewerErrorMessage( response ) )
						.addClass( 'notice notice-error' )
						.removeAttr( 'hidden' );
					$button
						.prop( 'disabled', false )
						.text( cssViewerI18n.show );
				} );
		}

		function updateSaveButtonVisibility() {
			const $activeMainPanel = $container
				.children( '.pum-tabs-container' )
				.first()
				.children( '.tab-content.active' );
			const isEmptyGoProPanel =
				'pum-settings_go-pro' === $activeMainPanel.attr( 'id' ) &&
				0 ===
					$activeMainPanel
						.find( '.pum-field' )
						.not( '.pum-go-pro-placeholder' ).length;

			$( '#pum-settings-save' ).toggle( ! isEmptyGoProPanel );
		}

		if ( $container.length ) {
			$container.find( '.pum-no-js' ).hide();
			window.PUM_Admin.forms.render( args, values, $container );
			updateSaveButtonVisibility();

			$container.on( 'click', '#show_pum_styles', function () {
				loadCssViewer( $( this ).closest( '.pum-css-viewer' ) );
			} );

			$container.on( 'click', '[data-pum-css-format]', function () {
				if ( ! cssViewerStyles ) {
					return;
				}

				const $button = $( this );
				const format = $button.data( 'pum-css-format' );
				const $viewer = $button.closest( '.pum-css-viewer' );

				$viewer
					.find( '#pum_core_styles' )
					.val( cssViewerStyles.core[ format ] );
				$viewer
					.find( '[data-pum-css-format]' )
					.removeClass( 'button-primary' )
					.attr( 'aria-pressed', 'false' );
				$button
					.addClass( 'button-primary' )
					.attr( 'aria-pressed', 'true' );
			} );

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
							window.history.replaceState(
								null,
								null,
								window.location.pathname +
									window.location.search
							);
						}

						updateSaveButtonVisibility();
					}, 50 );
				}
			);
		}
	} );
} )( window.jQuery );
