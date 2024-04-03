/**
 * Defines the core $.popmake.cookie functions.
 * Version 1.4
 *
 * Defines the pm_cookie & pm_remove_cookie global functions.
 */
var pm_cookie, pm_cookie_json, pm_remove_cookie;
( function( $ ) {
	'use strict';

	function cookie( converter ) {
		if ( converter === undefined ) {
			converter = function() {};
		}

		function api( key, value, attributes ) {
			var result,
				expires = new Date();
			if ( typeof document === 'undefined' ) {
				return;
			}

			// Write
			if ( arguments.length > 1 ) {
				attributes = $.extend(
					{
						path: pum_vars.home_url,
					},
					api.defaults,
					attributes
				);

				switch ( typeof attributes.expires ) {
					case 'number':
						expires.setMilliseconds(
							expires.getMilliseconds() +
								attributes.expires * 864e5
						);
						attributes.expires = expires;
						break;
					case 'string':
						expires.setTime(
							$.fn.popmake.utilities.strtotime(
								'+' + attributes.expires
							) * 1000
						);
						attributes.expires = expires;
						break;
				}

				try {
					result = JSON.stringify( value );
					if ( /^[\{\[]/.test( result ) ) {
						value = result;
					}
				} catch ( e ) {}

				if ( ! converter.write ) {
					value = encodeURIComponent( String( value ) ).replace(
						/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g,
						decodeURIComponent
					);
				} else {
					value = converter.write( value, key );
				}

				key = encodeURIComponent( String( key ) );
				key = key.replace(
					/%(23|24|26|2B|5E|60|7C)/g,
					decodeURIComponent
				);
				key = key.replace( /[\(\)]/g, escape );

				return ( document.cookie = [
					key,
					'=',
					value,
					attributes.expires
						? '; expires=' + attributes.expires.toUTCString()
						: '', // use expires attribute, max-age is not supported by IE
					attributes.path ? '; path=' + attributes.path : '',
					attributes.domain ? '; domain=' + attributes.domain : '',
					attributes.secure ? '; secure' : '',
				].join( '' ) );
			}

			// Read

			if ( ! key ) {
				result = {};
			}

			// To prevent the for loop in the first place assign an empty array
			// in case there are no cookies at all. Also prevents odd result when
			// calling "get()"
			var cookies = document.cookie ? document.cookie.split( '; ' ) : [];
			var rdecode = /(%[0-9A-Z]{2})+/g;
			var i = 0;

			for ( ; i < cookies.length; i++ ) {
				var parts = cookies[ i ].split( '=' );
				var cookie = parts.slice( 1 ).join( '=' );

				if ( cookie.charAt( 0 ) === '"' ) {
					cookie = cookie.slice( 1, -1 );
				}

				try {
					var name = parts[ 0 ].replace(
						rdecode,
						decodeURIComponent
					);
					cookie = converter.read
						? converter.read( cookie, name )
						: converter( cookie, name ) ||
						  cookie.replace( rdecode, decodeURIComponent );

					if ( this.json ) {
						try {
							cookie = JSON.parse( cookie );
						} catch ( e ) {}
					}

					if ( key === name ) {
						result = cookie;
						break;
					}

					if ( ! key ) {
						result[ name ] = cookie;
					}
				} catch ( e ) {}
			}

			return result;
		}

		api.set = api;
		api.get = function( key ) {
			return api.call( api, key );
		};
		api.getJSON = function() {
			return api.apply(
				{
					json: true,
				},
				[].slice.call( arguments )
			);
		};
		api.defaults = {
			domain: pum_vars.cookie_domain ?  pum_vars.cookie_domain : '',
		};

		api.remove = function( key, attributes ) {
			// Clears keys with current path.
			api(
				key,
				'',
				$.extend( {}, attributes, {
					expires: -1,
					path: '',
				} )
			);
			// Clears sitewide keys.
			api(
				key,
				'',
				$.extend( {}, attributes, {
					expires: -1,
				} )
			);
		};

		/**
		 * Polyfill for jQuery Cookie argument arrangement.
		 *
		 * @param key
		 * @param value
		 * @param attributes || expires (deprecated)
		 * @param path (deprecated)
		 * @return {*}
		 */
		api.process = function( key, value, attributes, path ) {
			if (
				arguments.length > 3 &&
				typeof arguments[ 2 ] !== 'object' &&
				value !== undefined
			) {
				return api.apply( api, [
					key,
					value,
					{
						expires: attributes,
						path: path,
					},
				] );
			}
			return api.apply( api, [].slice.call( arguments, [ 0, 2 ] ) );
		};

		api.withConverter = $.fn.popmake.cookie;

		return api;
	}

	$.extend( $.fn.popmake, {
		cookie: cookie(),
	} );

	pm_cookie = $.pm_cookie = $.fn.popmake.cookie.process;
	pm_cookie_json = $.pm_cookie_json = $.fn.popmake.cookie.getJSON;
	pm_remove_cookie = $.pm_remove_cookie = $.fn.popmake.cookie.remove;
} )( jQuery );
