/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
( function ( $ ) {
	'use strict';

	// Ensure PUM exists globally.
	window.PUM = window.PUM || {};

	/**
	 * URL Tracking functionality for Popup Maker.
	 *
	 * Handles:
	 * - Appending pid (popup ID) to internal links within popups
	 * - Firing click conversion beacons for external/special links (mailto, tel, etc.)
	 */
	window.PUM_URLTracking = {
		/**
		 * Initialize the URL tracking system.
		 */
		init: function () {
			this.addPopupOpenTracking();
		},

		/**
		 * Add tracking when popups open.
		 */
		addPopupOpenTracking: function () {
			var self = this;

			// Listen for popup open events.
			$( document ).on( 'pumAfterOpen.url_tracking', '.pum', function () {
				var $popup = window.PUM.getPopup( this ),
					settings = $popup.popmake( 'getSettings' ),
					pid = parseInt( settings.id, 10 );

				// Only process valid popup IDs.
				if ( pid > 0 ) {
					// Process all links within this popup.
					self.processPopupLinks( $popup, pid );
				}
			} );
		},

		/**
		 * Process all links within a popup to add tracking parameters.
		 *
		 * Internal links get ?pid= appended (tracked via server redirect).
		 * External/special links get click handlers for beacon tracking.
		 *
		 * @param {jQuery} $popup The popup element.
		 * @param {number} pid    The popup ID.
		 */
		processPopupLinks: function ( $popup, pid ) {
			var self = this;

			$popup.find( 'a[href]' ).each( function () {
				var $link = $( this ),
					href = $link.attr( 'href' );

				if ( self.isInternalUrl( href ) ) {
					// Internal URLs: Append PID parameter (tracked via server redirect).
					var urlParams = { pid: pid };

					// Allow extensions to add additional parameters.
					if ( window.PUM && window.PUM.hooks ) {
						urlParams = window.PUM.hooks.applyFilters(
							'popupMaker.popup.linkUrlParams',
							urlParams,
							$popup,
							$link
						);
					}

					var newHref = self.appendParamsToUrl( href, urlParams );
					$link.attr( 'href', newHref );
				} else if ( self.shouldTrackClick( href ) ) {
					// External/special links: Attach click handler for beacon tracking.
					self.attachClickTracking( $link, pid, href );
				}
			} );
		},

		/**
		 * Determine if a link should have click tracking attached.
		 *
		 * @param {string} url The URL to check.
		 * @return {boolean} True if click should be tracked.
		 */
		shouldTrackClick: function ( url ) {
			// Skip empty URLs.
			if ( ! url ) {
				return false;
			}

			// Skip links already tracked via CTA system.
			if ( url.indexOf( 'cta=' ) !== -1 ) {
				return false;
			}

			return true;
		},

		/**
		 * Get the link type for analytics segmentation.
		 *
		 * @param {string} url The URL to categorize.
		 * @return {string} Link type: 'external', 'mailto', 'tel', or 'other'.
		 */
		getLinkType: function ( url ) {
			if ( url.indexOf( 'mailto:' ) === 0 ) {
				return 'mailto';
			}
			if ( url.indexOf( 'tel:' ) === 0 ) {
				return 'tel';
			}
			if ( url.indexOf( 'javascript:' ) === 0 ) {
				return 'javascript';
			}
			if ( url === '#' || url.indexOf( '#' ) === 0 ) {
				return 'anchor';
			}
			if ( url.indexOf( 'http' ) === 0 || url.indexOf( '//' ) === 0 ) {
				return 'external';
			}
			return 'other';
		},

		/**
		 * Attach click tracking to a link element.
		 *
		 * Fires a conversion beacon when the link is clicked.
		 *
		 * @param {jQuery} $link The link element.
		 * @param {number} pid   The popup ID.
		 * @param {string} href  The link URL.
		 */
		attachClickTracking: function ( $link, pid, href ) {
			var self = this;

			// Prevent duplicate handlers.
			if ( $link.data( 'pum-click-tracked' ) ) {
				return;
			}
			$link.data( 'pum-click-tracked', true );

			$link.on( 'click.pum_tracking', function () {
				// Only track if analytics is available and enabled.
				if (
					! window.PUM_Analytics ||
					! window.pum_vars ||
					! window.pum_vars.analytics_enabled
				) {
					return;
				}

				var data = {
					pid: pid,
					event: 'conversion',
					eventData: {
						type: 'link_click',
						url: href,
						linkType: self.getLinkType( href ),
					},
				};

				// Allow extensions to modify click tracking data.
				if ( window.PUM && window.PUM.hooks ) {
					data = window.PUM.hooks.applyFilters(
						'popupMaker.popup.linkClickData',
						data,
						$link
					);
				}

				// Fire beacon (sendBeacon queues even during navigation).
				window.PUM_Analytics.beacon( data );
			} );
		},

		/**
		 * Check if URL is internal to the current site.
		 *
		 * @param {string} url The URL to check.
		 * @return {boolean} True if internal, false otherwise.
		 */
		isInternalUrl: function ( url ) {
			if ( ! url || url === '#' || url.indexOf( '#' ) === 0 ) {
				return false;
			}

			// Skip non-HTTP protocols (mailto:, tel:, javascript:, etc.).
			if (
				/^[a-z][a-z0-9+.-]*:/i.test( url ) &&
				! /^https?:/i.test( url )
			) {
				return false;
			}

			// Handle relative URLs.
			if ( url.indexOf( '/' ) === 0 && url.indexOf( '//' ) !== 0 ) {
				return true;
			}

			// Handle protocol-relative URLs.
			if ( url.indexOf( '//' ) === 0 ) {
				url = window.location.protocol + url;
			}

			// Handle absolute URLs.
			if ( url.indexOf( 'http' ) === 0 ) {
				try {
					var urlObj = new URL( url );
					return urlObj.hostname === window.location.hostname;
				} catch ( e ) {
					return false;
				}
			}

			// Assume relative URL.
			return true;
		},

		/**
		 * Append multiple parameters to URL.
		 *
		 * @param {string} url    The URL to modify.
		 * @param {Object} params Object containing key-value pairs to append.
		 * @return {string} The modified URL.
		 */
		appendParamsToUrl: function ( url, params ) {
			try {
				var urlObj = new URL( url, window.location.origin );

				// Add each parameter to the URL.
				for ( var key in params ) {
					if (
						Object.prototype.hasOwnProperty.call( params, key ) &&
						params[ key ]
					) {
						urlObj.searchParams.set( key, params[ key ] );
					}
				}

				return urlObj.toString();
			} catch ( e ) {
				// Fallback for malformed URLs.
				var queryString = '';
				for ( var paramKey in params ) {
					if (
						Object.prototype.hasOwnProperty.call(
							params,
							paramKey
						) &&
						params[ paramKey ]
					) {
						if ( queryString.length > 0 ) {
							queryString += '&';
						}
						queryString +=
							encodeURIComponent( paramKey ) +
							'=' +
							encodeURIComponent( params[ paramKey ] );
					}
				}

				if ( queryString.length > 0 ) {
					var separator = url.indexOf( '?' ) !== -1 ? '&' : '?';
					return url + separator + queryString;
				}

				return url;
			}
		},
	};

	// Initialize on DOM ready.
	$( function () {
		window.PUM_URLTracking.init();
	} );
} )( jQuery );
