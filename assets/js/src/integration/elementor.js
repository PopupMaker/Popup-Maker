/*******************************************************************************
 * Copyright (c) 2024, WP Popup Maker
 ******************************************************************************/
{
	const formProvider = 'elementor';
	const $ = window.jQuery;

	const refreshWidgets = ( popup ) => {
		const elementorRoot = popup.querySelector( '.elementor' );

		if ( ! elementorRoot ) {
			return;
		}

		if (
			window.elementorFrontend &&
			window.elementorFrontend.elementsHandler &&
			typeof window.elementorFrontend.elementsHandler.runReadyTrigger ===
				'function'
		) {
			$( elementorRoot )
				.find( '[data-element_type]' )
				.addBack( '[data-element_type]' )
				.each( function () {
					window.elementorFrontend.elementsHandler.runReadyTrigger(
						this
					);
				} );
		}
	};

	// Elementor Forms success event.
	$( document ).on( 'submit_success', '.elementor-form', function () {
		const $form = $( this );

		// Get element_id from the widget container.
		// Elementor form widgets are inside a .elementor-element-{id} container.
		const $widget = $( this ).closest( '[data-id]' );
		const elementId = $widget.length
			? $widget.attr( 'data-id' )
			: 'unknown';

		window.PUM.integrations.formSubmission( $form, {
			formProvider,
			formId: elementId,
		} );
	} );

	// Reinitialize Elementor widgets after their popup becomes visible.
	$( document ).on( 'pumAfterOpen', '.pum', function () {
		refreshWidgets( this );
	} );
}
