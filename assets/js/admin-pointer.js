/**
 * Handles pointers throughout Popup Maker.
 */
(function ($) {
	window.pumPointers = window.pumPointers || {};
	function open_pointer( id ) {
		var pointer = pumPointers.pointers[id];
		var options = $.extend( pointer.options, {
			close: function() {
				$.post( ajaxurl, {
					pointer: pointer.pointer_id,
					action: 'dismiss-wp-pointer'
				});
			}
		});

		$(pointer.target).pointer( options ).pointer('open');
	}
	$(document).ready( function($) {
		open_pointer( 0 );
	});
}(jQuery));
