/*******************************************************************************
 * License Key UX Enhancements
 * Streamlined license management with PHP form processing
 * Copyright (c) 2025, Code Atlantic LLC
 ******************************************************************************/

( function ( $ ) {
	'use strict';

	window.PUM_Admin = window.PUM_Admin || {};

	/**
	 * License Key UX Enhancements
	 * Essential features:
	 * - Auto-click activate on paste (32-char validation)
	 * - Enter key triggers activate button click
	 * - Real-time button state management
	 * - Uses existing PHP form processing for reliability
	 */
	function initLicenseKeyEnhancements() {
		const $licenseInput = $( '#popup_maker_pro_license_key' );

		if ( ! $licenseInput.length ) {
			return;
		}

		const $activateButton = $licenseInput
			.closest( '.pum-license-input-wrapper' )
			.find( '.pum-license-activate, .button-primary' );

		/**
		 * Check license key format and manage button state
		 */
		function updateButtonState() {
			const licenseKey = $licenseInput.val().trim();
			const isValidFormat = licenseKey.length === 32;
			const isProcessing = $activateButton.data( 'processing' ) === true;

			if ( $activateButton.length ) {
				$activateButton.prop( 'disabled', ! isValidFormat || isProcessing );
			}
		}

		/**
		 * Auto-click activate button on paste
		 */
		$licenseInput.on( 'paste', function () {
			setTimeout( function () {
				const licenseKey = $licenseInput.val().trim();
				if ( licenseKey.length === 32 && $activateButton.length && ! $activateButton.prop( 'disabled' ) ) {
					$activateButton.trigger( 'click' );
				}
			}, 100 );
		} );

		/**
		 * Enter key triggers activate button click
		 */
		$licenseInput.on( 'keypress', function ( e ) {
			if ( e.which === 13 || e.keyCode === 13 ) {
				e.preventDefault();
				const licenseKey = $licenseInput.val().trim();
				if ( licenseKey.length === 32 && $activateButton.length && ! $activateButton.prop( 'disabled' ) ) {
					$activateButton.trigger( 'click' );
				}
			}
		} );

		/**
		 * Update button state as user types
		 */
		$licenseInput.on( 'input keyup', updateButtonState );

		/**
		 * Add processing state to button clicks
		 */
		$activateButton.on( 'click', function () {
			const $btn = $( this );
			setTimeout( function () {
				$btn.data( 'processing', true );
				const originalText = $btn.val() || $btn.text();
				$btn.data( 'original-text', originalText );
				$btn.val( originalText + '...' ).prop( 'disabled', true );
			}, 50 );
		} );

		// Initial button state
		updateButtonState();
	}

	// Initialize on document ready
	$( function () {
		initLicenseKeyEnhancements();
	} );

	// Re-initialize if settings are dynamically loaded
	$( document ).on( 'pum_init', function () {
		initLicenseKeyEnhancements();
	} );

} )( jQuery );
