( function ( $, document, undefined ) {
	'use strict';

	$( document ).on( 'click', '#popup_reset_open_count', function () {
		var $this = $( this );
		if (
			$this.is( ':checked' ) &&
			! confirm( pum_admin_vars.I10n.confirm_count_reset )
		) {
			$this.prop( 'checked', false );
		}
	} );
} )( jQuery, document );
