( function ( $ ) {
	'use strict';

	$.fn.popmake.last_open_trigger = null;
	$.fn.popmake.last_close_trigger = null;
	$.fn.popmake.conversion_trigger = null;

	var rest_enabled = !! (
		typeof pum_vars.analytics_api !== 'undefined' && pum_vars.analytics_api
	);

	// Debounce window (ms) for coalescing rapid events into one request. Events
	// that fire close together (e.g. triggered + open on the same popup) are
	// batched into a single beacon. Filterable for tuning.
	var FLUSH_DEBOUNCE_MS = window.pum.hooks.applyFilters(
		'pum.analyticsBeaconDebounce',
		400
	);

	// Pending event payloads awaiting flush, and their callbacks.
	var queue = [];
	var queueCallbacks = [];
	var flushTimer = null;

	/**
	 * Resolve the beacon endpoint URL (REST or ajax fallback).
	 *
	 * @return {string} URL or '' when none configured.
	 */
	function beaconUrl() {
		var url = rest_enabled ? pum_vars.analytics_api : pum_vars.ajaxurl;
		if ( ! url ) {
			return '';
		}
		if ( rest_enabled ) {
			url += window.pum.hooks.applyFilters(
				'pum.analyticsBeaconRoute',
				'/' + pum_vars.analytics_route + '/'
			);
		}
		return url;
	}

	/**
	 * POST a payload via sendBeacon (FormData), falling back to an image GET.
	 * Object values are JSON-encoded so they survive FormData/query encoding.
	 *
	 * @param {Object}   payload  Request body keys.
	 * @param {Function} callback Invoked on success (best effort).
	 * @return {boolean} Whether the send was dispatched.
	 */
	function dispatch( payload, callback ) {
		var url = beaconUrl();
		if ( ! url ) {
			return false;
		}

		if ( ! rest_enabled ) {
			payload.action = 'pum_analytics';
		}

		if ( 'sendBeacon' in navigator ) {
			try {
				var formData = new FormData();
				for ( var key in payload ) {
					if (
						Object.prototype.hasOwnProperty.call( payload, key )
					) {
						var value = payload[ key ];
						if ( typeof value === 'object' && value !== null ) {
							value = JSON.stringify( value );
						}
						formData.append( key, value );
					}
				}

				var success = navigator.sendBeacon( url, formData );
				if ( success && typeof callback === 'function' ) {
					callback();
				}
				return true;
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.warn(
					'sendBeacon failed, falling back to image beacon:',
					error
				);
			}
		}

		// Fallback: traditional image beacon (single-event only — GET length).
		var beacon = new Image();
		$( beacon ).on(
			'error success load done',
			typeof callback === 'function' ? callback : function () {}
		);
		beacon.src = url + '?' + $.param( payload );
		return true;
	}

	/**
	 * Flush all queued events as one batched request. sendBeacon is used so the
	 * send survives page unload, making this safe to call on exit.
	 */
	function flush() {
		if ( flushTimer ) {
			clearTimeout( flushTimer );
			flushTimer = null;
		}

		if ( ! queue.length ) {
			return;
		}

		var events = queue;
		var callbacks = queueCallbacks;
		queue = [];
		queueCallbacks = [];

		var done = function () {
			for ( var i = 0; i < callbacks.length; i++ ) {
				if ( typeof callbacks[ i ] === 'function' ) {
					callbacks[ i ]();
				}
			}
		};

		// Single event: send it flat (back-compat with the original payload
		// shape). Multiple: send as an `events` batch the endpoint unpacks.
		if ( events.length === 1 ) {
			dispatch( events[ 0 ], done );
			return;
		}

		dispatch( { events: events, _cache: +new Date() }, done );
	}

	window.PUM_Analytics = {
		/**
		 * Queue an analytics event for batched delivery.
		 *
		 * @param {Object}   data     Event payload (event, pid, eventData…).
		 * @param {Function} callback Optional success callback.
		 */
		beacon: function ( data, callback ) {
			var payload = window.pum.hooks.applyFilters(
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
			);

			queue.push( payload );
			queueCallbacks.push(
				typeof callback === 'function' ? callback : null
			);

			if ( flushTimer ) {
				clearTimeout( flushTimer );
			}
			flushTimer = setTimeout( flush, FLUSH_DEBOUNCE_MS );
		},

		/**
		 * Force-send any queued events immediately. Used on page exit.
		 */
		flush: flush,
	};

	// Guarantee delivery on page exit: pagehide covers navigation/close/bfcache,
	// and visibilitychange→hidden covers tab switches and mobile backgrounding.
	// sendBeacon is built to survive unload, so the full queue is flushed 100%.
	window.addEventListener( 'pagehide', flush );
	document.addEventListener( 'visibilitychange', function () {
		if ( document.visibilityState === 'hidden' ) {
			flush();
		}
	} );

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
				if (
					args.ajax === false ||
					args.tracked ||
					( args.phases && ! args.phases.tracking )
				) {
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
						submissionId: args.submissionId || null,
						phases: args.phases,
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
