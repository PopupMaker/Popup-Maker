/**
 * Handles pointers throughout Popup Maker.
 */
(function ($) {
	window.pumPointers = window.pumPointers || {};
	function open_pointer( id ) {
		id = parseInt( id );
		var pointer = pumPointers.pointers[id];
		var options = $.extend( pointer.options, {
			close: function() {
				$.post( ajaxurl, {
					pointer: pointer.pointer_id,
					action: 'dismiss-wp-pointer'
				});
				if ( id !== pumPointers.pointers.length - 1 ) {
					open_pointer( id + 1 );
				}
			},
			buttons: function( event, t ) {
				var $btn_next  = $( '<button class="button">Next</button>' );
				var $btn_complete  = $( '<button class="button">Finish!</button>' );
				var $wrapper = $( '<div class=\"pum-pointer-buttons\" />' );
				$btn_next.bind( 'click.pointer', function(e) {
					e.preventDefault();
					t.element.pointer('close');
				});
				$btn_complete.bind( 'click.pointer', function(e) {
					e.preventDefault();
					t.element.pointer('close');
				});
				if ( id === pumPointers.pointers.length - 1 ) {
					$wrapper.append( $btn_complete );
				} else {
					$wrapper.append( $btn_next );
				}

				return $wrapper;

			}
		});
		var this_pointer = $( pointer.target ).pointer( options );
		$('html, body').animate({ scrollTop: $( pointer.target ).offset().top - 200 });
		this_pointer.pointer( 'open' );
	}
	$(document).ready( function($) {
		open_pointer( 0 );
	});
}(jQuery));
