/*******************************************************************************
 * License Key UX Enhancements
 * Improves user experience with license key inputs and integrates upgrade flow
 * Copyright (c) 2025, Code Atlantic LLC
 ******************************************************************************/

( function ( $ ) {
	'use strict';

	window.PUM_Admin = window.PUM_Admin || {};

	/**
	 * License Key UX Enhancements
	 * - Auto-click activate button on paste
	 * - Enter key triggers save/activate
	 * - Upgrade flow integration
	 * - Loading states and error messages
	 * - Automatic upgrade triggering
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

		// Initialize upgrade flow integration
		initUpgradeFlowIntegration();
	}

	/**
	 * Initialize upgrade flow integration features
	 */
	function initUpgradeFlowIntegration() {
		// Add upgrade flow triggers to the license section
		addUpgradeTriggers();

		// Bind upgrade flow events
		bindUpgradeFlowEvents();

		// Initialize loading states
		initLoadingStates();

		// Monitor license status changes
		monitorLicenseStatus();
	}

	/**
	 * Add upgrade flow trigger buttons to license interface
	 */
	function addUpgradeTriggers() {
		const $licenseSection = $( '#popup_maker_pro_license_key' ).closest(
			'.pum-field'
		);

		if ( ! $licenseSection.length ) {
			return;
		}

		// Add upgrade trigger button if license key is empty
		const $licenseInput = $( '#popup_maker_pro_license_key' );
		const licenseKey = $licenseInput.val().trim();

		if ( licenseKey.length === 0 ) {
			const $upgradeButton = $( `
				<div class="pum-upgrade-flow-container">
					<p class="pum-upgrade-prompt">
						Don't have a license yet?
						<button type="button" class="button button-secondary pum-pro-upgrade-trigger"
								data-product="popup-maker-pro"
								data-source="license-section"
								data-campaign="no-license">
							Get Popup Maker Pro
						</button>
					</p>
				</div>
			` );

			$licenseSection.append( $upgradeButton );
		}

		// Add connection trigger for existing licenses
		const $connectButton = $( `
			<div class="pum-license-connect-container">
				<p class="pum-connect-prompt">
					Already have a license?
					<button type="button" class="button button-primary pum-license-connect-trigger"
							data-product="popup-maker-pro"
							data-source="license-section"
							data-campaign="existing-license">
						Connect License
					</button>
				</p>
			</div>
		` );

		$licenseSection.append( $connectButton );
	}

	/**
	 * Bind upgrade flow related events
	 */
	function bindUpgradeFlowEvents() {
		// License connection success
		$( document ).on( 'pum_license_connected', handleLicenseConnected );

		// License status changes
		$( document ).on(
			'pum_license_status_changed',
			handleLicenseStatusChanged
		);

		// Pro installation trigger
		$( document ).on( 'pum_install_pro_plugin', handleProInstallation );

		// License validation errors
		$( document ).on( 'pum_license_connection_error', handleLicenseError );

		// License field changes
		$( '#popup_maker_pro_license_key' ).on(
			'input change',
			handleLicenseFieldChange
		);
	}

	/**
	 * Initialize loading states for license interface
	 */
	function initLoadingStates() {
		// Add loading state styles if not present
		if ( ! $( '#pum-license-loading-styles' ).length ) {
			$( 'head' ).append( `
				<style id="pum-license-loading-styles">
					.pum-license-loading {
						position: relative;
						opacity: 0.6;
						pointer-events: none;
					}

					.pum-license-loading::after {
						content: '';
						position: absolute;
						top: 50%;
						left: 50%;
						width: 20px;
						height: 20px;
						margin: -10px 0 0 -10px;
						border: 2px solid #f3f3f3;
						border-top: 2px solid #007cba;
						border-radius: 50%;
						animation: pum-spin 1s linear infinite;
					}

					@keyframes pum-spin {
						0% { transform: rotate(0deg); }
						100% { transform: rotate(360deg); }
					}

					.pum-upgrade-flow-container,
					.pum-license-connect-container {
						margin-top: 10px;
						padding: 10px;
						background: #f9f9f9;
						border-left: 4px solid #007cba;
					}

					.pum-upgrade-prompt,
					.pum-connect-prompt {
						margin: 0;
					}

					.pum-license-status {
						margin-top: 5px;
						padding: 5px 10px;
						border-radius: 3px;
						font-size: 12px;
					}

					.pum-license-status.valid {
						background: #d4edda;
						color: #155724;
						border: 1px solid #c3e6cb;
					}

					.pum-license-status.invalid {
						background: #f8d7da;
						color: #721c24;
						border: 1px solid #f5c6cb;
					}

					.pum-popup-open {
						position: relative;
					}

					.pum-popup-open::after {
						content: 'Opening license connection...';
						position: absolute;
						top: 0;
						left: 0;
						right: 0;
						bottom: 0;
						background: rgba(255, 255, 255, 0.9);
						display: flex;
						align-items: center;
						justify-content: center;
						font-size: 12px;
						color: #666;
					}
				</style>
			` );
		}
	}

	/**
	 * Monitor license status changes and update UI accordingly
	 */
	function monitorLicenseStatus() {
		// Check if license status polling is available
		if ( window.PUM_Admin.LicenseStatusPolling ) {
			// Status polling will handle monitoring
			return;
		}

		// Fallback: periodic status checks
		const checkInterval = setInterval( function () {
			checkLicenseStatus();
		}, 30000 ); // Check every 30 seconds

		// Clear interval on page unload
		$( window ).on( 'beforeunload', function () {
			clearInterval( checkInterval );
		} );
	}

	/**
	 * Handle license connection success
	 * @param {Event}  event Custom event
	 * @param {Object} data  Connection data
	 */
	function handleLicenseConnected( event, data ) {
		// eslint-disable-next-line no-console
		console.log( 'License key enhancements: License connected', data );

		// Update license field
		if ( data.license_key ) {
			const $licenseField = $( '#popup_maker_pro_license_key' );
			$licenseField.val( data.license_key );
			$licenseField.trigger( 'change' );
		}

		// Show success message
		showLicenseMessage( 'License connected successfully!', 'success' );

		// Update UI state
		updateLicenseUIState( data );

		// Hide upgrade triggers since license is now connected
		hideUpgradeTriggers();
	}

	/**
	 * Handle license status changes
	 * @param {Event}  event Custom event
	 * @param {Object} data  Status data
	 */
	function handleLicenseStatusChanged( event, data ) {
		// eslint-disable-next-line no-console
		console.log( 'License key enhancements: License status changed', data );

		// Update UI based on new status
		updateLicenseUIState( data );

		// Show appropriate message
		if ( data.is_valid ) {
			showLicenseMessage( 'License activated successfully!', 'success' );
		} else if ( data.status ) {
			showLicenseMessage( `License status: ${ data.status }`, 'info' );
		}
	}

	/**
	 * Handle Pro plugin installation trigger
	 * @param {Event}  event Custom event
	 * @param {Object} data  Installation data
	 */
	function handleProInstallation( event, data ) {
		// eslint-disable-next-line no-console
		console.log(
			'License key enhancements: Pro installation triggered',
			data
		);

		// Show installation progress
		showLicenseMessage( 'Installing Popup Maker Pro...', 'info' );

		// Add loading state to license section
		addLoadingState();

		// The actual installation would be handled by the Pro Upgrader
		// This just manages the UI state
	}

	/**
	 * Handle license connection/validation errors
	 * @param {Event}  event Custom event
	 * @param {Object} data  Error data
	 */
	function handleLicenseError( event, data ) {
		// eslint-disable-next-line no-console
		console.error( 'License key enhancements: License error', data );

		// Show error message
		showLicenseMessage(
			data.error || 'License connection failed',
			'error'
		);

		// Remove loading states
		removeLoadingState();
	}

	/**
	 * Handle license field changes
	 */
	function handleLicenseFieldChange() {
		const $licenseField = $( this );
		const licenseKey = $licenseField.val().trim();

		// Show/hide upgrade triggers based on license key presence
		if ( licenseKey.length === 0 ) {
			showUpgradeTriggers();
		} else {
			hideUpgradeTriggers();
		}

		// Update license status display
		updateLicenseDisplay( licenseKey );
	}

	/**
	 * Update license UI state based on data
	 * @param {Object} data License data
	 */
	function updateLicenseUIState( data ) {
		// Update status display
		updateLicenseStatusDisplay( data );

		// Update buttons state
		updateButtonStates( data );

		// Show/hide appropriate sections
		if ( data.is_valid ) {
			hideUpgradeTriggers();
		}
	}

	/**
	 * Update license status display
	 * @param {Object} data License data
	 */
	function updateLicenseStatusDisplay( data ) {
		let $statusDisplay = $( '.pum-license-status' );

		if ( ! $statusDisplay.length ) {
			$statusDisplay = $( '<div class="pum-license-status"></div>' );
			$( '#popup_maker_pro_license_key' ).after( $statusDisplay );
		}

		// Update status class and text
		$statusDisplay.removeClass( 'valid invalid' );

		if ( data.is_valid ) {
			$statusDisplay.addClass( 'valid' );
			$statusDisplay.html( `
				<span class="pum-license-status-text">✓ License Valid</span>
				${
					data.expires
						? `<span class="pum-license-expires"> (Expires: ${ data.expires })</span>`
						: ''
				}
			` );
		} else {
			$statusDisplay.addClass( 'invalid' );
			$statusDisplay.html( `
				<span class="pum-license-status-text">✗ ${
					data.status || 'Invalid License'
				}</span>
			` );
		}
	}

	/**
	 * Update button states based on license status
	 * @param {Object} data License data
	 */
	function updateButtonStates( data ) {
		const $activateButton = $( '.pum-license-activate, .button-primary' );

		if ( $activateButton.length ) {
			if ( data.is_valid ) {
				$activateButton
					.text( 'Deactivate' )
					.removeClass( 'button-primary' )
					.addClass( 'button-secondary' );
			} else {
				$activateButton
					.text( 'Activate' )
					.removeClass( 'button-secondary' )
					.addClass( 'button-primary' );
			}
		}
	}

	/**
	 * Show upgrade triggers
	 */
	function showUpgradeTriggers() {
		$(
			'.pum-upgrade-flow-container, .pum-license-connect-container'
		).show();
	}

	/**
	 * Hide upgrade triggers
	 */
	function hideUpgradeTriggers() {
		$(
			'.pum-upgrade-flow-container, .pum-license-connect-container'
		).hide();
	}

	/**
	 * Add loading state to license section
	 */
	function addLoadingState() {
		$( '#popup_maker_pro_license_key' )
			.closest( '.pum-field' )
			.addClass( 'pum-license-loading' );
	}

	/**
	 * Remove loading state from license section
	 */
	function removeLoadingState() {
		$( '#popup_maker_pro_license_key' )
			.closest( '.pum-field' )
			.removeClass( 'pum-license-loading' );
	}

	/**
	 * Update license display based on current key
	 * @param {string} licenseKey Current license key
	 */
	function updateLicenseDisplay( licenseKey ) {
		if ( licenseKey.length === 32 ) {
			// Valid format license key
			const $statusDisplay = $( '.pum-license-status' );
			if ( ! $statusDisplay.length ) {
				$( '#popup_maker_pro_license_key' ).after(
					'<div class="pum-license-status">Ready to activate</div>'
				);
			}
		} else {
			// Remove status display for invalid format
			$( '.pum-license-status' ).remove();
		}
	}

	/**
	 * Check license status via AJAX
	 */
	function checkLicenseStatus() {
		const licenseKey = $( '#popup_maker_pro_license_key' ).val().trim();

		if ( licenseKey.length !== 32 ) {
			return;
		}

		// Use existing AJAX infrastructure to check status
		$.ajax( {
			url: window.ajaxurl || '/wp-admin/admin-ajax.php',
			type: 'POST',
			data: {
				action: 'pum_check_license_status',
				license_key: licenseKey,
				nonce: window.pum_admin_vars?.nonce || '',
			},
			success: function ( response ) {
				if ( response.success && response.data ) {
					updateLicenseUIState( response.data );
				}
			},
			error: function () {
				// Silent fail for background checks
				// eslint-disable-next-line no-console
				console.log( 'License status check failed' );
			},
		} );
	}

	/**
	 * Show license-related message
	 * @param {string} message Message text
	 * @param {string} type    Message type
	 */
	function showLicenseMessage( message, type ) {
		// Use ProUpgradeFlow messaging if available
		if (
			window.PUM_Admin.ProUpgradeFlow &&
			window.PUM_Admin.ProUpgradeFlow.showMessage
		) {
			window.PUM_Admin.ProUpgradeFlow.showMessage( message, type );
		} else {
			// Fallback to console
			// eslint-disable-next-line no-console
			console.log( `License: [${ type.toUpperCase() }] ${ message }` );
		}
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
