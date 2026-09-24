describe( 'Formidable Forms success integration', () => {
	let handler;
	let formSubmission;
	let savingDraft;

	beforeEach( () => {
		jest.resetModules();
		formSubmission = jest.fn();
		savingDraft = undefined;
		window.PUM = {
			getPopup: jest.fn( () => 'popup' ),
			integrations: { formSubmission },
		};
		window.jQuery = jest.fn( ( target ) => {
			if ( target === document ) {
				return {
					on: jest.fn( ( _eventName, callback ) => {
						handler = callback;
					} ),
				};
			}

			return {
				find: jest.fn( ( selector ) => ( {
					val: () => {
						if ( 'input[name="frm_saving_draft"]' === selector ) {
							return savingDraft;
						}
						return 'form_id' ===
							selector.match( /name="([^"]+)"/ )?.[ 1 ]
							? '7'
							: '42';
					},
				} ) ),
			};
		} );

		require( '../../assets/js/src/integration/formidableforms' );
	} );

	test( 'ignores AJAX draft completion messages', () => {
		savingDraft = '1';
		handler( {}, document.createElement( 'form' ), {
			content: '<div class="frm_message">Draft saved</div>',
		} );

		expect( formSubmission ).not.toHaveBeenCalled();
		expect( window.PUM.getPopup ).not.toHaveBeenCalled();
	} );

	test( 'observes submitted parent completion messages once', () => {
		handler( {}, document.createElement( 'form' ), {
			content: '<div class="frm_message">Success</div>',
		} );

		expect( formSubmission ).toHaveBeenCalledTimes( 1 );
		expect( formSubmission ).toHaveBeenCalledWith( expect.anything(), {
			popup: 'popup',
			formProvider: 'formidableforms',
			formId: '7',
			extras: {
				response: { content: '<div class="frm_message">Success</div>' },
			},
		} );
	} );
} );
