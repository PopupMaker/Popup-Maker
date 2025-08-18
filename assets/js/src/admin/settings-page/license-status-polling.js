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
		 * Polling configuration
		 */
		config: {
			// Initial polling interval (fast polling)
			initialInterval: 2000, // 2 seconds
			// Standard polling interval
			normalInterval: 5000, // 5 seconds
			// Slow polling interval
			slowInterval: 10000, // 10 seconds
			// Maximum polling duration
			maxDuration: 300000, // 5 minutes
			// Maximum number of attempts
			maxAttempts: 60, // 5 minutes at 5-second intervals
			// Fast polling duration
			fastPollDuration: 30000, // 30 seconds
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
			console.log( 'Initializing License Status Polling' );
			this.setupApiConfig();
			this.bindEvents();
			
			// Check if we have valid AJAX configuration
			if ( ! this.apiConfig.endpoint || ! this.apiConfig.nonce ) {
				console.warn( 'License Status Polling: Invalid AJAX configuration, disabling polling' );
				return;
			}
		},

		/**
		 * Setup API configuration from global variables
		 */
		setupApiConfig: function () {
			console.log( 'Setting up API config. Available globals:', {
				ajaxurl: window.ajaxurl,
				pum_admin_vars: window.pum_admin_vars,
				pum_settings_editor: window.pum_settings_editor
			} );
			
			// Use admin AJAX for license status checking
			if ( window.ajaxurl ) {
				this.apiConfig.endpoint = window.ajaxurl;
				this.apiConfig.nonce = window.pum_admin_vars?.nonce || window.pum_settings_editor?.nonce || '';
				console.log( 'Using ajaxurl:', this.apiConfig.endpoint );
				console.log( 'Using nonce:', this.apiConfig.nonce );
			} else if ( window.pum_admin_vars && window.pum_admin_vars.ajax_url ) {
				// Fallback to admin AJAX from vars
				this.apiConfig.endpoint = window.pum_admin_vars.ajax_url;
				this.apiConfig.nonce = window.pum_admin_vars.nonce;
				console.log( 'Using fallback ajaxurl:', this.apiConfig.endpoint );
				console.log( 'Using fallback nonce:', this.apiConfig.nonce );
			} else {
				console.warn(
					'License Status Polling: No AJAX URL available'
				);
			}
			
			// Final validation - if we don't have valid configuration, clear everything
			if ( ! this.apiConfig.endpoint || ! this.apiConfig.nonce || this.apiConfig.nonce.trim() === '' ) {
				console.warn( 'License Status Polling: Invalid AJAX configuration detected, disabling polling entirely' );
				this.apiConfig.endpoint = '';
				this.apiConfig.nonce = '';
			}
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
		 * Start polling for license status updates
		 */
		startPolling: function () {
			if ( this.isPolling ) {
				console.log( 'License Status Polling: Already polling' );
				return;
			}

			// Skip if no valid configuration
			if ( ! this.apiConfig.endpoint || ! this.apiConfig.nonce ) {
				console.log( 'License Status Polling: Skipping polling - no valid AJAX configuration' );
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
				type: 'POST',
				data: requestData,
				dataType: 'json',
				timeout: 10000, // 10 second timeout
				success: this.handlePollSuccess.bind( this ),
				error: this.handlePollError.bind( this ),
			} );
		},

		/**
		 * Prepare request data for the poll
		 * @return {Object} Request data
		 */
		prepareRequestData: function () {
			// Always use admin AJAX format
			return {
				action: 'pum_check_license_status',
				nonce: this.apiConfig.nonce,
				timestamp: Date.now(),
			};
		},

		/**
		 * Set request headers (not needed for admin AJAX)
		 * @param {XMLHttpRequest} xhr XMLHttpRequest object
		 */
		setRequestHeaders: function ( xhr ) {
			// Not needed for admin AJAX requests
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
				is_valid: response.is_valid || false,
				license_key: response.license_key || '',
				status: response.status || '',
				expires: response.expires || '',
				pro_installed: response.pro_installed || false,
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
			if ( response.is_valid && ! response.pro_installed ) {
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
					response.is_valid ? 'valid' : 'invalid'
				);
				$statusContainer
					.find( '.pum-license-status-text' )
					.text( response.status || 'Unknown' );
			}

			// Show status message
			if ( response.is_valid ) {
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
			// Stop if license is valid and pro is installed
			if ( response.is_valid && response.pro_installed ) {
				console.log(
					'License valid and Pro installed, stopping polling'
				);
				return false;
			}

			// Continue polling if license is valid but pro not installed
			// Continue polling if license is not yet valid
			return true;
		},

		/**
		 * Handle license connection success event
		 * @param {Event}  event Custom event
		 * @param {Object} data  Event data
		 */
		handleLicenseConnected: function ( event, data ) {
			console.log( 'License connected event received:', data );

			// Skip if no valid configuration
			if ( ! this.apiConfig.endpoint || ! this.apiConfig.nonce ) {
				console.log( 'License Status Polling: Skipping connected event handling - no valid AJAX configuration' );
				return;
			}

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
			// Skip if no valid configuration
			if ( ! this.apiConfig.endpoint || ! this.apiConfig.nonce ) {
				console.log( 'License Status Polling: Skipping status check - no valid AJAX configuration' );
				return;
			}
			
			const requestData = this.prepareRequestData();

			$.ajax( {
				url: this.apiConfig.endpoint,
				type: 'POST',
				data: requestData,
				dataType: 'json',
				timeout: 5000,
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
