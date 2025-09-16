/* eslint-disable no-console */
/*******************************************************************************
 * License Status Polling - AJAX Status Updates
 * Handles REST API polling for license status updates with intelligent intervals
 * Copyright (c) 2025, Code Atlantic LLC
 ******************************************************************************/

( function ( $ ) {
	'use strict';

	window.PUM_Admin = window.PUM_Admin || {};
	window.PUM_Admin.LicenseStatusPolling = {};

	/**
	 * License Status Polling Manager
	 * Implements intelligent REST API polling for license status updates
	 */
	const LicenseStatusPolling = {
		/**
		 * Polling state
		 */
		isPolling: false,

		/**
		 * Current polling interval reference
		 */
		pollInterval: null,

		/**
		 * Current polling timeout reference
		 */
		pollTimeout: null,

		/**
		 * Polling configuration - optimized for Pro installation detection
		 */
		config: {
			// Initial polling interval (fast polling)
			initialInterval: 3000, // 3 seconds
			// Standard polling interval
			normalInterval: 5000, // 5 seconds
			// Slow polling interval
			slowInterval: 10000, // 10 seconds
			// Maximum polling duration - reduced from 5 minutes
			maxDuration: 120000, // 2 minutes
			// Maximum number of attempts - reduced
			maxAttempts: 24, // 2 minutes at 5-second intervals
			// Fast polling duration
			fastPollDuration: 20000, // 20 seconds
		},

		/**
		 * Current polling state
		 */
		state: {
			attempts: 0,
			startTime: null,
			currentInterval: null,
			lastResponse: null,
			consecutiveErrors: 0,
		},

		/**
		 * REST API configuration
		 */
		apiConfig: {
			endpoint: '',
			nonce: '',
			namespace: 'popup-maker/v2',
			route: 'license',
		},

		/**
		 * Initialize the license status polling
		 */
		init: function () {
	
			// Setup API configuration and validate it
			if ( ! this.setupApiConfig() ) {
				console.error(
					'License Status Polling: Failed to setup API configuration'
				);
				return;
			}

			this.bindEvents();

			// NOTE: Do not start polling automatically on page load.
			// Polling should only start when triggered by specific user actions.
		},

		/**
		 * Setup API configuration from global variables
		 */
		setupApiConfig: function () {
			// Always try REST API first - build the URL manually if needed
			if ( window.wpApiSettings && window.wpApiSettings.root ) {
				this.apiConfig.endpoint = `${ window.wpApiSettings.root }${ this.apiConfig.namespace }/${ this.apiConfig.route }`;
				this.apiConfig.nonce = window.wpApiSettings.nonce;
			} else {
				// Build REST API URL manually as fallback
				const restRoot = window.location.origin + '/wp-json/';
				this.apiConfig.endpoint = `${ restRoot }${ this.apiConfig.namespace }/${ this.apiConfig.route }`;
				this.apiConfig.nonce =
					window.pum_admin_vars?.rest_nonce ||
					window.pum_admin_vars?.nonce ||
					'';
			}

			// Validate we have a proper REST endpoint
			if ( ! this.apiConfig.endpoint.includes( '/wp-json/' ) ) {
				console.error(
					'License Status Polling: Invalid REST API endpoint configured'
				);
				return false;
			}

			return true;
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function () {
			// Listen for license popup closure
			$( document ).on(
				'pum_license_popup_closed',
				this.startPolling.bind( this )
			);

			// Listen for license connection success
			$( document ).on(
				'pum_license_connected',
				this.handleLicenseConnected.bind( this )
			);

			// Listen for page visibility changes
			$( document ).on(
				'visibilitychange',
				this.handleVisibilityChange.bind( this )
			);

			// Listen for window focus (user returned from upgrade popup)
			$( window ).on( 'focus', this.handleWindowFocus.bind( this ) );

			// Cleanup on page unload
			$( window ).on( 'beforeunload', this.stopPolling.bind( this ) );
		},

		/**
		 * Check if polling is needed based on current license status
		 * @return {Promise<boolean>} True if polling should start
		 */
		shouldStartPolling: async function () {
			try {
				// Get current license status from server
				const response = await $.ajax( {
					url: this.apiConfig.endpoint,
					type: 'GET',
					headers: {
						'X-WP-Nonce': this.apiConfig.nonce,
					},
					timeout: 10000,
				} );

				console.log( 'Pre-flight license check:', response );

				// Only poll if license is active but Pro is not yet installed/active
				const needsPolling =
					response.is_active &&
					( ! response.is_pro_installed || ! response.is_pro_active );

				if ( ! needsPolling ) {
					console.log( 'License Status Polling: No polling needed', {
						is_active: response.is_active,
						is_pro_installed: response.is_pro_installed,
						is_pro_active: response.is_pro_active,
					} );
				}

				return needsPolling;
			} catch ( error ) {
				console.warn(
					'License Status Polling: Pre-flight check failed, will attempt polling:',
					error
				);
				// If check fails, err on the side of polling (might be needed)
				return true;
			}
		},

		/**
		 * Start polling for license status updates
		 */
		startPolling: async function () {
			if ( this.isPolling ) {
				console.log( 'License Status Polling: Already polling' );
				return;
			}

			// Check if polling is actually needed
			const shouldPoll = await this.shouldStartPolling();
			if ( ! shouldPoll ) {
				console.log(
					'License Status Polling: Polling not needed, skipping'
				);
				return;
			}

			console.log( 'Starting license status polling' );

			// Reset state
			this.resetState();

			// Set polling flag
			this.isPolling = true;

			// Start with fast polling
			this.state.currentInterval = this.config.initialInterval;
			this.state.startTime = Date.now();

			// Perform first poll immediately
			this.performPoll();

			// Schedule regular polling
			this.scheduleNextPoll();
		},

		/**
		 * Stop polling for license status
		 */
		stopPolling: function () {
			if ( ! this.isPolling ) {
				return;
			}

			console.log( 'Stopping license status polling' );

			// Clear intervals and timeouts
			if ( this.pollInterval ) {
				clearInterval( this.pollInterval );
				this.pollInterval = null;
			}

			if ( this.pollTimeout ) {
				clearTimeout( this.pollTimeout );
				this.pollTimeout = null;
			}

			// Reset state
			this.isPolling = false;
		},

		/**
		 * Reset polling state
		 */
		resetState: function () {
			this.state = {
				attempts: 0,
				startTime: null,
				currentInterval: null,
				lastResponse: null,
				consecutiveErrors: 0,
			};
		},

		/**
		 * Schedule the next poll with intelligent interval adjustment
		 */
		scheduleNextPoll: function () {
			if ( ! this.isPolling ) {
				return;
			}

			// Check if we've exceeded maximum duration or attempts
			const elapsed = Date.now() - this.state.startTime;

			if (
				elapsed > this.config.maxDuration ||
				this.state.attempts >= this.config.maxAttempts
			) {
				console.log(
					'License Status Polling: Maximum duration/attempts reached, stopping'
				);
				this.stopPolling();
				return;
			}

			// Adjust polling interval based on elapsed time
			this.adjustPollingInterval( elapsed );

			// Schedule next poll
			this.pollTimeout = setTimeout( () => {
				this.performPoll();
				this.scheduleNextPoll();
			}, this.state.currentInterval );
		},

		/**
		 * Adjust polling interval based on elapsed time and state
		 * @param {number} elapsed Elapsed time since polling started
		 */
		adjustPollingInterval: function ( elapsed ) {
			// Fast polling for first 30 seconds
			if ( elapsed < this.config.fastPollDuration ) {
				this.state.currentInterval = this.config.initialInterval;
			}
			// Normal polling after fast period
			else if ( elapsed < this.config.fastPollDuration * 2 ) {
				this.state.currentInterval = this.config.normalInterval;
			}
			// Slow polling for extended periods
			else {
				this.state.currentInterval = this.config.slowInterval;
			}

			// Enhanced exponential backoff with jitter and cap
			if ( this.state.consecutiveErrors > 0 ) {
				const backoffFactor = Math.min(
					Math.pow( 2, this.state.consecutiveErrors ),
					64
				); // Max 64x
				const jitter = Math.random() * 0.1; // 10% jitter
				this.state.currentInterval *= backoffFactor * ( 1 + jitter );
			}

			// Absolute maximum interval cap
			this.state.currentInterval = Math.min(
				this.state.currentInterval,
				60000
			); // 1 minute max
		},

		/**
		 * Perform a single poll for license status
		 */
		performPoll: function () {
			if ( ! this.isPolling ) {
				return;
			}

			this.state.attempts++;

			console.log( `License Status Poll #${ this.state.attempts }` );

			// Prepare request data
			const requestData = this.prepareRequestData();

			// Perform AJAX request
			$.ajax( {
				url: this.apiConfig.endpoint,
				type: 'GET',
				data: requestData,
				dataType: 'json',
				timeout: 10000, // 10 second timeout
				beforeSend: this.setRequestHeaders.bind( this ),
				success: this.handlePollSuccess.bind( this ),
				error: this.handlePollError.bind( this ),
			} );
		},

		/**
		 * Prepare request data for the poll
		 * @return {Object} Request data
		 */
		prepareRequestData: function () {
			// For REST API calls, we don't need body data - authentication is via headers
			// Just add a timestamp to prevent caching
			return {
				timestamp: Date.now(),
			};
		},

		/**
		 * Set request headers
		 * @param {XMLHttpRequest} xhr XMLHttpRequest object
		 */
		setRequestHeaders: function ( xhr ) {
			// Always set the nonce header for REST API authentication
			if ( this.apiConfig.nonce ) {
				xhr.setRequestHeader( 'X-WP-Nonce', this.apiConfig.nonce );
			}

			// Set content type for JSON
			xhr.setRequestHeader( 'Content-Type', 'application/json' );
		},

		/**
		 * Handle successful poll response
		 * @param {Object} response Server response
		 */
		handlePollSuccess: function ( response ) {
			console.log( 'License status poll success:', response );

			// Reset consecutive error count
			this.state.consecutiveErrors = 0;

			// Store last response
			this.state.lastResponse = response;

			// Check if license status has changed
			if ( this.hasLicenseStatusChanged( response ) ) {
				this.handleStatusChange( response );
			}

			// Check if we should continue polling
			if ( ! this.shouldContinuePolling( response ) ) {
				this.stopPolling();
			}
		},

		/**
		 * Handle poll error
		 * @param {XMLHttpRequest} xhr    XMLHttpRequest object
		 * @param {string}         status Error status
		 * @param {string}         error  Error message
		 */
		handlePollError: function ( xhr, status, error ) {
			console.warn( 'License status poll error:', {
				status,
				error,
				xhr,
			} );

			// Increment consecutive error count
			this.state.consecutiveErrors++;

			// Stop polling after too many consecutive errors
			if ( this.state.consecutiveErrors >= 5 ) {
				console.error(
					'License Status Polling: Too many consecutive errors, stopping'
				);
				this.stopPolling();
				this.showError(
					'Unable to check license status. Please refresh the page.'
				);
			}
		},

		/**
		 * Check if license status has changed
		 * @param {Object} response Current response
		 * @return {boolean} True if status changed
		 */
		hasLicenseStatusChanged: function ( response ) {
			// If no previous response, consider it unchanged
			if ( ! this.state.lastResponse ) {
				return false;
			}

			// Compare relevant status fields
			const currentStatus = this.extractStatusData( response );
			const previousStatus = this.extractStatusData(
				this.state.lastResponse
			);

			return (
				JSON.stringify( currentStatus ) !==
				JSON.stringify( previousStatus )
			);
		},

		/**
		 * Extract status data for comparison
		 * @param {Object} response Response object
		 * @return {Object} Status data
		 */
		extractStatusData: function ( response ) {
			return {
				is_active: response.is_active || false,
				license_key: response.license_key || '',
				status: response.status || '',
				is_pro_installed: response.is_pro_installed || false,
				is_pro_active: response.is_pro_active || false,
			};
		},

		/**
		 * Handle license status change
		 * @param {Object} response Response with new status
		 */
		handleStatusChange: function ( response ) {
			console.log( 'License status changed:', response );

			// Trigger custom event
			$( document ).trigger( 'pum_license_status_changed', response );

			// Update UI based on new status
			this.updateLicenseUI( response );

			// Check if license is now valid and pro should be installed
			if ( response.is_active && ! response.is_pro_installed ) {
				this.triggerProInstallation( response );
			}
		},

		/**
		 * Update license UI based on status
		 * @param {Object} response License status response
		 */
		updateLicenseUI: function ( response ) {
			// Cache DOM elements to avoid repeated queries
			if ( ! this._cachedElements ) {
				this._cachedElements = {
					licenseField: $( '#popup_maker_pro_license_key' ),
					statusContainer: $( '.pum-license-status' ),
				};
			}

			const $licenseField = this._cachedElements.licenseField;
			const $statusContainer = this._cachedElements.statusContainer;

			// Update license key field if provided
			if ( response.license_key && $licenseField.length ) {
				$licenseField.val( response.license_key );
			}

			// Update status display
			if ( $statusContainer.length ) {
				$statusContainer.removeClass( 'valid invalid' );
				$statusContainer.addClass(
					response.is_active ? 'valid' : 'invalid'
				);
				$statusContainer
					.find( '.pum-license-status-text' )
					.text( response.status || 'Unknown' );
			}

			// Show status message
			if ( response.is_active ) {
				this.showSuccess( 'License activated successfully!' );
			} else if ( response.status ) {
				this.showError( `License issue: ${ response.status }` );
			}
		},

		/**
		 * Trigger Pro plugin installation
		 * @param {Object} response License status response
		 */
		triggerProInstallation: function ( response ) {
			console.log( 'Triggering Pro plugin installation' );

			// Show installation message
			this.showInfo(
				'Valid license detected! Installing Popup Maker Pro...'
			);

			// Trigger custom event for pro installation
			$( document ).trigger( 'pum_install_pro_plugin', response );

			// If pro upgrader is available, trigger installation
			if ( window.PUM_Admin.ProUpgrader ) {
				window.PUM_Admin.ProUpgrader.installProPlugin(
					response.license_key
				);
			}
		},

		/**
		 * Check if polling should continue
		 * @param {Object} response Current response
		 * @return {boolean} True if should continue
		 */
		shouldContinuePolling: function ( response ) {
			// Stop if license is valid and Pro is both installed AND active
			if (
				response.is_active &&
				response.is_pro_installed &&
				response.is_pro_active
			) {
				console.log(
					'License valid and Pro installed & active, stopping polling'
				);
				return false;
			}

			// Stop if license becomes invalid (shouldn't happen but safety check)
			if ( ! response.is_active ) {
				console.log( 'License is no longer active, stopping polling' );
				return false;
			}

			// Continue polling if license is valid but Pro not yet installed/active
			console.log( 'Continuing polling - Pro installation pending', {
				is_active: response.is_active,
				is_pro_installed: response.is_pro_installed,
				is_pro_active: response.is_pro_active,
			} );
			return true;
		},

		/**
		 * Handle license connection success event
		 * @param {Event}  event Custom event
		 * @param {Object} data  Event data
		 */
		handleLicenseConnected: function ( event, data ) {
			console.log( 'License connected event received:', data );

			// Stop current polling to restart with fresh state
			this.stopPolling();

			// Short delay before starting polling
			setTimeout( () => {
				this.startPolling();
			}, 1000 );
		},

		/**
		 * Handle page visibility changes
		 */
		handleVisibilityChange: function () {
			if ( ! this.isPolling ) {
				return;
			}

			if ( document.hidden ) {
				// Page hidden - slow down polling or pause
				console.log( 'Page hidden, reducing polling frequency' );
				this.state.currentInterval = this.config.slowInterval * 2;
			} else {
				// Page visible - resume normal polling
				console.log( 'Page visible, resuming normal polling' );
				this.adjustPollingInterval( Date.now() - this.state.startTime );
			}
		},

		/**
		 * Handle window focus (user returned from upgrade popup)
		 */
		handleWindowFocus: function () {
			// Only check if we're currently polling (in upgrade flow)
			if ( ! this.isPolling ) {
				return;
			}

			// Check if Pro plugin is now installed by performing immediate poll
			console.log(
				'Window focused - checking if Pro plugin was installed'
			);

			// Perform immediate status check
			this.performImmediateStatusCheck();
		},

		/**
		 * Perform immediate status check (bypass normal polling interval)
		 */
		performImmediateStatusCheck: function () {
			const requestData = this.prepareRequestData();

			$.ajax( {
				url: this.apiConfig.endpoint,
				type: 'GET',
				data: requestData,
				dataType: 'json',
				timeout: 5000,
				beforeSend: this.setRequestHeaders.bind( this ),
				success: ( response ) => {
					console.log( 'Immediate status check result:', response );

					// Check if Pro plugin is now installed and active
					if ( response.is_pro_installed && response.is_pro_active ) {
						console.log(
							'Pro plugin detected as installed and active - reloading page'
						);
						this.showSuccess(
							'Popup Maker Pro installed successfully! Reloading page...'
						);

						// Stop polling and reload page after short delay
						this.stopPolling();
						setTimeout( () => {
							window.location.reload();
						}, 2000 );
					} else if (
						response.is_pro_installed &&
						! response.is_pro_active
					) {
						console.log(
							'Pro plugin installed but not active - attempting activation'
						);
						this.showInfo(
							'Pro plugin installed but not activated. Please activate it manually.'
						);
					} else {
						console.log(
							'Pro plugin not yet installed - continuing normal polling'
						);
					}
				},
				error: ( xhr, status, error ) => {
					console.warn( 'Immediate status check failed:', {
						status,
						error,
					} );
					// Continue normal polling on error
				},
			} );
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
		 * Show info message
		 * @param {string} message Info message
		 */
		showInfo: function ( message ) {
			this.showMessage( message, 'info' );
		},

		/**
		 * Show message to user (reuse from ProUpgradeFlow)
		 * @param {string} message Message text
		 * @param {string} type    Message type (success, error, info)
		 */
		showMessage: function ( message, type = 'info' ) {
			// Delegate to ProUpgradeFlow if available
			if (
				window.PUM_Admin.ProUpgradeFlow &&
				window.PUM_Admin.ProUpgradeFlow.showMessage
			) {
				window.PUM_Admin.ProUpgradeFlow.showMessage( message, type );
				return;
			}

			// Fallback implementation
			console.log(
				`License Status: [${ type.toUpperCase() }] ${ message }`
			);
		},

		/**
		 * Cleanup method to prevent memory leaks
		 */
		cleanup: function () {
			this.stopPolling();
			this._cachedElements = null;

			// Remove window focus event listener
			$( window ).off( 'focus', this.handleWindowFocus );
		},
	};

	// Export to global namespace
	window.PUM_Admin.LicenseStatusPolling = LicenseStatusPolling;

	// Initialize on document ready
	$( function () {
		LicenseStatusPolling.init();
	} );

	// Re-initialize if settings are dynamically loaded
	$( document ).on( 'pum_init', function () {
		LicenseStatusPolling.init();
	} );
} )( jQuery );
