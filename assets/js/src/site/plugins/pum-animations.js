/**
 * Defines the core $.popmake animations.
 * Version 1.4
 */
( function ( $, document, undefined ) {
	'use strict';

	$.fn.popmake.methods.animate_overlay = function (
		style,
		duration,
		callback
	) {
		// Method calling logic
		var settings = PUM.getPopup( this ).popmake( 'getSettings' );

		if ( settings.overlay_disabled ) {
			return $.fn.popmake.overlay_animations.none.apply( this, [
				duration,
				callback,
			] );
		}

		if ( $.fn.popmake.overlay_animations[ style ] ) {
			return $.fn.popmake.overlay_animations[ style ].apply( this, [
				duration,
				callback,
			] );
		}

		if ( window.console ) {
			console.warn( 'Animation style ' + style + ' does not exist.' );
		}
		return this;
	};

	$.fn.popmake.methods.animate = function ( style ) {
		// Method calling logic
		if ( $.fn.popmake.animations[ style ] ) {
			return $.fn.popmake.animations[ style ].apply(
				this,
				Array.prototype.slice.call( arguments, 1 )
			);
		}
		if ( window.console ) {
			console.warn( 'Animation style ' + style + ' does not exist.' );
		}
		return this;
	};

	/**
	 * Resets animation & position properties prior to opening/reopening the popup.
	 *
	 * @param $popup
	 */
	function popupCssReset( $popup ) {
		var $container = $popup.popmake( 'getContainer' ),
			cssResets = { display: '', opacity: '' };

		$popup.css( cssResets );
		$container.css( cssResets );
	}

	function overlayAnimationSpeed( settings ) {
		if ( settings.overlay_disabled ) {
			return 0;
		}

		return settings.animation_speed / 2;
	}

	function containerAnimationSpeed( settings ) {
		if ( settings.overlay_disabled ) {
			return parseInt( settings.animation_speed );
		}

		return settings.animation_speed / 2;
	}

	/**
	 * All animations should.
	 *
	 * 1. Reset Popup CSS styles. Defaults are as follows:
	 * - opacity: 1
	 * - display: "none"
	 * - left, top, right, bottom: set to final position (where animation ends).
	 *
	 * 2. Prepare the popup for animation. Examples include:
	 * - a. Static positioned animations like fade might set display: "block" & opacity: 0.
	 * - b. Moving animations such as slide might set display: "block" & opacity: 0 so that
	 *      positioning can be accurately calculated, then set opacity: 1 before the animation begins.
	 *
	 * 3. Animate the overlay using `$popup.popmake( 'animate_overlay', type, speed, callback);`
	 *
	 * 4. Animate the container.
	 * - a. Moving animations can use $container.popmake( 'reposition', callback ); The callback
	 *      accepts a position argument for where you should animate to.
	 * - b. This usually takes place inside the callback for the overlay callback or after it.
	 */
	$.fn.popmake.animations = {
		none: function ( callback ) {
			var $popup = PUM.getPopup( this );

			// Ensure the container is visible immediately.
			$popup
				.popmake( 'getContainer' )
				.css( { opacity: 1, display: 'block' } );

			$popup.popmake( 'animate_overlay', 'none', 0, function () {
				// Fire user passed callback.
				if ( callback !== undefined ) {
					callback();
					// TODO Test this new method. Then remove the above.
					//callback.apply(this);
				}
			} );
			return this;
		},
		slide: function ( callback ) {
			var $popup = PUM.getPopup( this ),
				$container = $popup.popmake( 'getContainer' ),
				settings = $popup.popmake( 'getSettings' ),
				start = $popup.popmake(
					'animation_origin',
					settings.animation_origin
				);

			// Step 1. Reset popup styles.
			popupCssReset( $popup );

			// Step 2. Position the container offscreen.
			$container.position( start );

			// Step 3. Animate the popup.
			$popup.popmake(
				'animate_overlay',
				'fade',
				overlayAnimationSpeed( settings ),
				function () {
					$container.popmake( 'reposition', function ( position ) {
						$container.animate(
							position,
							containerAnimationSpeed( settings ),
							'swing',
							function () {
								// Fire user passed callback.
								if ( callback !== undefined ) {
									callback();
									// TODO Test this new method. Then remove the above.
									//allback.apply(this);
								}
							}
						);
					} );
				}
			);
			return this;
		},
		fade: function ( callback ) {
			var $popup = PUM.getPopup( this ),
				$container = $popup.popmake( 'getContainer' ),
				settings = $popup.popmake( 'getSettings' );

			// Step 1. Reset popup styles.
			popupCssReset( $popup );

			// Step 2. Hide each element to be faded in.
			$popup.css( { opacity: 0, display: 'block' } );
			$container.css( { opacity: 0, display: 'block' } );

			// Step 3. Animate the popup.
			$popup.popmake(
				'animate_overlay',
				'fade',
				overlayAnimationSpeed( settings ),
				function () {
					$container.animate(
						{ opacity: 1 },
						containerAnimationSpeed( settings ),
						'swing',
						function () {
							// Fire user passed callback.
							if ( callback !== undefined ) {
								callback();
								// TODO Test this new method. Then remove the above.
								//callback.apply(this);
							}
						}
					);
				}
			);
			return this;
		},
		fadeAndSlide: function ( callback ) {
			var $popup = PUM.getPopup( this ),
				$container = $popup.popmake( 'getContainer' ),
				settings = $popup.popmake( 'getSettings' ),
				start = $popup.popmake(
					'animation_origin',
					settings.animation_origin
				);

			// Step 1. Reset popup styles.
			popupCssReset( $popup );

			// Step 2. Hide each element to be faded in. display: "block" is neccessary for accurate positioning based on popup size.
			$popup.css( { display: 'block', opacity: 0 } );
			$container.css( { display: 'block', opacity: 0 } );

			// Step 3. Position the container offscreen.
			$container.position( start );

			// Step 4. Animate the popup.
			$popup.popmake(
				'animate_overlay',
				'fade',
				overlayAnimationSpeed( settings ),
				function () {
					$container.popmake( 'reposition', function ( position ) {
						// Add opacity to the animation properties.
						position.opacity = 1;
						// Animate the fade & slide.
						$container.animate(
							position,
							containerAnimationSpeed( settings ),
							'swing',
							function () {
								// Fire user passed callback.
								if ( callback !== undefined ) {
									callback();
									// TODO Test this new method. Then remove the above.
									//callback.apply(this);
								}
							}
						);
					} );
				}
			);
			return this;
		},
		/**
		 * TODO: Remove these and let import script replace them.
		 * @deprecated
		 * @returns {$.fn.popmake.animations}
		 */
		grow: function ( callback ) {
			return $.fn.popmake.animations.fade.apply( this, arguments );
		},
		/**
		 * @deprecated
		 * @returns {$.fn.popmake.animations}
		 */
		growAndSlide: function ( callback ) {
			return $.fn.popmake.animations.fadeAndSlide.apply(
				this,
				arguments
			);
		},
	};

	$.fn.popmake.overlay_animations = {
		none: function ( duration, callback ) {
			PUM.getPopup( this ).css( { opacity: 1, display: 'block' } );

			if ( typeof callback === 'function' ) {
				callback();
			}
		},
		fade: function ( duration, callback ) {
			PUM.getPopup( this )
				.css( { opacity: 0, display: 'block' } )
				.animate( { opacity: 1 }, duration, 'swing', callback );
		},
		slide: function ( duration, callback ) {
			PUM.getPopup( this ).slideDown( duration, callback );
		},
	};
} )( jQuery, document );
