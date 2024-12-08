import './plugins';

( function ( $ ) {
	'use strict';

	window.PUM_Admin = window.PUM_Admin || {};

	window.pum_popup_settings_editor = window.pum_popup_settings_editor || {
		form_args: {},
		current_values: {},
		preview_nonce: null,
	};

	$( document )
		.on( 'keydown', '#popup-title', function ( event ) {
			var keyCode = event.keyCode || event.which;
			if ( 9 === keyCode ) {
				event.preventDefault();
				$( '#title' ).focus();
			}
		} )
		.on( 'keydown', '#title, #popup-title', function ( event ) {
			var keyCode = event.keyCode || event.which,
				target;
			if ( ! event.shiftKey && 9 === keyCode ) {
				event.preventDefault();
				target =
					$( this ).attr( 'id' ) === 'title'
						? '#popup-title'
						: '#insert-media-button';
				$( target ).focus();
			}
		} )
		.on(
			'keydown',
			'#popup-title, #insert-media-button',
			function ( event ) {
				var keyCode = event.keyCode || event.which,
					target;
				if ( event.shiftKey && 9 === keyCode ) {
					event.preventDefault();
					target =
						$( this ).attr( 'id' ) === 'popup-title'
							? '#title'
							: '#popup-title';
					$( target ).focus();
				}
			}
		);

	// Initiate when ready.
	$( function () {
		$( this ).trigger( 'pum_init' );

		$( '#title' ).prop( 'required', true );

		var $container = $( '#pum-popup-settings-container' ),
			args = pum_popup_settings_editor.form_args || {},
			values = pum_popup_settings_editor.current_values || {};

		if ( $container.length ) {
			$container.find( '.pum-no-js' ).hide();
			PUM_Admin.forms.render( args, values, $container );
		}

		$( 'a.page-title-action' )
			.clone()
			.attr( 'target', '_blank' )
			.attr(
				'href',
				pum_admin_vars.homeurl +
					'?popup_preview=' +
					pum_popup_settings_editor.preview_nonce +
					'&popup=' +
					$( '#post_ID' ).val()
			)
			.text( pum_admin_vars.I10n.preview_popup )
			.insertAfter( 'a.page-title-action' );

		// TODO Can't figure out why this is needed, but it looks stupid otherwise when the first condition field defaults to something other than the placeholder.
		$( '#pum-first-condition, #pum-first-trigger, #pum-first-cookie' )
			.val( null )
			.trigger( 'change' );

		// Add event handler to detect when opening sound is change and play the sound to allow admin to preview it.
		document
			.querySelector( '#pum-popup-settings-container' )
			.addEventListener( 'change', function ( e ) {
				if ( 'open_sound' === e.target.id ) {
					// Only play if the sound selected is not None or Custom.
					var notThese = [ 'none', 'custom' ];
					if ( notThese.indexOf( e.target.value ) === -1 ) {
						var audio = new Audio(
							pum_admin_vars.pm_dir_url +
								'assets/sounds/' +
								e.target.value
						);
						audio.addEventListener( 'canplaythrough', function () {
							this.play().catch( function ( reason ) {
								console.warn(
									'Sound was not able to play when selected. Reason: ' +
										reason
								);
							} );
						} );
						audio.addEventListener( 'error', function () {
							console.warn(
								'Error occurred when trying to load popup opening sound.'
							);
						} );
					}
				}
			} );

		// Dynamically switches example click trigger from popup-{popup-id} to using real ID.
		$( document ).on( 'pum_init', function () {
			$(
				'#pum-default-click-trigger-class:not(.pum-click-trigger-initialized)'
			).each( function () {
				$( this )
					.addClass( 'pum-click-trigger-initialized' )
					.text( $( '#popup-id' ).data( 'popup-id' ) );
			} );
		} );

		document
			.querySelector( '#pum-popup-settings-container' )
			.addEventListener( 'click', function ( e ) {
				if (
					Array.from( e.target.classList ).includes( 'popup-type' ) ||
					Array.from( e.target.parentElement.classList ).includes(
						'popup-type'
					)
				) {
					var $container = jQuery( '#pum-popup-settings-container' );
					if ( 1 === $container.length ) {
						// Our initial presets. As we add more, consider creating JSON import system and moving to there.
						var popupTypes = {
							'center-popup': {
								size: 'medium',
								responsive_min_width: '0%',
								responsive_max_width: '100%',
								animation_type: 'fade',
								animation_speed: 350,
								location: 'center',
								position_fixed: false,
								position_from_trigger: false,
								overlay_disabled: false,
								stackable: false,
								disable_reposition: false,
							},
							'left-bottom-notice': {
								size: 'tiny',
								responsive_min_width: '0%',
								responsive_max_width: '100%',
								animation_type: 'fade',
								animation_speed: 350,
								animation_origin: 'left bottom',
								location: 'left bottom',
								position_bottom: 10,
								position_left: 10,
								position_from_trigger: false,
								position_fixed: true,
								overlay_disabled: true,
								stackable: true,
								disable_reposition: false,
							},
							'top-bar': {
								size: 'custom',
								custom_width: '100%',
								custom_height_auto: true,
								animation_type: 'fadeAndSlide',
								animation_speed: 300,
								animation_origin: 'top',
								location: 'center top',
								position_top: 0,
								position_from_trigger: false,
								position_fixed: true,
								overlay_disabled: true,
								stackable: true,
								disable_reposition: false,
							},
							'right-bottom-slidein': {
								size: 'custom',
								custom_width: '300px',
								custom_height_auto: true,
								animation_type: 'slide',
								animation_speed: 350,
								animation_origin: 'bottom',
								location: 'right bottom',
								position_bottom: 10,
								position_right: 10,
								position_from_trigger: false,
								position_fixed: true,
								overlay_disabled: true,
								stackable: true,
								disable_reposition: false,
							},
						};
						var popupType =
							e.target.dataset.popupType ||
							e.target.parentElement.dataset.popupType ||
							'';

						// Gather our values needed for creating new settings object.
						var presetValues = popupTypes.hasOwnProperty(
							popupType
						)
							? popupTypes[ popupType ]
							: {};
						var args = pum_popup_settings_editor.form_args || {};
						var originalValues =
							pum_popup_settings_editor.current_values || {};
						var currentValues = $container.pumSerializeObject();

						// pumSerializeObject returns the trigger/cookie settings as strings instead of objects.
						// Cycle through each trigger and cookie and convert to objects.
						if ( currentValues.popup_settings.triggers ) {
							for (
								var i = 0;
								i <
								currentValues.popup_settings.triggers.length;
								i++
							) {
								currentValues.popup_settings.triggers[
									i
								].settings = JSON.parse(
									currentValues.popup_settings.triggers[ i ]
										.settings
								);
							}
						}
						if ( currentValues.popup_settings.cookies ) {
							for (
								var j = 0;
								j < currentValues.popup_settings.cookies.length;
								j++
							) {
								currentValues.popup_settings.cookies[
									j
								].settings = JSON.parse(
									currentValues.popup_settings.cookies[ j ]
										.settings
								);
							}
						}

						var newValues = Object.assign(
							{},
							originalValues,
							currentValues.popup_settings,
							presetValues
						);

						// Re-render form using updated settings.
						PUM_Admin.forms.render( args, newValues, $container );

						// Click to 'Display' so they don't jump to 'Targeting' tab upon render.
						document
							.querySelector(
								'a[href="#pum-popup-settings_display"]'
							)
							.click();

						// Adds a notice into 'Display Presets' tab telling admin the settings have been applied.
						var notice = document.createElement( 'div' );
						notice.classList.add( 'notice', 'updated' );
						notice.insertBefore(
							document.createElement( 'p' ),
							notice.firstChild
						);
						notice.firstChild.innerText =
							'Display settings have been updated with the ' +
							popupType +
							' preset';
						var parent = document.querySelector(
							'#pum-popup-settings-display-subtabs_preset'
						);
						parent.insertBefore( notice, parent.firstChild );
					}
				}
			} );
	} );
} )( jQuery );
