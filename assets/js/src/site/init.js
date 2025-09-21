/**
 * Initialize Popup Maker.
 * Version 1.21
 */
( function ( $, document, undefined ) {
	'use strict';
	// Defines the current version.
	$.fn.popmake.version = 1.21;

	// Stores the last open popup.
	$.fn.popmake.last_open_popup = null;

	// Here for backward compatibility.
	window.ajaxurl = window.pum_vars.ajaxurl;

	window.PUM.init = function () {
		$( document ).trigger( 'pumBeforeInit' );
		$( '.pum' ).popmake();
		$( document ).trigger( 'pumInitialized' );

		/**
		 * Process php based form submissions when the form_success args are passed.
		 */
		if ( typeof pum_vars.form_success === 'object' ) {
			pum_vars.form_success = $.extend( {
				popup_id: null,
				settings: {},
			} );

			PUM.forms.success(
				pum_vars.form_success.popup_id,
				pum_vars.form_success.settings
			);
		}

		// Initiate integrations.
		PUM.integrations.init();
	};

	// Initiate when ready.
	$( function () {
		const shouldInit = PUM.hooks.applyFilters( 'pum.shouldInit', true );

		if ( ! shouldInit ) {
			return;
		}

		// TODO can this be moved outside doc.ready since we are awaiting our own promises first?
		var initHandler = PUM.hooks.applyFilters( 'pum.initHandler', PUM.init );
		var initPromises = PUM.hooks.applyFilters( 'pum.initPromises', [] );

		Promise.all( initPromises ).then( initHandler );
	} );

	/**
	 * Add hidden field to all popup forms.
	 */
	$( '.pum' )
		.on( 'pumInit', function () {
			var $popup = PUM.getPopup( this ),
				popupID = PUM.getSetting( $popup, 'id' ),
				$forms = $popup.find( 'form' );

			/**
			 * If there are forms in the popup add a hidden field for use in retriggering the popup on reload.
			 */
			if ( $forms.length ) {
				var $hiddenField = $( '<input>', {
					type: 'hidden',
					name: 'pum_form_popup_id',
					value: popupID,
				} );

				$forms.append( $hiddenField );
			}
		} )
		.on( 'pumAfterClose', window.PUM.actions.stopIframeVideosPlaying );

	$( '.pum .pum-cta a, .pum a.pum-cta' ).on( 'click', function () {
		PUM.getPopup( this ).trigger( 'pumConversion' );
	} );
} )( jQuery );
