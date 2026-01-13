( function ( $ ) {
	'use strict';

	$.fn.popmake.last_open_trigger = null;
	$.fn.popmake.last_close_trigger = null;
	$.fn.popmake.conversion_trigger = null;

	var rest_enabled = !! (
		typeof pum_vars.analytics_api !== 'undefined' && pum_vars.analytics_api
	);

	window.PUM_Analytics = {
		beacon: function ( data, callback ) {
			var url = rest_enabled ? pum_vars.analytics_api : pum_vars.ajaxurl,
				opts = {
					route: window.pum.hooks.applyFilters(
						'pum.analyticsBeaconRoute',
						'/' + pum_vars.analytics_route + '/'
					),
					data: window.pum.hooks.applyFilters(
						'pum.AnalyticsBeaconData',
						$.extend(
							true,
							{
								event: 'open',
								pid: null,
								_cache: +new Date(),
							},
							data
						)
					),
					callback:
						typeof callback === 'function'
							? callback
							: function () {},
				};

			if ( ! rest_enabled ) {
				opts.data.action = 'pum_analytics';
			} else {
				url += opts.route;
			}

			// Create a beacon if a url is provided
			if ( url ) {
				// Use modern sendBeacon API when available (more reliable for page exit events)
				if ( 'sendBeacon' in navigator ) {
					try {
						// Convert data to FormData for sendBeacon
						var formData = new FormData();
						for ( var key in opts.data ) {
							if (
								Object.prototype.hasOwnProperty.call(
									opts.data,
									key
								)
							) {
								// Check if the value is an object and serialize it
								var value = opts.data[ key ];
								if (
									typeof value === 'object' &&
									value !== null
								) {
									value = JSON.stringify( value );
								}
								formData.append( key, value );
							}
						}

						// Send beacon - returns true if queued successfully
						var success = navigator.sendBeacon( url, formData );

						// Call callback if provided
						if ( success ) {
							opts.callback();
						}

						return;
					} catch ( error ) {
						// Fall back to image beacon if sendBeacon fails
						console.warn(
							'sendBeacon failed, falling back to image beacon:',
							error
						);
					}
				}

				// Fallback: Use traditional image beacon method
				var beacon = new Image();
				// Attach the event handlers to the image object
				$( beacon ).on( 'error success load done', opts.callback );
				// Attach the src for the script call
				beacon.src = url + '?' + $.param( opts.data );
			}
		},
	};

	if ( pum_vars.analytics_enabled ) {
		// Only popups from the editor should fire analytics events.
		$( document )
			/**
			 * Track opens for popups.
			 */
			.on( 'pumAfterOpen.core_analytics', '.pum', function () {
				var $popup = window.PUM.getPopup( this ),
					data = {
						pid:
							parseInt(
								$popup.popmake( 'getSettings' ).id,
								10
							) || null,
					};

				// Shortcode popups use negative numbers, and single-popup (preview mode) shouldn't be tracked.
				if (
					data.pid > 0 &&
					! $( 'body' ).hasClass( 'single-popup' )
				) {
					window.PUM_Analytics.beacon( data );
				}
			} );
		/**
		 * Track form submission conversions
		 */
		$( function () {
			// Store reference so Pro can unhook it
			window.PUM.coreFormAnalyticsHandler = function ( form, args ) {
				// If the submission has already been counted in the backend, we can bail early.
				if ( args.ajax === false ) {
					return;
				}

				// If no popup is included in the args, we can bail early since we only record conversions within popups.
				if ( args.popup.length === 0 ) {
					return;
				}
				var data = {
					pid:
						parseInt(
							args.popup.popmake( 'getSettings' ).id,
							10
						) || null,
					event: 'conversion',
					eventData: {
						type: 'form_submission',
						formProvider: args.formProvider || null,
						formId: args.formId || null,
						formKey: args.formKey || null,
						formInstanceId: args.formInstanceId || null,
					},
				};

				// Shortcode popups use negative numbers, and single-popup (preview mode) shouldn't be tracked.
				if (
					data.pid > 0 &&
					! $( 'body' ).hasClass( 'single-popup' )
				) {
					window.PUM_Analytics.beacon( data );
				}
			};

			window.PUM.hooks.addAction(
				'pum.integration.form.success',
				window.PUM.coreFormAnalyticsHandler
			);
		} );
	}
} )( jQuery );
