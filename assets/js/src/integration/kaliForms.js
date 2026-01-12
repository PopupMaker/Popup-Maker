/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'kaliForms';
	const $ = window.jQuery;

	/**
	 * Kali Forms uses a custom submit event system with AJAX processing.
	 * Forms have ID format: kaliforms-form-{formId}
	 * Success is determined by response status in _processForm method.
	 */

	/**
	 * Track form submission on successful processing.
	 *
	 * Kali Forms dispatches custom events during form processing.
	 * We listen to the document-level custom event after form processing completes.
	 */
	document.addEventListener( 'kaliformProcessCompleted', function ( event ) {
		const detail = event.detail;

		if ( ! detail || ! detail.form ) {
			return;
		}

		const $form = $( detail.form );
		const formId = $form.data( 'form-id' );
		const formInstanceId = $form.attr( 'id' ); // e.g., "kaliforms-form-123"

		// All the magic happens here.
		window.PUM.integrations.formSubmission( $form, {
			formProvider,
			formId,
			formInstanceId,
		} );
	} );

	/**
	 * Alternative approach: Listen for successful AJAX response.
	 * Kali Forms processes forms via AJAX action 'kaliforms_form_process'.
	 */
	$( document ).on( 'submit', 'form[data-form-id]', function ( event ) {
		const $form = $( this );
		const formClass = $form.attr( 'class' );

		// Check if this is a Kali Forms form.
		if ( ! formClass || ! formClass.includes( 'kali-form' ) ) {
			return;
		}

		// Store reference for success handler.
		const formId = $form.data( 'form-id' );
		const formInstanceId = $form.attr( 'id' );

		// Listen for successful form processing via custom event.
		const successHandler = function ( successEvent ) {
			if (
				successEvent.detail &&
				successEvent.detail.formId === formId
			) {
				window.PUM.integrations.formSubmission( $form, {
					formProvider,
					formId,
					formInstanceId,
				} );

				// Remove the listener after firing once.
				document.removeEventListener(
					'kaliFormSuccess',
					successHandler
				);
			}
		};

		document.addEventListener( 'kaliFormSuccess', successHandler );

		// Cleanup listener after 30 seconds if not fired.
		setTimeout( function () {
			document.removeEventListener( 'kaliFormSuccess', successHandler );
		}, 30000 );
	} );

	/**
	 * Add hidden popup ID field to Kali Forms inside popups.
	 *
	 * This ensures the form submission includes the popup ID for tracking.
	 */
	$( document ).on( 'pumAfterOpen', '.pum', function () {
		const $popup = $( this );
		const popupId = window.PUM.getSetting( $popup, 'id' );

		if ( ! popupId ) {
			return;
		}

		// Find all Kali Forms in this popup.
		$popup.find( 'form[data-form-id]' ).each( function () {
			const $form = $( this );

			// Check if already has popup ID field.
			if ( $form.find( 'input[name="pum_form_popup_id"]' ).length ) {
				return;
			}

			// Check if this is a Kali Forms form.
			const formClass = $form.attr( 'class' );
			if ( ! formClass || ! formClass.includes( 'kali-form' ) ) {
				return;
			}

			// Add hidden field with popup ID.
			$form.append(
				$( '<input>', {
					type: 'hidden',
					name: 'pum_form_popup_id',
					value: popupId,
				} )
			);
		} );
	} );
}
