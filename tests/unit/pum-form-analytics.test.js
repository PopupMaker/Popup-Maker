describe( 'PUM form analytics phase policy', () => {
	let formSuccessHandler;
	let jqueryResult;

	beforeEach( () => {
		jest.resetModules();
		jest.useFakeTimers();

		jqueryResult = {
			on: jest.fn().mockReturnThis(),
			hasClass: jest.fn( () => false ),
		};
		const jquery = jest.fn( ( value ) => {
			if ( 'function' === typeof value ) {
				value();
			}

			return jqueryResult;
		} );
		jquery.fn = { popmake: {} };
		jquery.extend = ( target, ...sources ) =>
			Object.assign( target, ...sources );
		jquery.param = jest.fn( () => '' );

		window.jQuery = jquery;
		global.jQuery = jquery;
		window.pum_vars = {
			analytics_enabled: true,
			analytics_api: '',
			ajaxurl: '',
		};
		global.pum_vars = window.pum_vars;
		window.pum = {
			hooks: {
				applyFilters: jest.fn( ( hookName, value ) => value ),
			},
		};
		global.pum = window.pum;
		window.PUM = {
			hooks: {
				addAction: jest.fn( ( hookName, callback ) => {
					if ( 'pum.integration.form.success' === hookName ) {
						formSuccessHandler = callback;
					}
				} ),
			},
		};

		require( '../../assets/js/src/site/plugins/pum-analytics' );
	} );

	afterEach( () => {
		window.PUM_Analytics.flush();
		jest.useRealTimers();
	} );

	test( 'only queues conversions authorized for browser tracking', () => {
		const beacon = jest.spyOn( window.PUM_Analytics, 'beacon' );
		const popup = {
			length: 1,
			popmake: jest.fn( () => ( { id: 42 } ) ),
		};
		const args = {
			ajax: true,
			tracked: false,
			phases: { tracking: false },
			popup,
		};

		formSuccessHandler( null, args );
		formSuccessHandler( null, {
			...args,
			tracked: true,
			phases: { tracking: true },
		} );
		formSuccessHandler( null, {
			...args,
			ajax: false,
			phases: { tracking: true },
		} );

		expect( beacon ).not.toHaveBeenCalled();

		formSuccessHandler( null, {
			...args,
			phases: { tracking: true },
			submissionId: 'entry-12',
		} );

		expect( beacon ).toHaveBeenCalledWith(
			expect.objectContaining( {
				event: 'conversion',
				eventData: expect.objectContaining( {
					submissionId: 'entry-12',
					phases: { tracking: true },
				} ),
			} )
		);
	} );
} );
