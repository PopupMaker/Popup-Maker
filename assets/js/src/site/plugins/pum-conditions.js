( function ( $, document, undefined ) {
	'use strict';

	// Used for Mobile Detect when needed.
	var _md,
		md = function () {
			if ( _md === undefined ) {
				_md =
					typeof MobileDetect !== 'undefined'
						? new MobileDetect( window.navigator.userAgent )
						: {
								phone: function () {
									return false;
								},
								tablet: function () {
									return false;
								},
						  };
			}

			return _md;
		};

	$.extend( $.fn.popmake.methods, {
		checkConditions: function () {
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
		checkCondition: function ( condition ) {
			var target = condition.target || null,
				settings = condition.settings || condition,
				check;

			if ( ! target ) {
				console.warn( 'Condition type not set.' );
				return false;
			}

			// Get v1.21.0+ condition callback.
			const conditionCallback = getConditionCallback( target );

			if ( conditionCallback ) {
				return conditionCallback.apply( this, [ settings, condition ] );
			}

			// Backward Compatible Method calling logic.
			// TODO once all extensions updated and in circulation for v1.7, change the below to pass settings, not condition.
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

	// Cache condition callbacks.
	let popupConditionCallbacks = {};

	/**
	 * Get the condition callbacks for the current popup.
	 *
	 * @since 1.21.0
	 *
	 * @return {Object} The condition callbacks object.
	 */
	const getConditionCallbacks = () => {
		if ( Object.keys( popupConditionCallbacks ).length ) {
			return popupConditionCallbacks;
		}

		popupConditionCallbacks = window.PUM.hooks.applyFilters(
			'popupMaker.conditionCallbacks',
			{}
		);

		// Set the conditions on the Popup Maker object for backwards compatibility.
		$.fn.popmake.conditionCallbacks = popupConditionCallbacks;

		return popupConditionCallbacks;
	};

	/**
	 * Get the condition callback for a given condition ID.
	 *
	 * @since 1.21.0
	 *
	 * @param {string} conditionId - The ID of the condition to get the callback for.
	 * @return {Function|false} The condition callback function, or false if not found.
	 */
	const getConditionCallback = ( conditionId ) => {
		const conditionCallbacks = getConditionCallbacks();
		return conditionCallbacks[ conditionId ] ?? false;
	};

	// $.fn.popmake.conditions = $.fn.popmake.conditions || getConditions();
} )( jQuery, document );
