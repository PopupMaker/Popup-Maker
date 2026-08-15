describe( 'PUM normalized form submission phases', () => {
	let applyFilters;
	let doAction;
	let popup;

	const loadIntegrations = ( pumVars = {} ) => {
		window.pum_vars = pumVars;
		require( '../../assets/js/src/site/plugins/pum-integrations' );
	};

	const actionArgs = ( name, occurrence = 0 ) =>
		doAction.mock.calls.filter( ( call ) => call[ 0 ] === name )[
			occurrence
		]?.[ 2 ];

	beforeEach( () => {
		jest.resetModules();

		applyFilters = jest.fn( ( hookName, value ) => value );
		doAction = jest.fn();
		popup = {
			length: 0,
			trigger: jest.fn(),
		};

		window.PUM = {
			getPopup: jest.fn( () => popup ),
			getSetting: jest.fn( () => 42 ),
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
	} );

	test( 'generates portable defaults and dispatches observation plus actions', () => {
		loadIntegrations();

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'gravityforms',
			formId: 7,
		} );

		const args = actionArgs( 'pum.integration.form.observed' );

		expect( args.submissionId ).toEqual( expect.any( String ) );
		expect( args.sourcePostId ).toBeNull();
		expect( args.sourceUrl ).toBe( window.location.href );
		expect( args.context ).toEqual( {} );
		expect( args.formKey ).toBe( 'gravityforms_7' );
		expect( args.phases ).toEqual( {
			actions: true,
			tracking: true,
			frontend: true,
		} );
		expect( actionArgs( 'pum.integration.form.success' ) ).toBe( args );
		expect( actionArgs( 'pum.integration.form.actions' ) ).toBe( args );
	} );

	test( 'filters context and phases while preserving a native submission ID', () => {
		applyFilters.mockImplementation( ( hookName, value ) => {
			if ( 'pum.integration.form.submissionArgs' === hookName ) {
				return {
					...value,
					context: {
						exampleExtension: { campaignId: 90 },
					},
				};
			}

			if ( 'pum.integration.form.phases' === hookName ) {
				return { ...value, tracking: false };
			}

			return value;
		} );
		loadIntegrations();

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
		expect( applyFilters ).toHaveBeenCalledWith(
			'pum.integration.form.phases',
			{
				actions: true,
				tracking: true,
				frontend: true,
			},
			expect.objectContaining( { submissionId: 'entry-12' } ),
			null
		);

		const args = actionArgs( 'pum.integration.form.observed' );
		expect( args.context.exampleExtension.campaignId ).toBe( 90 );
		expect( args.phases.tracking ).toBe( false );
	} );

	test( 'retains the canonical ID when a filter returns invalid values', () => {
		let canonicalSubmissionId;

		applyFilters.mockImplementation( ( hookName, value ) => {
			if ( 'pum.integration.form.submissionArgs' !== hookName ) {
				return value;
			}

			canonicalSubmissionId = value.submissionId;

			return {
				...value,
				submissionId: null,
				context: [],
			};
		} );
		loadIntegrations();

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'gravityforms',
			formId: 7,
		} );

		const args = actionArgs( 'pum.integration.form.observed' );
		expect( args.submissionId ).toBe( canonicalSubmissionId );
		expect( args.context ).toEqual( {} );
	} );

	test( 'a tracking receipt cannot be opted back into tracking', () => {
		loadIntegrations();

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'gravityforms',
			formId: 7,
			tracked: true,
			phases: { tracking: true },
		} );

		expect(
			actionArgs( 'pum.integration.form.observed' ).phases.tracking
		).toBe( false );
	} );

	test( 'repeated native submissions only fire the observation hook', () => {
		loadIntegrations();
		const submission = {
			formProvider: 'fluentforms',
			formId: 4,
			submissionId: 'entry-12',
		};

		window.PUM.integrations.formSubmission( null, submission );
		window.PUM.integrations.formSubmission( null, submission );

		expect(
			doAction.mock.calls.filter(
				( call ) => 'pum.integration.form.observed' === call[ 0 ]
			)
		).toHaveLength( 2 );
		expect(
			doAction.mock.calls.filter(
				( call ) => 'pum.integration.form.success' === call[ 0 ]
			)
		).toHaveLength( 1 );
		expect(
			doAction.mock.calls.filter(
				( call ) => 'pum.integration.form.actions' === call[ 0 ]
			)
		).toHaveLength( 1 );
		expect(
			actionArgs( 'pum.integration.form.observed', 1 ).phases
		).toEqual( {
			actions: false,
			tracking: false,
			frontend: false,
		} );
	} );

	test( 'equivalent numeric and string receipts deduplicate', () => {
		loadIntegrations();

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'fluentforms',
			formId: 4,
			submissionId: 12,
		} );
		window.PUM.integrations.formSubmission( null, {
			formProvider: 'fluentforms',
			formId: '4',
			submissionId: '12',
		} );

		expect(
			doAction.mock.calls.filter(
				( call ) => 'pum.integration.form.actions' === call[ 0 ]
			)
		).toHaveLength( 1 );
		expect(
			actionArgs( 'pum.integration.form.observed', 1 ).phases
		).toEqual( {
			actions: false,
			tracking: false,
			frontend: false,
		} );
	} );

	test( 'native submission dedupe history is bounded', () => {
		loadIntegrations();

		for ( let id = 0; id <= 100; id++ ) {
			window.PUM.integrations.formSubmission( null, {
				formProvider: 'fluentforms',
				formId: 4,
				submissionId: `entry-${ id }`,
			} );
		}

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'fluentforms',
			formId: 4,
			submissionId: 'entry-0',
		} );

		expect(
			doAction.mock.calls.filter(
				( call ) => 'pum.integration.form.actions' === call[ 0 ]
			)
		).toHaveLength( 102 );
	} );

	test( 'generated event IDs do not claim deduplication', () => {
		loadIntegrations();

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'fluentforms',
			formId: 4,
		} );
		window.PUM.integrations.formSubmission( null, {
			formProvider: 'fluentforms',
			formId: 4,
		} );

		expect(
			doAction.mock.calls.filter(
				( call ) => 'pum.integration.form.actions' === call[ 0 ]
			)
		).toHaveLength( 2 );
	} );

	test( 'localized non-AJAX replay preserves frontend effects only', () => {
		loadIntegrations( {
			form_submission: {
				popupId: 42,
				formProvider: 'fluentforms',
				formId: 4,
				submissionId: 'entry-12',
				phases: {
					actions: true,
					tracking: true,
					frontend: true,
				},
			},
		} );
		popup.length = 1;

		window.PUM.integrations.init();

		const args = actionArgs( 'pum.integration.form.success' );
		expect( args.ajax ).toBe( false );
		expect( args.tracked ).toBe( true );
		expect( args.phases ).toEqual( {
			actions: false,
			tracking: false,
			frontend: true,
		} );
		expect( popup.trigger ).toHaveBeenCalledWith( 'pumConversion' );
		expect( actionArgs( 'pum.integration.form.actions' ) ).toBeUndefined();
	} );

	test( 'frontend suppression prevents popup effects but not observation', () => {
		loadIntegrations();
		popup.length = 1;

		window.PUM.integrations.formSubmission( null, {
			formProvider: 'fluentforms',
			formId: 4,
			phases: { frontend: false },
		} );

		expect( popup.trigger ).not.toHaveBeenCalled();
		expect( actionArgs( 'pum.integration.form.success' ) ).toBeUndefined();
		expect( actionArgs( 'pum.integration.form.observed' ) ).toBeDefined();
	} );

	test( 'replaces non-finite numeric submission IDs', () => {
		loadIntegrations();

		[ NaN, Infinity, -Infinity ].forEach( ( submissionId ) => {
			window.PUM.integrations.formSubmission( null, {
				formProvider: 'gravityforms',
				formId: 7,
				submissionId,
			} );
		} );

		const ids = doAction.mock.calls
			.filter( ( call ) => 'pum.integration.form.observed' === call[ 0 ] )
			.map( ( call ) => call[ 2 ].submissionId );
		expect( ids ).toHaveLength( 3 );
		ids.forEach( ( submissionId ) => {
			expect( submissionId ).toEqual( expect.any( String ) );
			expect( submissionId ).not.toHaveLength( 0 );
		} );
	} );
} );
