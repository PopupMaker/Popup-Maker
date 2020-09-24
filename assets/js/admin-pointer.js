/**
 * Handles pointers throughout Popup Maker.
 */
( function( $ ) {
	window.pumPointers = window.pumPointers || {
		pointers: [],
	};
	window.skipTour = false;
	function open_pointer( id ) {
		id = parseInt( id );
		var pointer = pumPointers.pointers[id];

		// Checks if we need to do anything prior to opening pointer.
		if ( pointer.hasOwnProperty( 'pre' ) ) {
			// Checks if we need to do any clicks.
			if ( pointer.pre.hasOwnProperty( 'clicks' ) ) {
				$.each( pointer.pre.clicks, function( index, element ) {
					$( element ).click();
				} );
			}
		}

		// If target is not valid, exit early.
		var $target = $( pointer.target );
		if ( 0 === $target.length ) {
			return;
		}

		// Prepare the options to be passed to wp-pointer.
		var options = $.extend( pointer.options, {
			close: function() {
				$.post( ajaxurl, {
					pointer: pointer.pointer_id,
					action: 'dismiss-wp-pointer',
				} );

				// If we have other pointers left in tour, open the next.
				if ( false === skipTour && id !== pumPointers.pointers.length - 1 ) {
					open_pointer( id + 1 );
				}
			},
			buttons: function( event, t ) {
				var $btn_skip = $( '<button class="button" style="margin-right: 10px;">Skip tour</button>' );
				var $btn_next = $( '<button class="button button-primary">Next</button>' );
				var $btn_complete = $( '<button class="button button-primary">Thanks!</button>' );
				var $wrapper = $( '<div class=\"pum-pointer-buttons\" />' );
				$btn_skip.bind( 'click.point', function( e ) {
					e.preventDefault();
					skipTour = true;
					t.element.pointer( 'close' );
					for ( var i = 0; i < pumPointers.pointers.length; i++ ) {
						/**
						 * If the user has never dismissed pointers before, there is a chance
						 * their user meta key hasn't been set yet. If we fire too many of these
						 * at once, WordPress will rewrite itself a few times causing not all
						 * to be dismissed.
						 */
						setTimeout( function( i ) {
							$.post( ajaxurl, {
								pointer: pumPointers.pointers[i].pointer_id,
								action: 'dismiss-wp-pointer',
							} );
						}, ( i * 500 ) + 1, i );
					}
				} );
				$btn_next.bind( 'click.pointer', function( e ) {
					e.preventDefault();
					t.element.pointer( 'close' );
				} );
				$btn_complete.bind( 'click.pointer', function( e ) {
					e.preventDefault();
					t.element.pointer( 'close' );
				} );

				// If this is the first pointer of a tour...
				if ( 1 !== pumPointers.pointers.length && 0 === id ) {
					// ... then show a skip tour button.
					$wrapper.append( $btn_skip );
				}

				// If this is the last pointer in tour...
				if ( id === pumPointers.pointers.length - 1 ) {
					// ... then show complete button.
					$wrapper.append( $btn_complete );
				} else {
					// ... else show next button.
					$wrapper.append( $btn_next );
				}

				return $wrapper;
			},
		} );

		// Show pointer after scrolling to target.
		var this_pointer = $target.pointer( options );
		$( 'html, body' ).animate( { scrollTop: $target.offset().top - 200 } );
		this_pointer.pointer( 'open' );
	}
	$( document ).ready( function( $ ) {
		/**
		 * Since many of our fields and screens are dynamically loaded, we need to ensure
		 * the first pointer doesn't start until after they are loaded. But, we can set those
		 * JS files as dependencies as we these pointers could be loaded on any admin screen.
		 */
		setTimeout( function() {
			if ( 0 < pumPointers.pointers.length ) {
				open_pointer( 0 );
			}
		}, 1000 );
	} );
}( jQuery ) );
