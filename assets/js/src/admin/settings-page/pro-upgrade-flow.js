// eslint-disable-next-line eslint-comments/disable-enable-pair
/* eslint-disable no-console */
/*******************************************************************************
 * Pro Upgrade Flow - Popup Window Management
 * Handles popup window creation, monitoring, and license connection flow
 * Copyright (c) 2025, Code Atlantic LLC
 ******************************************************************************/

( function ( $ ) {
	'use strict';

	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.ProUpgradeFlow = {};

	/**
	 * Pro Upgrade Flow Manager
	 * Manages popup windows for pro license connection and upgrade flow
	 */
	const ProUpgradeFlow = {
		/**
		 * Current popup window reference
		 */
		popupWindow: null,

		/**
		 * Connection monitoring state
		 */
		isMonitoring: false,

		/**
		 * Monitoring interval reference
		 */
		monitorInterval: null,

		/**
		 * Initialization state to prevent double init
		 */
		isInitialized: false,

		/**
		 * Default popup window configuration
		 */
		popupConfig: {
			width: 580,
			height: 600,
			name: 'popup-maker-license-connect',
			features:
				'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no',
		},

		/**
		 * Initialize the pro upgrade flow
		 */
		init: function () {
			// Prevent double initialization
			if ( this.isInitialized ) {
				return;
			}

			this.isInitialized = true;
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function () {
			// Bind to license connection triggers (purchase flow removed)
			$( document ).on(
				'click',
				'.pum-license-connect-trigger',
				this.handleUpgradeClick.bind( this )
			);

			// Bind to window focus events to detect popup closure
			$( window ).on( 'focus', this.handleWindowFocus.bind( this ) );

			// Cleanup on page unload
			$( window ).on( 'beforeunload', this.cleanup.bind( this ) );
		},

		/**
		 * Handle upgrade/connect button clicks
		 * @param {Event} e Click event
		 */
		handleUpgradeClick: async function ( e ) {
			e.preventDefault();

			const $button = $( e.currentTarget );

			try {
				// Store original text and disable button to prevent re-clicks
				if ( ! $button.data( 'original-text' ) ) {
					$button.data( 'original-text', $button.html() );
				}
				$button.prop( 'disabled', true );

				// Check if this is an activate button (not install button)
				const buttonText = $button.html();
				if ( buttonText.includes( 'ACTIVATE PRO NOW' ) ) {
					// This is an activation button - call API endpoint instead of opening popup
					await this.handleProActivation( $button );
					return;
				}

				// Regular install flow - open popup
				// CRITICAL: Open popup IMMEDIATELY from user gesture to prevent blocking
				// This is the Content Control pattern - popup opens first, then we get URL
				this.openLoadingPopup();

				// Now get connection info asynchronously
				const connectInfo = await this.extractConnectInfo( $button );

				// Validate required connection info
				if ( ! this.validateConnectInfo( connectInfo ) ) {
					console.error( 'Invalid connection info:', connectInfo );
					this.showError(
						'Invalid connection parameters. Please try again.'
					);
					this.closePopup();
					return;
				}

				// Navigate the already-open popup to the final URL
				this.navigatePopupToFinalUrl( connectInfo );
			} catch ( error ) {
				console.error( 'Error getting connection info:', error );
				this.showError(
					'Failed to prepare connection. Please try again.'
				);
				this.closePopup();
				// Re-enable button on error
				$button.prop( 'disabled', false );
			}
		},

		/**
		 * Handle Pro plugin activation via REST API
		 * @param {jQuery} $button The clicked activation button
		 */
		handleProActivation: async function ( $button ) {
			try {
				// Show loading state
				this.showMessage( 'Activating Pro plugin...', 'info' );

				// Call activation API endpoint
				const response = await $.ajax( {
					url:
						window.location.origin +
						'/wp-json/popup-maker/v2/license/activate-plugin',
					type: 'POST',
					data: {},
					headers: {
						'X-WP-Nonce':
							window.pum_admin_vars?.rest_nonce ||
							window.wpApiSettings?.nonce ||
							'',
					},
				} );

				// Handle success
				if ( response.success ) {
					this.showSuccess( response.message || 'Pro plugin activated successfully!' );
					
					// Reload page after short delay to show updated state
					setTimeout( () => {
						window.location.reload();
					}, 1500 );
				} else {
					throw new Error( response.message || 'Activation failed' );
				}
			} catch ( error ) {
				console.error( 'Pro activation error:', error );
				
				// Extract error message from response
				let errorMessage = 'Failed to activate Pro plugin. Please try again.';
				if ( error.responseJSON && error.responseJSON.message ) {
					errorMessage = error.responseJSON.message;
				} else if ( error.message ) {
					errorMessage = error.message;
				}

				this.showError( errorMessage );
				
				// Re-enable button
				$button.prop( 'disabled', false );
			}
		},

		/**
		 * Extract connection information from button/context
		 * @param {jQuery} $button The clicked button
		 * @return {Promise<Object>} Connection info object with server-generated parameters
		 */
		extractConnectInfo: async function ( $button ) {
			// Get the license key for server-side parameter generation
			const licenseField = $( '#popup_maker_pro_license_key' );
			const licenseKey = licenseField.length
				? licenseField.val().trim()
				: '';

			// Get basic client context
			const clientContext = {
				product: $button.data( 'product' ) || 'popup-maker-pro',
				source: $button.data( 'source' ) || 'settings-page',
				campaign: $button.data( 'campaign' ) || 'upgrade-flow',
				existing_license: licenseKey,
			};

			try {
				// Get properly formatted connection info from REST API
				const response = await $.ajax( {
					url:
						window.location.origin +
						'/wp-json/popup-maker/v2/license/connect-info',
					type: 'GET',
					data: {
						license_key: licenseKey,
						context: JSON.stringify( clientContext ),
					},
					headers: {
						'X-WP-Nonce':
							window.pum_admin_vars?.rest_nonce ||
							window.wpApiSettings?.nonce ||
							'',
					},
				} );

				if ( response.success && response.data ) {
					console.log(
						'Got server-generated connection info:',
						response.data
					);
					return response.data;
				}
				throw new Error(
					response.data?.message || 'Failed to get connection info'
				);
			} catch ( error ) {
				console.error( 'Failed to get server connection info:', error );

				// Fallback to basic client-side info (will likely fail server validation)
				console.warn(
					'Using fallback client-side connection info - may not work properly'
				);
				return {
					site_url: window.location.origin || '',
					admin_url: window.ajaxurl
						? window.ajaxurl.replace( 'admin-ajax.php', '' )
						: '',
					return_url: window.location.href,
					product: clientContext.product,
					source: clientContext.source,
					campaign: clientContext.campaign,
					existing_license: clientContext.existing_license,
					nonce:
						window.pum_admin_vars?.nonce ||
						window.pum_settings_editor?.nonce ||
						'',
				};
			}
		},

		/**
		 * Validate connection info has required fields
		 * @param {Object} connectInfo Connection information
		 * @return {boolean} True if valid
		 */
		validateConnectInfo: function ( connectInfo ) {
			// Check for key server-generated parameters
			const required = [ 'key', 'token', 'nonce', 'full_url' ];

			return required.every( ( field ) => {
				const value = connectInfo[ field ];
				return (
					value &&
					typeof value === 'string' &&
					value.trim().length > 0
				);
			} );
		},

		/**
		 * Open the license connection popup window
		 * @param {Object} connectInfo Connection information to pass
		 */
		openLicensePopup: function ( connectInfo ) {
			// Close any existing popup
			this.closePopup();

			// Build popup URL with parameters
			const popupUrl = this.buildPopupUrl( connectInfo );

			// Calculate popup position (center of screen)
			const left = Math.round(
				( window.screen.width - this.popupConfig.width ) / 2
			);
			const top = Math.round(
				( window.screen.height - this.popupConfig.height ) / 2
			);

			// Build features string with positioning
			const features = `${ this.popupConfig.features },width=${ this.popupConfig.width },height=${ this.popupConfig.height },left=${ left },top=${ top }`;

			console.log( 'Opening license popup:', popupUrl );
			console.log( 'Popup config:', {
				name: this.popupConfig.name,
				features: features,
			} );

			// Open the popup window
			this.popupWindow = window.open(
				popupUrl,
				this.popupConfig.name,
				features
			);

			console.log( 'Popup window result:', this.popupWindow );

			if ( ! this.popupWindow ) {
				console.error(
					'Failed to open popup window - likely blocked by browser'
				);
				this.showError(
					'Popup blocked! Please allow popups for this site and try again.'
				);
				return;
			}

			// Additional check - sometimes window.open returns a window but it's not functional
			try {
				if ( this.popupWindow.closed ) {
					console.error( 'Popup window was immediately closed' );
					this.showError(
						'Popup was blocked or closed. Please allow popups for this site.'
					);
					this.popupWindow = null;
					return;
				}
			} catch ( e ) {
				console.log(
					'Cannot access popup window properties (normal for cross-origin):',
					e.message
				);
			}

			// Start monitoring the popup
			this.startPopupMonitoring();

			// Show loading state in UI
			this.showPopupOpenState();
		},

		/**
		 * Build popup URL with connection parameters
		 * @param {Object} connectInfo Connection information
		 * @return {string} Complete popup URL
		 */
		buildPopupUrl: function ( connectInfo ) {
			// If we have a pre-built full URL from the server, use it.
			if ( connectInfo.full_url ) {
				console.log(
					'Using server-generated full URL:',
					connectInfo.full_url
				);
				return connectInfo.full_url;
			}

			// Fallback to building URL from parameters.
			const baseUrl =
				connectInfo.base_url || 'https://upgrade.wppopupmaker.com';

			// URL encode the parameters
			const params = new URLSearchParams();

			// Skip special keys that aren't URL parameters.
			const skipKeys = [
				'product',
				'source',
				'campaign',
				'base_url',
				'full_url',
				'back_url',
				'existing_license',
			];

			Object.keys( connectInfo ).forEach( ( key ) => {
				if ( connectInfo[ key ] && ! skipKeys.includes( key ) ) {
					params.append( key, connectInfo[ key ] );
				}
			} );

			const fallbackUrl = `${ baseUrl }?${ params.toString() }`;
			console.log( 'Built fallback URL from parameters:', fallbackUrl );
			return fallbackUrl;
		},

		/**
		 * Open loading popup immediately from user gesture
		 * This prevents popup blockers by opening the window directly from the click event
		 */
		openLoadingPopup: function () {
			// Close any existing popup (but don't stop monitoring yet)
			if ( this.popupWindow && ! this.popupWindow.closed ) {
				this.popupWindow.close();
			}
			this.popupWindow = null;

			// Calculate popup position (center of screen)
			const left = Math.round(
				( window.screen.width - this.popupConfig.width ) / 2
			);
			const top = Math.round(
				( window.screen.height - this.popupConfig.height ) / 2
			);

			// Build features string with positioning
			const features = `${ this.popupConfig.features },width=${ this.popupConfig.width },height=${ this.popupConfig.height },left=${ left },top=${ top }`;

			// Create a loading page URL - use data URI for immediate loading
			const loadingUrl =
				'data:text/html;charset=utf-8,' +
				encodeURIComponent( `
				<!DOCTYPE html>
				<html>
				<head>
					<title>Connecting to Popup Maker Pro</title>
					<style>
						body {
							font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
							display: flex;
							justify-content: center;
							align-items: center;
							height: 100vh;
							margin: 0;
							background: #f1f1f1;
							color: #333;
						}
						.loading { text-align: center; }
						.spinner {
							border: 4px solid #e1e1e1;
							border-left: 4px solid #0073aa;
							border-radius: 50%;
							width: 40px;
							height: 40px;
							animation: spin 1s linear infinite;
							margin: 20px auto;
						}
						@keyframes spin {
							0% { transform: rotate(0deg); }
							100% { transform: rotate(360deg); }
						}
					</style>
				</head>
				<body>
					<div class="loading">
						<div class="spinner"></div>
						<h2>Connecting to Popup Maker Pro</h2>
						<p>Please wait while we prepare your upgrade...</p>
					</div>
				</body>
				</html>
			` );

			console.log(
				'Opening loading popup immediately from user gesture'
			);

			// Open the popup window with loading content
			this.popupWindow = window.open(
				loadingUrl,
				this.popupConfig.name,
				features
			);

			if ( ! this.popupWindow ) {
				console.error(
					'Failed to open popup window - likely blocked by browser'
				);
				this.showError(
					'Popup blocked! Please allow popups for this site and try again.'
				);
				return false;
			}

			// Additional check - sometimes window.open returns a window but it's not functional
			try {
				if ( this.popupWindow.closed ) {
					console.error( 'Popup window was immediately closed' );
					this.showError(
						'Popup was blocked or closed. Please allow popups for this site.'
					);
					this.popupWindow = null;
					return false;
				}
			} catch ( e ) {
				console.log(
					'Cannot access popup window properties (normal for cross-origin):',
					e.message
				);
			}

			// Start monitoring the popup
			this.startPopupMonitoring();

			// Show loading state in UI
			this.showPopupOpenState();

			return true;
		},

		/**
		 * Navigate existing popup to final upgrade URL
		 * @param {Object} connectInfo Connection information with final URL
		 */
		navigatePopupToFinalUrl: function ( connectInfo ) {
			if ( ! this.popupWindow || this.popupWindow.closed ) {
				console.error( 'Cannot navigate popup - window not available' );
				this.showError( 'Popup window was closed. Please try again.' );
				return;
			}

			// Build popup URL with parameters
			const popupUrl = this.buildPopupUrl( connectInfo );

			console.log( 'Navigating existing popup to final URL:', popupUrl );

			try {
				// Navigate the existing popup to the final URL
				this.popupWindow.location.href = popupUrl;
			} catch ( e ) {
				console.warn(
					'Could not navigate popup directly, attempting to open new window:',
					e.message
				);

				// Fallback: Close current popup and open new one
				this.closePopup();
				this.openLicensePopup( connectInfo );
			}
		},

		/**
		 * Start monitoring popup window state
		 */
		startPopupMonitoring: function () {
			if ( this.isMonitoring ) {
				return;
			}

			this.isMonitoring = true;

			// Optimized popup monitoring with adaptive intervals
			this.monitoringStartTime = Date.now();
			this.scheduleNextCheck();

			console.log( 'Started popup monitoring' );
		},

		/**
		 * Stop monitoring popup window
		 */
		/**
		 * Schedule next popup state check with adaptive interval
		 */
		scheduleNextCheck: function () {
			if ( ! this.isMonitoring ) {
				return;
			}

			const elapsed = Date.now() - this.monitoringStartTime;
			let interval = 500; // Start with 500ms

			// Increase interval over time to reduce resource usage
			if ( elapsed > 30000 ) {
				// After 30 seconds
				interval = 2000; // 2 seconds
			} else if ( elapsed > 10000 ) {
				// After 10 seconds
				interval = 1000; // 1 second
			}

			this.monitorTimeout = setTimeout( () => {
				this.checkPopupState();
				this.scheduleNextCheck(); // Schedule next check
			}, interval );
		},

		stopPopupMonitoring: function () {
			if ( ! this.isMonitoring ) {
				return;
			}

			this.isMonitoring = false;

			if ( this.monitorInterval ) {
				clearInterval( this.monitorInterval );
				this.monitorInterval = null;
			}

			if ( this.monitorTimeout ) {
				clearTimeout( this.monitorTimeout );
				this.monitorTimeout = null;
			}

			console.log( 'Stopped popup monitoring' );
		},

		/**
		 * Check current popup window state
		 */
		checkPopupState: function () {
			if ( ! this.popupWindow ) {
				console.log( 'checkPopupState: popupWindow is null' );
				this.handlePopupClosed();
				return;
			}

			// Check if popup was closed
			try {
				if ( this.popupWindow.closed ) {
					console.log( 'checkPopupState: popup window was closed' );
					this.handlePopupClosed();
					return;
				}
			} catch ( e ) {
				console.log(
					'checkPopupState: Error checking popup.closed, treating as closed:',
					e.message
				);
				this.handlePopupClosed();
				return;
			}

			// Check if popup completed successfully (could listen for postMessage)
			// This would be enhanced with actual communication from the popup
			try {
				// Attempt to access popup location for same-origin detection
				const popupUrl = this.popupWindow.location.href;

				// If we can access location and it's back to our domain,
				// the flow may have completed
				if ( popupUrl.indexOf( window.location.origin ) === 0 ) {
					// Look for success parameters in URL
					const urlParams = new URLSearchParams(
						this.popupWindow.location.search
					);

					if ( urlParams.get( 'pum_license_connected' ) === '1' ) {
						this.handleConnectionSuccess( urlParams );
						return;
					}

					if ( urlParams.get( 'pum_license_error' ) ) {
						this.handleConnectionError(
							urlParams.get( 'pum_license_error' )
						);
					}
				}
			} catch ( e ) {
				// Cross-origin access blocked - this is normal during external flow
				// Continue monitoring
			}
		},

		/**
		 * Handle popup window closure
		 */
		handlePopupClosed: function () {
			console.log( 'License popup closed' );

			this.stopPopupMonitoring();
			this.popupWindow = null;
			this.hidePopupOpenState();

			// Re-enable install pro buttons when popup closes and restore original text
			$( '.pum-install-pro-button, .pum-license-connect-trigger' ).each( function() {
				const $btn = $( this );
				$btn.prop( 'disabled', false );
				
				// Restore original text if stored
				const originalText = $btn.data( 'original-text' );
				if ( originalText ) {
					$btn.html( originalText );
				}
			} );

			// Trigger custom event
			$( document ).trigger( 'pum_license_popup_closed' );

			// Start polling for license status changes
			if ( window.PUM_Admin.LicenseStatusPolling ) {
				window.PUM_Admin.LicenseStatusPolling.startPolling();
			}
		},

		/**
		 * Handle successful license connection
		 * @param {URLSearchParams} urlParams Success parameters
		 */
		handleConnectionSuccess: function ( urlParams ) {
			console.log( 'License connection successful' );

			const licenseKey = urlParams.get( 'license_key' );
			const licenseStatus = urlParams.get( 'license_status' );

			// Close popup
			this.closePopup();

			// Re-enable buttons after successful connection and restore original text
			$( '.pum-install-pro-button, .pum-license-connect-trigger' ).each( function() {
				const $btn = $( this );
				$btn.prop( 'disabled', false );
				
				// Restore original text if stored
				const originalText = $btn.data( 'original-text' );
				if ( originalText ) {
					$btn.html( originalText );
				}
			} );

			// Update license field if we have a key
			if ( licenseKey ) {
				const $licenseField = $( '#popup_maker_pro_license_key' );
				if ( $licenseField.length ) {
					$licenseField.val( licenseKey );
					$licenseField.trigger( 'change' );
				}
			}

			// Show success message
			this.showSuccess( 'License connected successfully!' );

			// Trigger custom event with data
			$( document ).trigger( 'pum_license_connected', {
				license_key: licenseKey,
				license_status: licenseStatus,
			} );

			// Trigger page reload after short delay to show updated state
			setTimeout( () => {
				window.location.reload();
			}, 1500 );
		},

		/**
		 * Handle license connection error
		 * @param {string} errorMessage Error message
		 */
		handleConnectionError: function ( errorMessage ) {
			console.error( 'License connection error:', errorMessage );

			// Close popup
			this.closePopup();

			// Re-enable buttons after error and restore original text
			$( '.pum-install-pro-button, .pum-license-connect-trigger' ).each( function() {
				const $btn = $( this );
				$btn.prop( 'disabled', false );
				
				// Restore original text if stored
				const originalText = $btn.data( 'original-text' );
				if ( originalText ) {
					$btn.html( originalText );
				}
			} );

			// Show error message
			this.showError( `Connection failed: ${ errorMessage }` );

			// Trigger custom event
			$( document ).trigger( 'pum_license_connection_error', {
				error: errorMessage,
			} );
		},

		/**
		 * Handle main window focus (potential popup closure detection)
		 */
		handleWindowFocus: function () {
			// Small delay to allow popup state to settle
			setTimeout( () => {
				if (
					this.isMonitoring &&
					this.popupWindow &&
					this.popupWindow.closed
				) {
					this.handlePopupClosed();
				}
			}, 100 );
		},

		/**
		 * Close the popup window
		 */
		closePopup: function () {
			if ( this.popupWindow && ! this.popupWindow.closed ) {
				this.popupWindow.close();
			}

			this.stopPopupMonitoring();
			this.popupWindow = null;
			this.hidePopupOpenState();
		},

		/**
		 * Show popup open state in UI
		 */
		showPopupOpenState: function () {
			// Add loading state to upgrade buttons
			$(
				'.pum-pro-upgrade-trigger, .pum-license-connect-trigger'
			).addClass( 'pum-popup-open' );

			// Show loading message
			this.showMessage( 'Opening license connection window...', 'info' );
		},

		/**
		 * Hide popup open state in UI
		 */
		hidePopupOpenState: function () {
			// Remove loading state
			$(
				'.pum-pro-upgrade-trigger, .pum-license-connect-trigger'
			).removeClass( 'pum-popup-open' );
		},

		/**
		 * Show success message
		 * @param {string} message Success message
		 */
		showSuccess: function ( message ) {
			this.showMessage( message, 'success' );
		},

		/**
		 * Show error message
		 * @param {string} message Error message
		 */
		showError: function ( message ) {
			this.showMessage( message, 'error' );
		},

		/**
		 * Show message to user
		 * @param {string} message Message text
		 * @param {string} type    Message type (success, error, info)
		 */
		showMessage: function ( message, type = 'info' ) {
			// Create or update message container
			let $messageContainer = $( '#pum-upgrade-flow-messages' );

			if ( ! $messageContainer.length ) {
				$messageContainer = $(
					'<div id="pum-upgrade-flow-messages" class="pum-upgrade-messages"></div>'
				);
				$( '#pum-settings-container' ).prepend( $messageContainer );
			}

			// Clear previous messages
			$messageContainer.empty();

			// Add new message
			const $message = $( `
				<div class="notice notice-${ type } is-dismissible">
					<p>${ message }</p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text">Dismiss this notice.</span>
					</button>
				</div>
			` );

			$messageContainer.append( $message );

			// Auto-hide after delay for non-error messages
			if ( type !== 'error' ) {
				setTimeout( () => {
					$message.fadeOut( 300, function () {
						$( this ).remove();
					} );
				}, 3000 );
			}

			// Bind dismiss button
			$message.find( '.notice-dismiss' ).on( 'click', function () {
				$message.fadeOut( 300, function () {
					$( this ).remove();
				} );
			} );
		},

		/**
		 * Cleanup on page unload
		 */
		cleanup: function () {
			this.closePopup();
		},
	};

	// Export to global namespace
	window.PUM_Admin.ProUpgradeFlow = ProUpgradeFlow;

	// Initialize on document ready
	$( function () {
		ProUpgradeFlow.init();
	} );

	// Re-initialize if settings are dynamically loaded
	$( document ).on( 'pum_init', function () {
		ProUpgradeFlow.init();
	} );

	// Cleanup on page unload to prevent memory leaks
	$( window ).on( 'beforeunload unload', function () {
		if ( window.PUM_Admin && window.PUM_Admin.ProUpgradeFlow ) {
			window.PUM_Admin.ProUpgradeFlow.cleanup();
		}
		if ( window.PUM_Admin && window.PUM_Admin.LicenseStatusPolling ) {
			window.PUM_Admin.LicenseStatusPolling.cleanup();
		}
	} );
} )( jQuery );
