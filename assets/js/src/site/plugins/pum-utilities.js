/**
 * Defines the core $.popmake.utilites methods.
 * Version 1.4
 */
( function ( $, document, undefined ) {
	'use strict';

	var inputTypes =
			'color,date,datetime,datetime-local,email,hidden,month,number,password,range,search,tel,text,time,url,week'.split(
				','
			),
		inputNodes = 'select,textarea'.split( ',' ),
		rName = /\[([^\]]*)\]/g;

	/**
	 * Polyfill for IE < 9
	 */
	if ( ! Array.prototype.indexOf ) {
		Array.prototype.indexOf = function ( searchElement /*, fromIndex */ ) {
			'use strict';

			if ( this === void 0 || this === null ) throw new TypeError();

			var t = Object( this );
			var len = t.length >>> 0;
			if ( len === 0 ) return -1;

			var n = 0;
			if ( arguments.length > 0 ) {
				n = Number( arguments[ 1 ] );
				if ( n !== n )
					// shortcut for verifying if it's NaN
					n = 0;
				else if ( n !== 0 && n !== 1 / 0 && n !== -( 1 / 0 ) )
					n = ( n > 0 || -1 ) * Math.floor( Math.abs( n ) );
			}

			if ( n >= len ) return -1;

			var k = n >= 0 ? n : Math.max( len - Math.abs( n ), 0 );

			for ( ; k < len; k++ ) {
				if ( k in t && t[ k ] === searchElement ) return k;
			}
			return -1;
		};
	}

	function storeValue( container, parsedName, value ) {
		var part = parsedName[ 0 ];

		if ( parsedName.length > 1 ) {
			if ( ! container[ part ] ) {
				// If the next part is eq to '' it means we are processing complex name (i.e. `some[]`)
				// for this case we need to use Array instead of an Object for the index increment purpose
				container[ part ] = parsedName[ 1 ] ? {} : [];
			}
			storeValue( container[ part ], parsedName.slice( 1 ), value );
		} else {
			// Increment Array index for `some[]` case
			if ( ! part ) {
				part = container.length;
			}

			container[ part ] = value;
		}
	}

	// Ensure PUM exists globally
	window.PUM = window.PUM || {};

	// Define utilities on both jQuery and PUM global
	$.fn.popmake.utilities = window.PUM.utilities = {
		scrollTo: function ( target, callback ) {
			var $target = $( target ) || $();

			if ( ! $target.length ) {
				return;
			}

			$( 'html, body' ).animate(
				{
					scrollTop: $target.offset().top - 100,
				},
				1000,
				'swing',
				function () {
					// Find the first :input that isn't a button or hidden type.
					var $input = $target
						.find(
							':input:not([type="button"]):not([type="hidden"]):not(button)'
						)
						.eq( 0 );

					if ( $input.hasClass( 'wp-editor-area' ) ) {
						tinyMCE.execCommand(
							'mceFocus',
							false,
							$input.attr( 'id' )
						);
					} else {
						$input.focus();
					}

					if ( typeof callback === 'function' ) {
						callback();
					}
				}
			);
		},
		/**
         * In Array tester function. Similar to PHP's in_array()

         * @param needle
         * @param array
         * @returns {boolean}
         */
		inArray: function ( needle, array ) {
			return !! ~array.indexOf( needle );
		},
		convert_hex: function ( hex, opacity ) {
			hex = hex.replace( '#', '' );
			var r = parseInt( hex.substring( 0, 2 ), 16 ),
				g = parseInt( hex.substring( 2, 4 ), 16 ),
				b = parseInt( hex.substring( 4, 6 ), 16 );
			return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity / 100 + ')';
		},
		debounce: function ( callback, threshold ) {
			var timeout;
			return function () {
				var context = this,
					params = arguments;
				window.clearTimeout( timeout );
				timeout = window.setTimeout( function () {
					callback.apply( context, params );
				}, threshold );
			};
		},
		throttle: function ( callback, threshold ) {
			var suppress = false,
				clear = function () {
					suppress = false;
				};
			return function () {
				if ( ! suppress ) {
					callback.apply( this, arguments );
					window.setTimeout( clear, threshold );
					suppress = true;
				}
			};
		},
		getXPath: function ( element ) {
			var path = [],
				current,
				id,
				classes,
				tag,
				eq;

			$.each( $( element ).parents(), function ( index, value ) {
				current = $( value );
				id = current.attr( 'id' ) || '';
				classes = current.attr( 'class' ) || '';
				tag = current.get( 0 ).tagName.toLowerCase();
				eq = current.parent().children( tag ).index( current );
				if ( tag === 'body' ) {
					return false;
				}
				if ( classes.length > 0 ) {
					classes = classes.split( ' ' );
					classes = classes[ 0 ];
				}
				path.push(
					tag +
						( id.length > 0
							? '#' + id
							: classes.length > 0
							? '.' + classes.split( ' ' ).join( '.' )
							: ':eq(' + eq + ')' )
				);
			} );
			return path.reverse().join( ' > ' );
		},
		strtotime: function ( text, now ) {
			//  discuss at: http://phpjs.org/functions/strtotime/
			//     version: 1109.2016
			// original by: Caio Ariede (http://caioariede.com)
			// improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// improved by: Caio Ariede (http://caioariede.com)
			// improved by: A. Matas Quezada (http://amatiasq.com)
			// improved by: preuter
			// improved by: Brett Zamir (http://brett-zamir.me)
			// improved by: Mirko Faber
			//    input by: David
			// bugfixed by: Wagner B. Soares
			// bugfixed by: Artur Tchernychev
			//        note: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
			//   example 1: strtotime('+1 day', 1129633200);
			//   returns 1: 1129719600
			//   example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
			//   returns 2: 1130425202
			//   example 3: strtotime('last month', 1129633200);
			//   returns 3: 1127041200
			//   example 4: strtotime('2009-05-04 08:30:00 GMT');
			//   returns 4: 1241425800
			var parsed,
				match,
				today,
				year,
				date,
				days,
				ranges,
				len,
				times,
				regex,
				i,
				fail = false;
			if ( ! text ) {
				return fail;
			}
			// Unecessary spaces
			text = text
				.replace( /^\s+|\s+$/g, '' )
				.replace( /\s{2,}/g, ' ' )
				.replace( /[\t\r\n]/g, '' )
				.toLowerCase();
			// in contrast to php, js Date.parse function interprets:
			// dates given as yyyy-mm-dd as in timezone: UTC,
			// dates with "." or "-" as MDY instead of DMY
			// dates with two-digit years differently
			// etc...etc...
			// ...therefore we manually parse lots of common date formats
			match = text.match(
				/^(\d{1,4})([\-\.\/\:])(\d{1,2})([\-\.\/\:])(\d{1,4})(?:\s(\d{1,2}):(\d{2})?:?(\d{2})?)?(?:\s([A-Z]+)?)?$/
			);
			if ( match && match[ 2 ] === match[ 4 ] ) {
				if ( match[ 1 ] > 1901 ) {
					switch ( match[ 2 ] ) {
						case '-':
							// YYYY-M-D
							if ( match[ 3 ] > 12 || match[ 5 ] > 31 ) {
								return fail;
							}
							return (
								new Date(
									match[ 1 ],
									parseInt( match[ 3 ], 10 ) - 1,
									match[ 5 ],
									match[ 6 ] || 0,
									match[ 7 ] || 0,
									match[ 8 ] || 0,
									match[ 9 ] || 0
								) / 1000
							);
						case '.':
							// YYYY.M.D is not parsed by strtotime()
							return fail;
						case '/':
							// YYYY/M/D
							if ( match[ 3 ] > 12 || match[ 5 ] > 31 ) {
								return fail;
							}
							return (
								new Date(
									match[ 1 ],
									parseInt( match[ 3 ], 10 ) - 1,
									match[ 5 ],
									match[ 6 ] || 0,
									match[ 7 ] || 0,
									match[ 8 ] || 0,
									match[ 9 ] || 0
								) / 1000
							);
					}
				} else if ( match[ 5 ] > 1901 ) {
					switch ( match[ 2 ] ) {
						case '-':
							// D-M-YYYY
							if ( match[ 3 ] > 12 || match[ 1 ] > 31 ) {
								return fail;
							}
							return (
								new Date(
									match[ 5 ],
									parseInt( match[ 3 ], 10 ) - 1,
									match[ 1 ],
									match[ 6 ] || 0,
									match[ 7 ] || 0,
									match[ 8 ] || 0,
									match[ 9 ] || 0
								) / 1000
							);
						case '.':
							// D.M.YYYY
							if ( match[ 3 ] > 12 || match[ 1 ] > 31 ) {
								return fail;
							}
							return (
								new Date(
									match[ 5 ],
									parseInt( match[ 3 ], 10 ) - 1,
									match[ 1 ],
									match[ 6 ] || 0,
									match[ 7 ] || 0,
									match[ 8 ] || 0,
									match[ 9 ] || 0
								) / 1000
							);
						case '/':
							// M/D/YYYY
							if ( match[ 1 ] > 12 || match[ 3 ] > 31 ) {
								return fail;
							}
							return (
								new Date(
									match[ 5 ],
									parseInt( match[ 1 ], 10 ) - 1,
									match[ 3 ],
									match[ 6 ] || 0,
									match[ 7 ] || 0,
									match[ 8 ] || 0,
									match[ 9 ] || 0
								) / 1000
							);
					}
				} else {
					switch ( match[ 2 ] ) {
						case '-':
							// YY-M-D
							if (
								match[ 3 ] > 12 ||
								match[ 5 ] > 31 ||
								( match[ 1 ] < 70 && match[ 1 ] > 38 )
							) {
								return fail;
							}
							year =
								match[ 1 ] >= 0 && match[ 1 ] <= 38
									? +match[ 1 ] + 2000
									: match[ 1 ];
							return (
								new Date(
									year,
									parseInt( match[ 3 ], 10 ) - 1,
									match[ 5 ],
									match[ 6 ] || 0,
									match[ 7 ] || 0,
									match[ 8 ] || 0,
									match[ 9 ] || 0
								) / 1000
							);
						case '.':
							// D.M.YY or H.MM.SS
							if ( match[ 5 ] >= 70 ) {
								// D.M.YY
								if ( match[ 3 ] > 12 || match[ 1 ] > 31 ) {
									return fail;
								}
								return (
									new Date(
										match[ 5 ],
										parseInt( match[ 3 ], 10 ) - 1,
										match[ 1 ],
										match[ 6 ] || 0,
										match[ 7 ] || 0,
										match[ 8 ] || 0,
										match[ 9 ] || 0
									) / 1000
								);
							}
							if ( match[ 5 ] < 60 && ! match[ 6 ] ) {
								// H.MM.SS
								if ( match[ 1 ] > 23 || match[ 3 ] > 59 ) {
									return fail;
								}
								today = new Date();
								return (
									new Date(
										today.getFullYear(),
										today.getMonth(),
										today.getDate(),
										match[ 1 ] || 0,
										match[ 3 ] || 0,
										match[ 5 ] || 0,
										match[ 9 ] || 0
									) / 1000
								);
							}
							return fail; // invalid format, cannot be parsed
						case '/':
							// M/D/YY
							if (
								match[ 1 ] > 12 ||
								match[ 3 ] > 31 ||
								( match[ 5 ] < 70 && match[ 5 ] > 38 )
							) {
								return fail;
							}
							year =
								match[ 5 ] >= 0 && match[ 5 ] <= 38
									? +match[ 5 ] + 2000
									: match[ 5 ];
							return (
								new Date(
									year,
									parseInt( match[ 1 ], 10 ) - 1,
									match[ 3 ],
									match[ 6 ] || 0,
									match[ 7 ] || 0,
									match[ 8 ] || 0,
									match[ 9 ] || 0
								) / 1000
							);
						case ':':
							// HH:MM:SS
							if (
								match[ 1 ] > 23 ||
								match[ 3 ] > 59 ||
								match[ 5 ] > 59
							) {
								return fail;
							}
							today = new Date();
							return (
								new Date(
									today.getFullYear(),
									today.getMonth(),
									today.getDate(),
									match[ 1 ] || 0,
									match[ 3 ] || 0,
									match[ 5 ] || 0
								) / 1000
							);
					}
				}
			}
			// other formats and "now" should be parsed by Date.parse()
			if ( text === 'now' ) {
				return now === null || isNaN( now )
					? new Date().getTime() / 1000 || 0
					: now || 0;
			}
			parsed = Date.parse( text );
			if ( ! isNaN( parsed ) ) {
				return parsed / 1000 || 0;
			}
			date = now ? new Date( now * 1000 ) : new Date();
			days = {
				sun: 0,
				mon: 1,
				tue: 2,
				wed: 3,
				thu: 4,
				fri: 5,
				sat: 6,
			};
			ranges = {
				yea: 'FullYear',
				mon: 'Month',
				day: 'Date',
				hou: 'Hours',
				min: 'Minutes',
				sec: 'Seconds',
			};

			function lastNext( type, range, modifier ) {
				var diff,
					day = days[ range ];
				if ( day !== undefined ) {
					diff = day - date.getDay();
					if ( diff === 0 ) {
						diff = 7 * modifier;
					} else if ( diff > 0 && type === 'last' ) {
						diff -= 7;
					} else if ( diff < 0 && type === 'next' ) {
						diff += 7;
					}
					date.setDate( date.getDate() + diff );
				}
			}

			function process( val ) {
				var splt = val.split( ' ' ),
					type = splt[ 0 ],
					range = splt[ 1 ].substring( 0, 3 ),
					typeIsNumber = /\d+/.test( type ),
					ago = splt[ 2 ] === 'ago',
					num = ( type === 'last' ? -1 : 1 ) * ( ago ? -1 : 1 );
				if ( typeIsNumber ) {
					num *= parseInt( type, 10 );
				}
				if (
					ranges.hasOwnProperty( range ) &&
					! splt[ 1 ].match( /^mon(day|\.)?$/i )
				) {
					return date[ 'set' + ranges[ range ] ](
						date[ 'get' + ranges[ range ] ]() + num
					);
				}
				if ( range === 'wee' ) {
					return date.setDate( date.getDate() + num * 7 );
				}
				if ( type === 'next' || type === 'last' ) {
					lastNext( type, range, num );
				} else if ( ! typeIsNumber ) {
					return false;
				}
				return true;
			}

			times =
				'(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
				'|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
				'|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)';
			regex =
				'([+-]?\\d+\\s' +
				times +
				'|' +
				'(last|next)\\s' +
				times +
				')(\\sago)?';
			match = text.match( new RegExp( regex, 'gi' ) );
			if ( ! match ) {
				return fail;
			}
			for ( i = 0, len = match.length; i < len; i += 1 ) {
				if ( ! process( match[ i ] ) ) {
					return fail;
				}
			}
			// ECMAScript 5 only
			// if (!match.every(process))
			//    return false;
			return date.getTime() / 1000;
		},
		serializeObject: function ( options ) {
			$.extend( {}, options );

			var values = {},
				settings = $.extend(
					true,
					{
						include: [],
						exclude: [],
						includeByClass: '',
					},
					options
				);

			this.find( ':input' ).each( function () {
				var parsedName;

				// Apply simple checks and filters
				if (
					! this.name ||
					this.disabled ||
					window.PUM.utilities.inArray(
						this.name,
						settings.exclude
					) ||
					( settings.include.length &&
						! window.PUM.utilities.inArray(
							this.name,
							settings.include
						) ) ||
					this.className.indexOf( settings.includeByClass ) === -1
				) {
					return;
				}

				// Parse complex names
				// JS RegExp doesn't support "positive look behind" :( that's why so weird parsing is used
				parsedName = this.name.replace( rName, '[$1' ).split( '[' );
				if ( ! parsedName[ 0 ] ) {
					return;
				}

				if (
					this.checked ||
					window.PUM.utilities.inArray( this.type, inputTypes ) ||
					window.PUM.utilities.inArray(
						this.nodeName.toLowerCase(),
						inputNodes
					)
				) {
					// Simulate control with a complex name (i.e. `some[]`)
					// as it handled in the same way as Checkboxes should
					if ( this.type === 'checkbox' ) {
						parsedName.push( '' );
					}

					// jQuery.val() is used to simplify of getting values
					// from the custom controls (which follow jQuery .val() API) and Multiple Select
					storeValue( values, parsedName, $( this ).val() );
				}
			} );

			return values;
		},
	};

	// Ensure backwards compatibility
	$.fn.pumSerializeObject = window.PUM.utilities.serializeObject;
	$.fn.popmake.utilies = $.fn.popmake.utilities; // Keep typo version for backwards compatibility
} )( jQuery, document );
