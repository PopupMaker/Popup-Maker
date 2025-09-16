/**
 * Adds needed backward compatibility for older versions of jQuery
 */
( function ( $ ) {
	'use strict';
	if ( $.fn.on === undefined ) {
		$.fn.on = function ( types, sel, fn ) {
			return this.delegate( sel, types, fn );
		};
	}
	if ( $.fn.off === undefined ) {
		$.fn.off = function ( types, sel, fn ) {
			return this.undelegate( sel, types, fn );
		};
	}

	if ( $.fn.bindFirst === undefined ) {
		$.fn.bindFirst = function ( which, handler ) {
			var $el = $( this ),
				events,
				registered;

			$el.unbind( which, handler );
			$el.bind( which, handler );

			events = $._data( $el[ 0 ] ).events;
			registered = events[ which ];
			registered.unshift( registered.pop() );

			events[ which ] = registered;
		};
	}

	if ( $.fn.outerHtml === undefined ) {
		$.fn.outerHtml = function () {
			var $el = $( this ).clone(),
				$temp = $( '<div/>' ).append( $el );

			return $temp.html();
		};
	}

	if ( $.fn.isInViewport === undefined ) {
		$.fn.isInViewport = function () {
			var elementTop = $( this ).offset().top;
			var elementBottom = elementTop + $( this ).outerHeight();

			var viewportTop = $( window ).scrollTop();
			var viewportBottom = viewportTop + $( window ).height();

			return elementBottom > viewportTop && elementTop < viewportBottom;
		};
	}

	if ( Date.now === undefined ) {
		Date.now = function () {
			return new Date().getTime();
		};
	}
} )( jQuery );
