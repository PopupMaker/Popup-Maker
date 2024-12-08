/*******************************************************************************
 * Copyright (c) 2020, WP Popup Maker
 ******************************************************************************/

{
	const formProvider = 'calderaforms';
	const $ = window.jQuery;
	let $form;

	/**
	 * This function is run before every CF Ajax call to store the form being submitted.
	 *
	 * @param event
	 * @param obj
	 */
	const beforeAjax = ( event, obj ) => ( $form = obj.$form );

	$( document )
		.on( 'cf.ajax.request', beforeAjax )
		// After all requests
		.on( 'cf.submission', function ( event, obj ) {
			// Only if status of request is complete|success.
			if (
				'complete' === obj.data.status ||
				'success' === obj.data.status
			) {
				//get the form that is submiting's ID attribute
				const [ formId, formInstanceId = null ] = $form
					.attr( 'id' )
					.split( '_' );

				// All the magic happens here.
				window.PUM.integrations.formSubmission( $form, {
					formProvider,
					formId,
					formInstanceId,
					extras: {
						state: window.cfstate.hasOwnProperty( formId )
							? window.cfstate[ formId ]
							: null,
					},
				} );
			}
		} );
}
