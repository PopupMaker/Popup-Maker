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
		 * @param {jQuery} $popup The popup element.
		 * @param {number} pid    The popup ID.
		 */
		processPopupLinks: function ( $popup, pid ) {
			var self = this;

			$popup.find( 'a[href]' ).each( function () {
				var $link = $( this ),
					href = $link.attr( 'href' );

				// Only process internal URLs.
				if ( self.isInternalUrl( href ) ) {
					// Start with base URL parameters.
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
				}
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
