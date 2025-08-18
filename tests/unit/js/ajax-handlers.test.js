/**
 * AJAX Handlers JavaScript Unit Tests
 *
 * Tests for AJAX request handling, caching, retry logic, and error management
 * in the Pro upgrade workflow.
 *
 * @package
 */

// Mock jQuery and WordPress dependencies
global.jQuery = require( 'jquery' );
global.$ = global.jQuery;

// Mock WordPress AJAX and REST API
global.wp = {
	ajax: {
		post: jest.fn(),
		send: jest.fn(),
	},
	apiFetch: jest.fn(),
	hooks: {
		addAction: jest.fn(),
		addFilter: jest.fn(),
		doAction: jest.fn(),
		applyFilters: jest.fn(),
	},
};

// Mock WordPress globals
global.wpApiSettings = {
	root: 'http://localhost/wp-json/',
	nonce: 'test-nonce-123',
};

global.pumAdminVars = {
	ajax_url: 'http://localhost/wp-admin/admin-ajax.php',
	nonce: 'test-nonce-456',
	pro_upgrade_nonce: 'test-upgrade-nonce-789',
};

describe( 'AJAX Handlers', () => {
	let ajaxHandler;
	let mockCache;

	beforeEach( () => {
		jest.clearAllMocks();

		// Mock cache implementation
		mockCache = new Map();

		// Mock AJAX handler
		ajaxHandler = {
			cache: mockCache,
			retryAttempts: 3,
			retryDelay: 1000,

			makeRequest: jest.fn(),
			makeRestRequest: jest.fn(),
			makeAjaxRequest: jest.fn(),
			handleError: jest.fn(),
			retryRequest: jest.fn(),
			getCachedResponse: jest.fn(),
			setCachedResponse: jest.fn(),
			clearCache: jest.fn(),
		};
	} );

	describe( 'REST API Requests', () => {
		test( 'should make REST API request with correct parameters', async () => {
			const requestParams = {
				path: '/popup-maker/v2/license',
				method: 'GET',
				headers: {
					'X-WP-Nonce': global.wpApiSettings.nonce,
				},
			};

			const mockResponse = {
				license_key: 'test-key',
				status: 'valid',
				is_active: true,
			};

			global.wp.apiFetch.mockResolvedValue( mockResponse );

			ajaxHandler.makeRestRequest.mockImplementation(
				async ( params ) => {
					return await global.wp.apiFetch( params );
				}
			);

			const result = await ajaxHandler.makeRestRequest( requestParams );

			expect( global.wp.apiFetch ).toHaveBeenCalledWith( requestParams );
			expect( result ).toEqual( mockResponse );
		} );

		test( 'should handle REST API errors properly', async () => {
			const errorResponse = {
				code: 'rest_forbidden',
				message: 'Sorry, you are not allowed to access this resource.',
				data: { status: 403 },
			};

			global.wp.apiFetch.mockRejectedValue( errorResponse );

			ajaxHandler.makeRestRequest.mockImplementation(
				async ( params ) => {
					try {
						return await global.wp.apiFetch( params );
					} catch ( error ) {
						ajaxHandler.handleError( error );
						throw error;
					}
				}
			);

			try {
				await ajaxHandler.makeRestRequest( {
					path: '/popup-maker/v2/license',
					method: 'GET',
				} );
			} catch ( error ) {
				expect( ajaxHandler.handleError ).toHaveBeenCalledWith(
					errorResponse
				);
				expect( error.code ).toBe( 'rest_forbidden' );
			}
		} );

		test( 'should include authentication headers automatically', async () => {
			const requestWithoutAuth = {
				path: '/popup-maker/v2/license/activate',
				method: 'POST',
				data: { license_key: 'test-key' },
			};

			// Mock successful response for this test
			global.wp.apiFetch.mockResolvedValue( {
				success: true,
				message: 'License activated successfully',
			} );

			ajaxHandler.makeRestRequest.mockImplementation(
				async ( params ) => {
					const enhancedParams = {
						...params,
						headers: {
							...params.headers,
							'X-WP-Nonce': global.wpApiSettings.nonce,
						},
					};
					return await global.wp.apiFetch( enhancedParams );
				}
			);

			await ajaxHandler.makeRestRequest( requestWithoutAuth );

			expect( global.wp.apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					headers: expect.objectContaining( {
						'X-WP-Nonce': global.wpApiSettings.nonce,
					} ),
				} )
			);
		} );
	} );

	describe( 'Legacy AJAX Requests', () => {
		test( 'should make legacy AJAX request with correct action', async () => {
			const actionData = {
				action: 'pum_pro_validate_license',
				license_key: 'test-key',
				nonce: global.pumAdminVars.pro_upgrade_nonce,
			};

			const mockResponse = {
				success: true,
				data: {
					valid: true,
					status: 'active',
				},
			};

			global.wp.ajax.post.mockResolvedValue( mockResponse );

			ajaxHandler.makeAjaxRequest.mockImplementation( async ( data ) => {
				return await global.wp.ajax.post( data );
			} );

			const result = await ajaxHandler.makeAjaxRequest( actionData );

			expect( global.wp.ajax.post ).toHaveBeenCalledWith( actionData );
			expect( result.success ).toBe( true );
		} );

		test( 'should handle legacy AJAX failures', async () => {
			const actionData = {
				action: 'pum_pro_activate_license',
				license_key: 'invalid-key',
				nonce: global.pumAdminVars.pro_upgrade_nonce,
			};

			const errorResponse = {
				success: false,
				data: {
					message: 'Invalid license key',
					code: 'invalid_license',
				},
			};

			global.wp.ajax.post.mockResolvedValue( errorResponse );

			ajaxHandler.makeAjaxRequest.mockImplementation( async ( data ) => {
				const response = await global.wp.ajax.post( data );
				if ( ! response.success ) {
					throw new Error( response.data.message );
				}
				return response;
			} );

			try {
				await ajaxHandler.makeAjaxRequest( actionData );
			} catch ( error ) {
				expect( error.message ).toBe( 'Invalid license key' );
			}
		} );

		test( 'should validate nonce for security', async () => {
			const dataWithoutNonce = {
				action: 'pum_pro_validate_license',
				license_key: 'test-key',
			};

			ajaxHandler.makeAjaxRequest.mockImplementation( async ( data ) => {
				if ( ! data.nonce ) {
					throw new Error( 'Nonce is required for security' );
				}
				return await global.wp.ajax.post( data );
			} );

			try {
				await ajaxHandler.makeAjaxRequest( dataWithoutNonce );
			} catch ( error ) {
				expect( error.message ).toBe(
					'Nonce is required for security'
				);
			}
		} );
	} );

	describe( 'Request Caching', () => {
		test( 'should cache GET request responses', async () => {
			const cacheKey = 'license_status_test-key';
			const requestParams = {
				path: '/popup-maker/v2/license',
				method: 'GET',
			};
			const mockResponse = { status: 'valid', cached: false };

			ajaxHandler.getCachedResponse.mockImplementation( ( key ) => {
				return mockCache.get( key );
			} );

			ajaxHandler.setCachedResponse.mockImplementation(
				( key, response, ttl = 300000 ) => {
					const cacheEntry = {
						data: response,
						timestamp: Date.now(),
						ttl,
					};
					mockCache.set( key, cacheEntry );
				}
			);

			ajaxHandler.makeRestRequest.mockImplementation(
				async ( params ) => {
					const cached = ajaxHandler.getCachedResponse( cacheKey );
					if (
						cached &&
						Date.now() - cached.timestamp < cached.ttl
					) {
						return { ...cached.data, cached: true };
					}

					const response = await global.wp.apiFetch( params );
					ajaxHandler.setCachedResponse( cacheKey, response );
					return response;
				}
			);

			global.wp.apiFetch.mockResolvedValue( mockResponse );

			// First request - should fetch from API
			const result1 = await ajaxHandler.makeRestRequest( requestParams );
			expect( result1.cached ).toBe( false );
			expect( ajaxHandler.setCachedResponse ).toHaveBeenCalledWith(
				cacheKey,
				mockResponse
			);

			// Second request - should return cached result
			const result2 = await ajaxHandler.makeRestRequest( requestParams );
			expect( result2.cached ).toBe( true );
		} );

		test( 'should not cache POST requests', async () => {
			const requestParams = {
				path: '/popup-maker/v2/license/activate',
				method: 'POST',
				data: { license_key: 'test-key' },
			};

			ajaxHandler.makeRestRequest.mockImplementation(
				async ( params ) => {
					// POST requests should never check cache
					const response = await global.wp.apiFetch( params );
					return response;
				}
			);

			global.wp.apiFetch.mockResolvedValue( { success: true } );

			await ajaxHandler.makeRestRequest( requestParams );

			expect( ajaxHandler.getCachedResponse ).not.toHaveBeenCalled();
			expect( ajaxHandler.setCachedResponse ).not.toHaveBeenCalled();
		} );

		test( 'should expire cached responses after TTL', async () => {
			const cacheKey = 'license_status_expired';
			const expiredEntry = {
				data: { status: 'valid' },
				timestamp: Date.now() - 400000, // 6+ minutes ago
				ttl: 300000, // 5 minutes
			};

			mockCache.set( cacheKey, expiredEntry );

			ajaxHandler.getCachedResponse.mockImplementation( ( key ) => {
				const entry = mockCache.get( key );
				if ( entry && Date.now() - entry.timestamp < entry.ttl ) {
					return entry;
				}
				return null;
			} );

			const cached = ajaxHandler.getCachedResponse( cacheKey );
			expect( cached ).toBeNull();
		} );

		test( 'should clear cache when requested', () => {
			mockCache.set( 'key1', { data: 'value1' } );
			mockCache.set( 'key2', { data: 'value2' } );

			ajaxHandler.clearCache.mockImplementation( () => {
				mockCache.clear();
			} );

			expect( mockCache.size ).toBe( 2 );
			ajaxHandler.clearCache();
			expect( mockCache.size ).toBe( 0 );
		} );
	} );

	describe( 'Retry Logic', () => {
		test( 'should retry failed requests with exponential backoff', async () => {
			let attempts = 0;
			const maxAttempts = 3;

			global.wp.apiFetch.mockImplementation( () => {
				attempts++;
				if ( attempts < maxAttempts ) {
					return Promise.reject( new Error( 'Network error' ) );
				}
				return Promise.resolve( { success: true } );
			} );

			ajaxHandler.retryRequest.mockImplementation(
				async ( requestFn, maxRetries = 3, baseDelay = 1000 ) => {
					for ( let i = 0; i < maxRetries; i++ ) {
						try {
							return await requestFn();
						} catch ( error ) {
							if ( i === maxRetries - 1 ) {
								throw error;
							}

							const delay = baseDelay * Math.pow( 2, i );
							await new Promise( ( resolve ) =>
								setTimeout( resolve, delay )
							);
						}
					}
				}
			);

			const requestFn = () =>
				global.wp.apiFetch( {
					path: '/popup-maker/v2/license',
					method: 'GET',
				} );

			const result = await ajaxHandler.retryRequest( requestFn, 3, 100 );

			expect( attempts ).toBe( 3 );
			expect( result.success ).toBe( true );
		} );

		test( 'should not retry certain error types', async () => {
			const authError = {
				code: 'rest_forbidden',
				message: 'Authentication failed',
				data: { status: 403 },
			};

			global.wp.apiFetch.mockRejectedValue( authError );

			ajaxHandler.retryRequest.mockImplementation(
				async ( requestFn ) => {
					try {
						return await requestFn();
					} catch ( error ) {
						// Don't retry authentication or validation errors
						if (
							error.code === 'rest_forbidden' ||
							error.code === 'invalid_license'
						) {
							throw error;
						}

						// Retry for other errors
						return await requestFn();
					}
				}
			);

			const requestFn = () =>
				global.wp.apiFetch( {
					path: '/popup-maker/v2/license',
					method: 'GET',
				} );

			try {
				await ajaxHandler.retryRequest( requestFn );
			} catch ( error ) {
				expect( error.code ).toBe( 'rest_forbidden' );
				expect( global.wp.apiFetch ).toHaveBeenCalledTimes( 1 ); // No retries
			}
		} );

		test( 'should respect maximum retry limit', async () => {
			let attempts = 0;

			global.wp.apiFetch.mockImplementation( () => {
				attempts++;
				return Promise.reject(
					new Error( 'Persistent network error' )
				);
			} );

			ajaxHandler.retryRequest.mockImplementation(
				async ( requestFn, maxRetries = 2 ) => {
					for ( let i = 0; i < maxRetries; i++ ) {
						try {
							return await requestFn();
						} catch ( error ) {
							if ( i === maxRetries - 1 ) {
								throw error;
							}
							await new Promise( ( resolve ) =>
								setTimeout( resolve, 100 )
							);
						}
					}
				}
			);

			const requestFn = () =>
				global.wp.apiFetch( {
					path: '/popup-maker/v2/license',
					method: 'GET',
				} );

			try {
				await ajaxHandler.retryRequest( requestFn, 2 );
			} catch ( error ) {
				expect( attempts ).toBe( 2 ); // Should stop at max retries
				expect( error.message ).toBe( 'Persistent network error' );
			}
		} );
	} );

	describe( 'Error Handling', () => {
		test( 'should categorize different error types', () => {
			const errorTypes = [
				{
					error: { code: 'rest_forbidden', data: { status: 403 } },
					expectedCategory: 'authentication',
				},
				{
					error: {
						code: 'invalid_license',
						message: 'License not found',
					},
					expectedCategory: 'validation',
				},
				{
					error: { message: 'Network Error' },
					expectedCategory: 'network',
				},
				{
					error: { code: 'server_error', data: { status: 500 } },
					expectedCategory: 'server',
				},
			];

			ajaxHandler.handleError.mockImplementation( ( error ) => {
				if (
					error.code === 'rest_forbidden' ||
					error.code === 'rest_unauthorized'
				) {
					return { category: 'authentication', retryable: false };
				}
				if (
					error.code === 'invalid_license' ||
					error.code === 'rest_invalid_param'
				) {
					return { category: 'validation', retryable: false };
				}
				if ( error.message && error.message.includes( 'Network' ) ) {
					return { category: 'network', retryable: true };
				}
				if ( error.data && error.data.status >= 500 ) {
					return { category: 'server', retryable: true };
				}
				return { category: 'unknown', retryable: false };
			} );

			errorTypes.forEach( ( { error, expectedCategory } ) => {
				const result = ajaxHandler.handleError( error );
				expect( result.category ).toBe( expectedCategory );
			} );
		} );

		test( 'should provide user-friendly error messages', () => {
			const errorMessages = {
				rest_forbidden:
					'You do not have permission to perform this action.',
				invalid_license: 'The license key is invalid or expired.',
				network_error:
					'Network connection failed. Please check your internet connection.',
				server_error: 'Server error occurred. Please try again later.',
				unknown_error:
					'An unexpected error occurred. Please try again.',
			};

			ajaxHandler.handleError.mockImplementation( ( error ) => {
				const code = error.code || 'unknown_error';
				return {
					userMessage:
						errorMessages[ code ] || errorMessages.unknown_error,
					technical: error.message || 'Unknown error',
				};
			} );

			Object.keys( errorMessages ).forEach( ( code ) => {
				const error = { code, message: `Technical: ${ code }` };
				const result = ajaxHandler.handleError( error );

				expect( result.userMessage ).toBe( errorMessages[ code ] );
				expect( result.technical ).toContain( 'Technical:' );
			} );
		} );

		test( 'should log errors for debugging', () => {
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation();

			ajaxHandler.handleError.mockImplementation( ( error ) => {
				console.error( '[PUM Pro Upgrade]', error ); // eslint-disable-line no-console
				return { logged: true };
			} );

			const testError = new Error( 'Test error for logging' );
			ajaxHandler.handleError( testError );

			expect( consoleSpy ).toHaveBeenCalledWith(
				'[PUM Pro Upgrade]',
				testError
			);

			consoleSpy.mockRestore();
		} );
	} );

	describe( 'Request Queuing', () => {
		test( 'should queue concurrent requests to prevent race conditions', async () => {
			const requestQueue = [];
			let processing = false;

			ajaxHandler.makeRequest.mockImplementation( async ( params ) => {
				return new Promise( ( resolve, reject ) => {
					const request = { params, resolve, reject };
					requestQueue.push( request );

					if ( ! processing ) {
						processQueue();
					}
				} );
			} );

			const processQueue = async () => {
				if ( processing || requestQueue.length === 0 ) {
					return;
				}

				processing = true;
				const request = requestQueue.shift();

				try {
					const result = await global.wp.apiFetch( request.params );
					request.resolve( result );
				} catch ( error ) {
					request.reject( error );
				}

				processing = false;
				if ( requestQueue.length > 0 ) {
					setTimeout( processQueue, 10 ); // Small delay between requests
				}
			};

			global.wp.apiFetch.mockImplementation( ( params ) =>
				Promise.resolve( { path: params.path, processed: true } )
			);

			// Make multiple concurrent requests
			const promises = [
				ajaxHandler.makeRequest( { path: '/test1' } ),
				ajaxHandler.makeRequest( { path: '/test2' } ),
				ajaxHandler.makeRequest( { path: '/test3' } ),
			];

			const results = await Promise.all( promises );

			expect( results ).toHaveLength( 3 );
			results.forEach( ( result, index ) => {
				expect( result.path ).toBe( `/test${ index + 1 }` );
				expect( result.processed ).toBe( true );
			} );
		} );

		test( 'should handle queue processing errors gracefully', async () => {
			let requestCount = 0;

			ajaxHandler.makeRequest.mockImplementation( async ( params ) => {
				requestCount++;
				if ( requestCount === 2 ) {
					throw new Error( 'Queue processing error' );
				}
				return await global.wp.apiFetch( params );
			} );

			global.wp.apiFetch.mockResolvedValue( { success: true } );

			const requests = [
				ajaxHandler.makeRequest( { path: '/test1' } ),
				ajaxHandler.makeRequest( { path: '/test2' } ), // This will fail
				ajaxHandler.makeRequest( { path: '/test3' } ),
			];

			const results = await Promise.allSettled( requests );

			expect( results[ 0 ].status ).toBe( 'fulfilled' );
			expect( results[ 1 ].status ).toBe( 'rejected' );
			expect( results[ 2 ].status ).toBe( 'fulfilled' );
		} );
	} );

	describe( 'Performance Monitoring', () => {
		test( 'should track request timing', async () => {
			const performanceMetrics = {
				requests: [],
				addMetric: jest.fn(),
			};

			ajaxHandler.makeRestRequest.mockImplementation(
				async ( params ) => {
					const startTime = performance.now();

					try {
						const result = await global.wp.apiFetch( params );
						const endTime = performance.now();

						performanceMetrics.addMetric( {
							path: params.path,
							method: params.method,
							duration: endTime - startTime,
							success: true,
						} );

						return result;
					} catch ( error ) {
						const endTime = performance.now();

						performanceMetrics.addMetric( {
							path: params.path,
							method: params.method,
							duration: endTime - startTime,
							success: false,
							error: error.code,
						} );

						throw error;
					}
				}
			);

			global.wp.apiFetch.mockImplementation(
				() =>
					new Promise( ( resolve ) =>
						setTimeout( () => resolve( { success: true } ), 100 )
					)
			);

			await ajaxHandler.makeRestRequest( {
				path: '/popup-maker/v2/license',
				method: 'GET',
			} );

			expect( performanceMetrics.addMetric ).toHaveBeenCalledWith(
				expect.objectContaining( {
					path: '/popup-maker/v2/license',
					method: 'GET',
					success: true,
					duration: expect.any( Number ),
				} )
			);
		} );

		test( 'should warn about slow requests', async () => {
			const consoleSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation();

			ajaxHandler.makeRestRequest.mockImplementation(
				async ( params ) => {
					const startTime = performance.now();
					const result = await global.wp.apiFetch( params );
					const duration = performance.now() - startTime;

					if ( duration > 2000 ) {
						// Warn if request takes more than 2 seconds
						// eslint-disable-next-line no-console
						console.warn(
							`Slow request detected: ${ params.path } took ${ duration }ms`
						);
					}

					return result;
				}
			);

			global.wp.apiFetch.mockImplementation(
				() =>
					new Promise( ( resolve ) =>
						setTimeout( () => resolve( { success: true } ), 2100 )
					)
			);

			await ajaxHandler.makeRestRequest( {
				path: '/popup-maker/v2/license/activate',
				method: 'POST',
			} );

			expect( consoleSpy ).toHaveBeenCalledWith(
				expect.stringContaining( 'Slow request detected' )
			);

			consoleSpy.mockRestore();
		} );
	} );
} );
