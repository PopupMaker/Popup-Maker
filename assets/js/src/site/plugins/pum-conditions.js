( function( $, document, undefined ) {
	'use strict';

	// Used for Mobile Detect when needed.
	var _md,
		md = function() {
			if ( _md === undefined ) {
				_md =
					typeof MobileDetect !== 'undefined'
						? new MobileDetect( window.navigator.userAgent )
						: {
								phone: function() {
									return false;
								},
								tablet: function() {
									return false;
								},
						  };
			}

			return _md;
		};

	$.extend( $.fn.popmake.methods, {
		checkConditions: function() {
			var $popup = PUM.getPopup( this ),
				settings = $popup.popmake( 'getSettings' ),
				// Loadable defaults to true if no conditions. Making the popup available everywhere.
				loadable = true,
				group_check,
				g,
				c,
				group,
				condition;

			if ( settings.disable_on_mobile ) {
				if ( md().phone() ) {
					return false;
				}
			}

			if ( settings.disable_on_tablet ) {
				if ( md().tablet() ) {
					return false;
				}
			}

			if ( settings.conditions.length ) {
				// All Groups Must Return True. Break if any is false and set loadable to false.
				for ( g = 0; settings.conditions.length > g; g++ ) {
					group = settings.conditions[ g ];

					// Groups are false until a condition proves true.
					group_check = false;

					// At least one group condition must be true. Break this loop if any condition is true.
					for ( c = 0; group.length > c; c++ ) {
						// Handle preprocessed PHP conditions.
						if ( typeof group[ c ] === 'boolean' ) {
							if ( !! group[ c ] ) {

								group_check = true;
								break;
							} else {
								continue;
							}
						}

						condition = $.extend(
							{},
							{
								not_operand: false,
							},
							group[ c ]
						);

						// If any condition passes, set group_check true and break.
						if (
							! condition.not_operand &&
							$popup.popmake( 'checkCondition', condition )
						) {
							group_check = true;
						} else if (
							condition.not_operand &&
							! $popup.popmake( 'checkCondition', condition )
						) {
							group_check = true;
						}

						$( this ).trigger( 'pumCheckingCondition', [
							group_check,
							condition,
						] );

						if ( group_check ) {
							break;
						}
					}

					// If any group of conditions doesn't pass, popup is not loadable.
					if ( ! group_check ) {
						loadable = false;
					}
				}
			}

			return loadable;
		},
		checkCondition: function( condition ) {
			var target = condition.target || null,
				settings = condition.settings || condition,
				check;

			if ( ! target ) {
				console.warn( 'Condition type not set.' );
				return false;
			}

			// TODO once all extensions updated and in circulation for v1.7, change the below to pass settings, not condition.

			// Method calling logic
			if ( $.fn.popmake.conditions[ target ] ) {
				return $.fn.popmake.conditions[ target ].apply( this, [
					condition,
				] );
			}
			if ( window.console ) {
				console.warn( 'Condition ' + target + ' does not exist.' );
				return true;
			}
		},
	} );

	$.fn.popmake.conditions = $.fn.popmake.conditions || {};
} )( jQuery, document );
