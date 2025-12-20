/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/

/**
 * Newsletter Plugin Integration for Popup Maker
 *
 * Newsletter (thenewsletterplugin.com) uses fetch-based AJAX and replaces
 * the form innerHTML with a success message. They don't fire any JavaScript
 * events, so we use MutationObserver to detect when the form is replaced
 * with the success/confirmation message.
 *
 * @param {jQuery} $ jQuery instance.
 */
( function ( $ ) {
	const formProvider = 'newsletter';

	/**
	 * Newsletter form selectors.
	 */
	const FORM_SELECTORS =
		'form.tnp-subscription, form.tnp-ajax, form[action*="newsletter"]';

	/**
	 * Check if a form has been replaced with success content.
	 * Newsletter replaces form innerHTML on success - inputs disappear.
	 * Client-side validation prevents invalid submissions, so missing
	 * inputs means the form was successfully submitted.
	 *
	 * @param {HTMLFormElement} form The form element to check.
	 * @return {boolean} True if form inputs have been replaced.
	 */
	const isFormShowingSuccess = ( form ) => {
		return ! form.querySelector(
			'input[type="text"], input[type="email"], input[type="submit"], button[type="submit"]'
		);
	};

	/**
	 * Check if form was completely removed from container.
	 * Used when Newsletter removes the form element entirely.
	 *
	 * @param {HTMLElement} element The container element to check.
	 * @return {boolean} True if no form exists in container.
	 */
	const formWasRemoved = ( element ) => {
		return ! element.querySelector( 'form' );
	};

	/**
	 * Handle successful form submission.
	 *
	 * @param {HTMLElement} container The form container element.
	 * @param {number|null} popupId   The popup ID if inside a popup.
	 */
	const handleSuccess = ( container, popupId ) => {
		// Prevent duplicate handling.
		if ( container.dataset.pumNewsletterHandled ) {
			return;
		}
		container.dataset.pumNewsletterHandled = 'true';

		if ( ! window.PUM || ! window.PUM.integrations ) {
			return;
		}

		window.PUM.integrations.formSubmission( $( container ), {
			formProvider,
			formId: null,
			formInstanceId: null,
			extras: {
				popupId,
			},
		} );
	};

	/**
	 * Set up MutationObserver for a Newsletter form.
	 *
	 * @param {HTMLFormElement} form    The form element.
	 * @param {number|null}     popupId The popup ID if inside a popup.
	 */
	const observeForm = ( form, popupId ) => {
		const container = form.parentElement;

		if ( ! form || form.dataset.pumNewsletterObserved ) {
			return;
		}

		// Mark as observed to prevent duplicate observers.
		form.dataset.pumNewsletterObserved = 'true';

		const observer = new MutationObserver( () => {
			// Newsletter replaces form innerHTML - check if form now shows success.
			const formSuccess = isFormShowingSuccess( form );

			// Also check if form was completely removed from container.
			const formRemoved =
				container &&
				! container.contains( form ) &&
				formWasRemoved( container );

			if ( formSuccess || formRemoved ) {
				observer.disconnect();
				delete form.dataset.pumNewsletterObserved;

				// Small delay to ensure DOM is settled.
				const target = formSuccess ? form : container;
				setTimeout( () => handleSuccess( target, popupId ), 50 );
			}
		} );

		// Observe the form element itself for innerHTML changes.
		observer.observe( form, {
			childList: true,
			subtree: true,
		} );

		// Also observe parent container in case form is completely replaced.
		if ( container ) {
			observer.observe( container, {
				childList: true,
				subtree: false,
			} );
		}

		// Cleanup after 30 seconds if no submission.
		setTimeout( () => {
			observer.disconnect();
			delete form.dataset.pumNewsletterObserved;
		}, 30000 );
	};

	/**
	 * Initialize observers for all Newsletter forms.
	 */
	const initObservers = () => {
		document.querySelectorAll( FORM_SELECTORS ).forEach( ( form ) => {
			const $popup = $( form ).closest( '.pum' );
			const popupId =
				$popup.length && window.PUM
					? window.PUM.getSetting( $popup, 'id' )
					: null;

			observeForm( form, popupId );
		} );
	};

	/**
	 * Add hidden popup ID field to Newsletter forms inside popups.
	 *
	 * @param {jQuery} $popup  The popup element.
	 * @param {number} popupId The popup ID.
	 */
	const injectPopupIdField = ( $popup, popupId ) => {
		$popup.find( FORM_SELECTORS ).each( function () {
			const $form = $( this );

			if ( $form.find( 'input[name="pum_form_popup_id"]' ).length ) {
				return;
			}

			$form.append(
				$( '<input>', {
					type: 'hidden',
					name: 'pum_form_popup_id',
					value: popupId,
				} )
			);

			// Set up observer for this form.
			observeForm( this, popupId );
		} );
	};

	// Initialize on DOM ready.
	$( () => {
		if ( ! window.PUM ) {
			return;
		}

		// Observe existing forms.
		initObservers();

		// When a popup opens, set up forms inside it.
		$( document ).on( 'pumAfterOpen', '.pum', function () {
			const $popup = $( this );
			const popupId = window.PUM.getSetting( $popup, 'id' );

			if ( popupId ) {
				injectPopupIdField( $popup, popupId );
			}
		} );
	} );
} )( window.jQuery );
