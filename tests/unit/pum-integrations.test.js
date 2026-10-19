describe( 'PUM normalized form submission context', () => {
	let applyFilters;
	let doAction;

	beforeEach( () => {
		jest.resetModules();

		applyFilters = jest.fn( ( hookName, args ) => args );
		doAction = jest.fn();

		window.pum_vars = {};
		window.PUM = {
			getPopup: jest.fn( () => ( { length: 0 } ) ),
			getSetting: jest.fn(),
			hooks: {
				applyFilters,
				doAction,
			},
			integrations: {},
		};
		window.jQuery = {
			extend: ( target, ...sources ) =>
				Object.assign( target, ...sources ),
		};

		require( '../../assets/js/src/site/plugins/pum-integrations' );
	} );

	test( 'generates portable defaults before dispatch', () => {
		window.PUM.integrations.formSubmission( null, {
			formProvider: 'gravityforms',
			formId: 7,
		} );

		const [ hookName, form, args ] = doAction.mock.calls[ 0 ];

		expect( hookName ).toBe( 'pum.integration.form.success' );
		expect( form ).toBeNull();
		expect( args.submissionId ).toEqual( expect.any( String ) );
		expect( args.sourcePostId ).toBeNull();
		expect( args.sourceUrl ).toBe( window.location.href );
		expect( args.context ).toEqual( {} );
		expect( args.formKey ).toBe( 'gravityforms_7' );
		expect( applyFilters.mock.calls[ 0 ][ 1 ].submissionId ).toEqual(
			expect.any( String )
		);
	} );

	test( 'filters context and preserves a provider submission ID', () => {
		applyFilters.mockImplementation( ( hookName, args ) => ( {
			...args,
			context: {
				exampleExtension: { campaignId: 90 },
			},
		} ) );

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'fluentforms',
			formId: 4,
			submissionId: 'entry-12',
		} );

		expect( applyFilters ).toHaveBeenCalledWith(
			'pum.integration.form.submissionArgs',
			expect.objectContaining( {
				submissionId: 'entry-12',
			} ),
			null
		);

		const args = doAction.mock.calls[ 0 ][ 2 ];
		expect( args.submissionId ).toBe( 'entry-12' );
		expect( args.context.exampleExtension.campaignId ).toBe( 90 );
	} );

	test( 'retains the canonical ID when a filter returns invalid values', () => {
		let canonicalSubmissionId;

		applyFilters.mockImplementation( ( hookName, args ) => {
			canonicalSubmissionId = args.submissionId;

			return {
				...args,
				submissionId: null,
				context: [],
			};
		} );

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'gravityforms',
			formId: 7,
		} );

		const args = doAction.mock.calls[ 0 ][ 2 ];
		expect( args.submissionId ).toBe( canonicalSubmissionId );
		expect( args.context ).toEqual( {} );
	} );

	test( 'replaces non-finite numeric submission IDs', () => {
		[ NaN, Infinity, -Infinity ].forEach( ( submissionId ) => {
			window.PUM.integrations.formSubmission( null, {
				formProvider: 'gravityforms',
				formId: 7,
				submissionId,
			} );
		} );

		const ids = doAction.mock.calls.map(
			( call ) => call[ 2 ].submissionId
		);
		expect( ids ).toHaveLength( 3 );
		ids.forEach( ( submissionId ) => {
			expect( submissionId ).toEqual( expect.any( String ) );
			expect( submissionId ).not.toHaveLength( 0 );
		} );
	} );
} );
