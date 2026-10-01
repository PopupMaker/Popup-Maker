describe( 'WS Form success integration', () => {
	let eventName;
	let handler;
	let formSubmission;

	beforeEach( () => {
		jest.resetModules();
		formSubmission = jest.fn();
		window.PUM = { integrations: { formSubmission } };
		window.jQuery = jest.fn( () => ( {
			on: jest.fn( ( registeredEvent, callback ) => {
				eventName = registeredEvent;
				handler = callback;
			} ),
		} ) );

		require( '../../assets/js/src/integration/wsforms' );
	} );

	test( 'observes submit success and never registers save success', () => {
		expect( eventName ).toBe( 'wsf-submit-success' );
		expect( eventName ).not.toContain( 'wsf-save-success' );

		const formElement = document.createElement( 'form' );
		handler( {}, {}, 7, 'instance-1', formElement, {} );

		expect( formSubmission ).toHaveBeenCalledTimes( 1 );
		expect( formSubmission ).toHaveBeenCalledWith( expect.anything(), {
			formProvider: 'wsforms',
			formId: 7,
			formInstanceId: 'instance-1',
		} );
	} );
} );
