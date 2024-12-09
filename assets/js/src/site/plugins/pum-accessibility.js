/**
 * Defines the core $.popmake binds.
 * Version 1.4
 */
var PUM_Accessibility;
( function ( $, document, undefined ) {
	'use strict';
	var $top_level_elements,
		focusableElementsString =
			'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]',
		previouslyFocused,
		currentModal,
		selector = '.pum:not(.pum-accessibility-disabled)';

	PUM_Accessibility = {
		// Accessibility: Checks focus events to ensure they stay inside the modal.
		forceFocus: function ( e ) {
			if (
				currentModal &&
				currentModal.length &&
				! currentModal[ 0 ].contains( e.target )
			) {
				e.stopPropagation();
				PUM_Accessibility.setFocusToFirstItem();
			}
		},
		trapTabKey: function ( e ) {
			// if tab or shift-tab pressed
			if ( e.keyCode === 9 ) {
				// get list of focusable items
				var focusableItems = currentModal
						.find( '.pum-container *' )
						.filter( focusableElementsString )
						.filter( ':visible' ),
					// get currently focused item
					focusedItem = $( ':focus' ),
					// get the number of focusable items
					numberOfFocusableItems = focusableItems.length,
					// get the index of the currently focused item
					focusedItemIndex = focusableItems.index( focusedItem );

				if ( e.shiftKey ) {
					//back tab
					// if focused on first item and user preses back-tab, go to the last focusable item
					if ( focusedItemIndex === 0 ) {
						focusableItems
							.get( numberOfFocusableItems - 1 )
							.focus();
						e.preventDefault();
					}
				} else {
					//forward tab
					// if focused on the last item and user preses tab, go to the first focusable item
					if ( focusedItemIndex === numberOfFocusableItems - 1 ) {
						focusableItems.get( 0 ).focus();
						e.preventDefault();
					}
				}
			}
		},
		setFocusToFirstItem: function () {
			var $firstEl = currentModal
				.find( '.pum-container *' )
				.filter( focusableElementsString )
				.filter( ':visible' )
				//.filter( ':not(.pum-close)' )
				.first();

			// set focus to first focusable item
			$firstEl.focus();
		},
		initiateFocusLock: function () {
			var $popup = PUM.getPopup( this ),
				$focused = $( ':focus' );

			// Accessibility: Sets the previous focus element.
			if ( ! $popup.has( $focused ).length ) {
				previouslyFocused = $focused;
			}

			// Accessibility: Sets the current modal for focus checks.
			currentModal = $popup
				// Accessibility: Trap tab key.
				.on(
					'keydown.pum_accessibility',
					PUM_Accessibility.trapTabKey
				);

			// Accessibility: Add focus check first time focus changes after popup opens that prevents tabbing outside of modal.
			$( document ).one(
				'focusin.pum_accessibility',
				PUM_Accessibility.forceFocus
			);

			// Accessibility: Focus on the modal.
			PUM_Accessibility.setFocusToFirstItem();
		},
	};

	$( document )
		.on( 'pumInit', selector, function () {
			PUM.getPopup( this )
				.find( '[tabindex]' )
				.each( function () {
					var $this = $( this );
					$this
						.data( 'tabindex', $this.attr( 'tabindex' ) )
						.prop( 'tabindex', '0' );
				} );
		} )
		.on( 'pumBeforeOpen', selector, function () {} )
		.on( 'pumAfterOpen', selector, PUM_Accessibility.initiateFocusLock )
		.on( 'pumAfterOpen', selector, function () {
			var $popup = PUM.getPopup( this );

			// Accessibility: Sets the current modal as open.
			currentModal = $popup.attr( 'aria-modal', 'true' );
		} )
		.on( 'pumBeforeClose', selector, function () {} )
		.on( 'pumAfterClose', selector, function () {
			var $popup = PUM.getPopup( this );

			$popup
				.off( 'keydown.pum_accessibility' )
				.attr( 'aria-modal', 'false' );

			// Accessibility: Focus back on the previously focused element.
			if ( previouslyFocused !== undefined && previouslyFocused.length ) {
				previouslyFocused.focus();
			}

			// Accessibility: Clears the currentModal var.
			currentModal = null;

			// Accessibility: Removes the force focus check.
			$( document ).off( 'focusin.pum_accessibility' );
		} )
		.on( 'pumSetupClose', selector, function () {} )
		.on( 'pumOpenPrevented', selector, function () {} )
		.on( 'pumClosePrevented', selector, function () {} )
		.on( 'pumBeforeReposition', selector, function () {} );
} )( jQuery, document );
