( function ( $ ) {
	'use strict';

	$.fn.popmake.last_open_trigger = null;
	$.fn.popmake.last_close_trigger = null;
	$.fn.popmake.conversion_trigger = null;

	const pumVars = window.pum_vars;
	const restEnabled = !! (
		typeof pumVars.analytics_api !== 'undefined' && pumVars.analytics_api
	);

	// Debounce window (ms) for coalescing rapid events into one request. Events
	// that fire close together (e.g. triggered + open on the same popup) are
	// batched into a single beacon. Filterable for tuning.
	const FLUSH_DEBOUNCE_MS = window.pum.hooks.applyFilters(
		'pum.analyticsBeaconDebounce',
		400
	);

	// Pending event payloads awaiting flush, and their callbacks.
	let queue = [];
	let queueCallbacks = [];
	let flushTimer = null;

	/**
	 * Resolve the beacon endpoint URL (REST or ajax fallback).
	 *
	 * @return {string} URL or '' when none configured.
	 */
	function beaconUrl() {
		let url = restEnabled ? pumVars.analytics_api : pumVars.ajaxurl;
		if ( ! url ) {
			return '';
		}
		if ( restEnabled ) {
			url += window.pum.hooks.applyFilters(
				'pum.analyticsBeaconRoute',
				'/' + pumVars.analytics_route + '/'
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
		const url = beaconUrl();
		if ( ! url ) {
			return false;
		}

		if ( ! restEnabled ) {
			payload.action = 'pum_analytics';
		}

		if ( 'sendBeacon' in navigator ) {
			try {
				const formData = new FormData();
				for ( const key in payload ) {
					if (
						Object.prototype.hasOwnProperty.call( payload, key )
					) {
						let value = payload[ key ];
						if ( typeof value === 'object' && value !== null ) {
							value = JSON.stringify( value );
						}
						formData.append( key, value );
					}
				}

				const success = navigator.sendBeacon( url, formData );
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
		const beacon = new window.Image();
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

		const events = queue;
		const callbacks = queueCallbacks;
		queue = [];
		queueCallbacks = [];

		const done = function () {
			for ( let i = 0; i < callbacks.length; i++ ) {
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

		dispatch( { events, _cache: +new Date() }, done );
	}

	window.PUM_Analytics = {
		/**
		 * Queue an analytics event for batched delivery.
		 *
		 * @param {Object}   data     Event payload (event, pid, eventData…).
		 * @param {Function} callback Optional success callback.
		 */
		beacon( data, callback ) {
			const payload = window.pum.hooks.applyFilters(
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
		flush,
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

	if ( pumVars.analytics_enabled ) {
		// Only popups from the editor should fire analytics events.
		$( document )
			/**
			 * Track opens for popups.
			 */
			.on( 'pumAfterOpen.core_analytics', '.pum', function () {
				const $popup = window.PUM.getPopup( this ),
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
				const data = {
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
						submissionId:
							'undefined' === typeof args.submissionId
								? null
								: args.submissionId,
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
				'pum.integration.form.observed',
				window.PUM.coreFormAnalyticsHandler
			);
		} );
	}
} )( window.jQuery );
