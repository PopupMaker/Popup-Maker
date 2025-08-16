/*******************************************************************************
 * License Key UX Enhancements
 * Improves user experience with license key inputs
 * Copyright (c) 2025, Code Atlantic LLC
 ******************************************************************************/

( function ( $ ) {
	'use strict';

	/**
	 * License Key UX Enhancements
	 * - Auto-click activate button on paste
	 * - Enter key triggers save/activate
	 */
	function initLicenseKeyEnhancements() {
		const licenseInputs = $( '#popup_maker_pro_license_key' );

		if ( licenseInputs.length === 0 ) {
			return;
		}

		licenseInputs.each( function () {
			const $input = $( this );

			if ( ! $input.length ) {
				return;
			}

			const $activateButton = $input
				.closest( '.pum-license-input-group' )
				.find( '.pum-license-activate, .button-primary' );

			/**
			 * Check license key length and enable/disable activate button
			 * Enables button when input length equals 32 characters and not processing
			 */
			function checkLicenseKeyLength() {
				const licenseKey = $input.val().trim();
				const targetLength = 32; // Length of fbec66dc4c7b47c233a136e9b66f1c64
				const isProcessing =
					$activateButton.data( 'processing' ) === true;

				if ( $activateButton.length ) {
					if (
						licenseKey.length === targetLength &&
						! isProcessing
					) {
						$activateButton.prop( 'disabled', false );
					} else {
						$activateButton.prop( 'disabled', true );
					}
				}
			}

			// Auto-click activate button on paste
			$input.on( 'paste', function () {
				// Small delay to allow paste content to be processed
				setTimeout( function () {
					// Validate input has content
					const licenseKey = $input.val().trim();
					if ( licenseKey.length === 0 ) {
						return;
					}

					// Check length and enable button if appropriate
					checkLicenseKeyLength();

					// Only proceed with auto-click if button is enabled and length is correct
					if (
						$activateButton.length &&
						! $activateButton.prop( 'disabled' )
					) {
						// Trigger the activate button
						$activateButton.trigger( 'click' );
					}
				}, 100 );
			} );

			// Check license key length as user types
			$input.on( 'input keyup', function () {
				checkLicenseKeyLength();
			} );

			// Enter key triggers save/activate
			$input.on( 'keypress', function ( e ) {
				// Check if Enter key was pressed (keyCode 13)
				if ( e.which === 13 || e.keyCode === 13 ) {
					e.preventDefault();

					// Check length first
					checkLicenseKeyLength();

					// Validate input has content and correct length
					const licenseKey = $input.val().trim();
					if ( licenseKey.length === 0 ) {
						return;
					}

					// Only trigger if button exists and is not disabled
					if (
						$activateButton.length &&
						! $activateButton.prop( 'disabled' )
					) {
						// Trigger the activate button
						$activateButton.trigger( 'click' );
					}
				}
			} );

			// Disable button during processing
			$activateButton.on( 'click', function () {
				const $btn = $( this );
				setTimeout( function () {
					$btn.data( 'processing', true );
					$btn.data( 'original-text', $btn.val() );
					$btn.val( $btn.val() + '...' );
					$btn.prop( 'disabled', true );
				}, 50 );
			} );

			// Initial check on page load to set correct button state
			checkLicenseKeyLength();
		} );
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
