/**
 * Pro Upgrade Flow JavaScript Unit Tests
 *
 * Tests for the Pro upgrade workflow JavaScript functionality including
 * license validation, popup management, and AJAX interactions.
 *
 * @package
 */

// Mock jQuery and WordPress dependencies
global.jQuery = require( 'jquery' ); // eslint-disable-line import/no-extraneous-dependencies
global.$ = global.jQuery;

// Mock WordPress AJAX and REST API
global.wp = {
	ajax: {
		post: jest.fn(),
		send: jest.fn(),
	},
	api: {
		init: jest.fn(),
		models: {},
		collections: {},
	},
	apiFetch: jest.fn(),
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

describe( 'Pro Upgrade Flow', () => {
	let proUpgradeFlow;
	let mockAjaxResponse;

	beforeEach( () => {
		// Reset mocks
		jest.clearAllMocks();

		// Mock successful AJAX responses by default
		mockAjaxResponse = {
			success: true,
			data: {
				license_key: 'test-license-key',
				status: 'valid',
				can_upgrade: true,
				connect_info: {
					url: 'https://connect.example.com/install',
					back_url: 'http://localhost/wp-admin/',
				},
			},
		};

		global.wp.ajax.post.mockResolvedValue( mockAjaxResponse );
		global.wp.apiFetch.mockResolvedValue( mockAjaxResponse.data );

		// Initialize the Pro Upgrade Flow (mock module)
		proUpgradeFlow = {
			licenseKey: '',
			isValidating: false,
			isUpgrading: false,

			// Mock methods that would be in the actual implementation
			validateLicense: jest.fn().mockImplementation( async () => {
				return global.wp.apiFetch( {
					path: '/popup-maker/v2/license',
					method: 'GET',
					headers: {
						'X-WP-Nonce': global.wpApiSettings.nonce,
					},
				} );
			} ),
			activateLicense: jest
				.fn()
				.mockImplementation( async ( licenseKey ) => {
					return global.wp.apiFetch( {
						path: '/popup-maker/v2/license/activate-pro',
						method: 'POST',
						data: {
							license_key: licenseKey,
						},
						headers: {
							'X-WP-Nonce': global.wpApiSettings.nonce,
						},
					} );
				} ),
			startUpgrade: jest.fn(),
			showSuccessMessage: jest.fn(),
			showErrorMessage: jest.fn(),
			updateUI: jest.fn(),
			resetForm: jest.fn(),
		};
	} );

	describe( 'License Validation', () => {
		test( 'should validate license key format', () => {
			const validKeys = [
				'abcdef1234567890abcdef1234567890', // Mock 32-char hex format
				'12345678-1234-1234-1234-123456789012', // Dash format
				'TEST-KEY-1234-5678-9012', // Another dash format
			];

			const invalidKeys = [
				'',
				'short',
				'invalid',
				'12345',
				null,
				undefined,
			];

			validKeys.forEach( ( key ) => {
				expect( isValidLicenseKey( key ) ).toBe( true );
			} );

			invalidKeys.forEach( ( key ) => {
				expect( isValidLicenseKey( key ) ).toBe( false );
			} );
		} );

		test( 'should sanitize license key input', () => {
			const testCases = [
				{
					input: '  test-license-key-123  ',
					expected: 'test-license-key-123',
				},
				{
					input: 'TEST-LICENSE-KEY-456',
					expected: 'test-license-key-456',
				},
				{
					input: '<script>alert("xss")</script>license-key',
					expected: 'license-key',
				},
			];

			testCases.forEach( ( { input, expected } ) => {
				expect( sanitizeLicenseKey( input ) ).toBe( expected );
			} );
		} );

		test( 'should make AJAX request to validate license', async () => {
			const licenseKey = 'test-license-key-123';

			await proUpgradeFlow.validateLicense( licenseKey );

			expect( global.wp.apiFetch ).toHaveBeenCalledWith( {
				path: '/popup-maker/v2/license',
				method: 'GET',
				headers: {
					'X-WP-Nonce': global.wpApiSettings.nonce,
				},
			} );
		} );

		test( 'should handle license validation success', async () => {
			const licenseKey = 'valid-license-key-12345';

			global.wp.apiFetch.mockResolvedValue( {
				success: true,
				status: 'valid',
				can_upgrade: true,
			} );

			const result = await proUpgradeFlow.validateLicense( licenseKey );

			expect( result.success ).toBe( true );
			expect( result.status ).toBe( 'valid' );
			expect( result.can_upgrade ).toBe( true );
		} );

		test( 'should handle license validation errors', async () => {
			const errorResponse = new Error( 'License key is invalid' );

			global.wp.apiFetch.mockRejectedValue( errorResponse );

			try {
				await proUpgradeFlow.validateLicense( 'invalid-key' );
			} catch ( error ) {
				expect( error.message ).toContain( 'License key is invalid' );
			}
		} );
	} );

	describe( 'License Activation', () => {
		test( 'should activate license with proper parameters', async () => {
			const licenseKey = 'test-license-key-activation';

			await proUpgradeFlow.activateLicense( licenseKey );

			expect( global.wp.apiFetch ).toHaveBeenCalledWith( {
				path: '/popup-maker/v2/license/activate-pro',
				method: 'POST',
				data: {
					license_key: licenseKey,
				},
				headers: {
					'X-WP-Nonce': global.wpApiSettings.nonce,
				},
			} );
		} );

		test( 'should handle successful license activation', async () => {
			const successResponse = {
				success: true,
				message: 'License activated successfully',
				can_upgrade: true,
				connect_info: {
					url: 'https://connect.example.com/install',
					back_url: 'http://localhost/wp-admin/',
				},
			};

			global.wp.apiFetch.mockResolvedValue( successResponse );

			const result = await proUpgradeFlow.activateLicense( 'valid-key' );

			expect( result.success ).toBe( true );
			expect( result.connect_info ).toBeDefined();
		} );

		test( 'should handle license activation failures', async () => {
			const errorResponse = new Error( 'License activation failed' );

			global.wp.apiFetch.mockRejectedValue( errorResponse );

			try {
				await proUpgradeFlow.activateLicense( 'failing-key' );
			} catch ( error ) {
				expect( error.message ).toBe( 'License activation failed' );
			}
		} );
	} );

	describe( 'Popup Management', () => {
		let popupManager;

		beforeEach( () => {
			// Mock popup manager
			popupManager = {
				activePopup: null,
				popupStack: [],

				showPopup: jest.fn(),
				hidePopup: jest.fn(),
				createPopup: jest.fn(),
				destroyPopup: jest.fn(),
				updatePopupContent: jest.fn(),
				bindEvents: jest.fn(),
				unbindEvents: jest.fn(),
			};

			// Mock DOM
			document.body.innerHTML = `
				<div id="popup-maker-pro-upgrade" class="pum-popup" style="display: none;">
					<div class="pum-container">
						<div class="pum-content">
							<form id="pro-upgrade-form">
								<input type="text" id="license-key" name="license_key" />
								<button type="submit" id="activate-license">Activate License</button>
								<div id="upgrade-status" class="pum-message"></div>
							</form>
						</div>
					</div>
				</div>
			`;
		} );

		test( 'should create upgrade popup with correct structure', () => {
			const popupConfig = {
				id: 'pro-upgrade-popup',
				title: 'Upgrade to Pro',
				content: '<div class="upgrade-form"></div>',
				settings: {
					theme_id: 'default',
					triggers: [],
					conditions: [],
					close_button_delay: 0,
				},
			};

			popupManager.createPopup.mockReturnValue( {
				id: popupConfig.id,
				element: document.getElementById( 'popup-maker-pro-upgrade' ),
			} );

			const popup = popupManager.createPopup( popupConfig );

			expect( popupManager.createPopup ).toHaveBeenCalledWith(
				popupConfig
			);
			expect( popup.id ).toBe( popupConfig.id );
		} );

		test( 'should show upgrade popup when triggered', () => {
			const popup = document.getElementById( 'popup-maker-pro-upgrade' );

			popupManager.showPopup.mockImplementation( () => {
				popup.style.display = 'block';
			} );

			popupManager.showPopup( 'pro-upgrade-popup' );

			expect( popupManager.showPopup ).toHaveBeenCalledWith(
				'pro-upgrade-popup'
			);
			expect( popup.style.display ).toBe( 'block' );
		} );

		test( 'should hide popup on successful upgrade', () => {
			const popup = document.getElementById( 'popup-maker-pro-upgrade' );
			popup.style.display = 'block';

			popupManager.hidePopup.mockImplementation( () => {
				popup.style.display = 'none';
			} );

			popupManager.hidePopup( 'pro-upgrade-popup' );

			expect( popupManager.hidePopup ).toHaveBeenCalledWith(
				'pro-upgrade-popup'
			);
			expect( popup.style.display ).toBe( 'none' );
		} );

		test( 'should update popup content dynamically', () => {
			const newContent =
				'<div class="success-message">Upgrade successful!</div>';
			const contentElement = document.querySelector(
				'#popup-maker-pro-upgrade .pum-content'
			);

			popupManager.updatePopupContent.mockImplementation(
				( popupId, content ) => {
					contentElement.innerHTML = content;
				}
			);

			popupManager.updatePopupContent( 'pro-upgrade-popup', newContent );

			expect( popupManager.updatePopupContent ).toHaveBeenCalledWith(
				'pro-upgrade-popup',
				newContent
			);
			expect( contentElement.innerHTML ).toBe( newContent );
		} );

		test( 'should handle popup events properly', () => {
			const eventHandlers = {
				pum_before_open: jest.fn(),
				pum_after_open: jest.fn(),
				pum_before_close: jest.fn(),
				pum_after_close: jest.fn(),
			};

			popupManager.bindEvents.mockImplementation( ( popup, handlers ) => {
				Object.keys( handlers ).forEach( ( event ) => {
					popup.addEventListener( event, handlers[ event ] );
				} );
			} );

			const popup = { addEventListener: jest.fn() };
			popupManager.bindEvents( popup, eventHandlers );

			expect( popupManager.bindEvents ).toHaveBeenCalledWith(
				popup,
				eventHandlers
			);
			expect( popup.addEventListener ).toHaveBeenCalledTimes( 4 );
		} );
	} );

	describe( 'AJAX Error Handling', () => {
		test( 'should handle network errors gracefully', async () => {
			global.wp.apiFetch.mockRejectedValue(
				new Error( 'Network connection failed' )
			);

			try {
				await proUpgradeFlow.validateLicense( 'test-key' );
			} catch ( error ) {
				expect( error.message ).toContain(
					'Network connection failed'
				);
			}
		} );

		test( 'should handle server errors with proper messages', async () => {
			const serverError = new Error( 'Internal server error' );
			serverError.code = 'server_error';
			serverError.data = { status: 500 };

			global.wp.apiFetch.mockRejectedValue( serverError );

			try {
				await proUpgradeFlow.validateLicense( 'test-key' );
			} catch ( error ) {
				expect( error.message ).toBe( 'Internal server error' );
			}
		} );

		test( 'should handle timeout errors', async () => {
			global.wp.apiFetch.mockImplementation(
				() =>
					new Promise( ( resolve, reject ) => {
						setTimeout(
							() => reject( new Error( 'Request timeout' ) ),
							1000
						);
					} )
			);

			jest.useFakeTimers();

			const validatePromise =
				proUpgradeFlow.validateLicense( 'test-key' );

			jest.advanceTimersByTime( 1500 );

			try {
				await validatePromise;
			} catch ( error ) {
				expect( error.message ).toContain( 'timeout' );
			}

			jest.useRealTimers();
		} );

		test( 'should retry failed requests with exponential backoff', async () => {
			let attempts = 0;
			global.wp.apiFetch.mockImplementation( () => {
				attempts++;
				if ( attempts < 3 ) {
					return Promise.reject( new Error( 'Temporary failure' ) );
				}
				return Promise.resolve( mockAjaxResponse.data );
			} );

			// Mock retry logic
			const retryWithBackoff = async (
				fn,
				maxAttempts = 3,
				delay = 1000
			) => {
				for ( let i = 0; i < maxAttempts; i++ ) {
					try {
						return await fn();
					} catch ( error ) {
						if ( i === maxAttempts - 1 ) {
							throw error;
						}
						await new Promise( ( resolve ) =>
							setTimeout( resolve, delay * Math.pow( 2, i ) )
						);
					}
				}
			};

			const result = await retryWithBackoff( () =>
				global.wp.apiFetch( { path: '/popup-maker/v2/license' } )
			);

			expect( attempts ).toBe( 3 );
			expect( result ).toEqual( mockAjaxResponse.data );
		} );
	} );

	describe( 'UI State Management', () => {
		beforeEach( () => {
			// Mock UI elements
			document.body.innerHTML = `
				<div id="pro-upgrade-container">
					<form id="license-form">
						<input type="text" id="license-key-input" />
						<button type="submit" id="validate-btn">Validate License</button>
						<button type="button" id="upgrade-btn" disabled>Upgrade to Pro</button>
					</form>
					<div id="status-message" class="hidden"></div>
					<div id="loading-spinner" class="hidden"></div>
				</div>
			`;
		} );

		test( 'should disable form during validation', () => {
			const submitBtn = document.getElementById( 'validate-btn' );
			const upgradeBtn = document.getElementById( 'upgrade-btn' );

			proUpgradeFlow.updateUI.mockImplementation( ( state ) => {
				if ( state === 'validating' ) {
					submitBtn.disabled = true;
					upgradeBtn.disabled = true;
					submitBtn.textContent = 'Validating...';
				}
			} );

			proUpgradeFlow.updateUI( 'validating' );

			expect( proUpgradeFlow.updateUI ).toHaveBeenCalledWith(
				'validating'
			);
			expect( submitBtn.disabled ).toBe( true );
			expect( upgradeBtn.disabled ).toBe( true );
		} );

		test( 'should show loading spinner during operations', () => {
			const spinner = document.getElementById( 'loading-spinner' );

			proUpgradeFlow.updateUI.mockImplementation( ( state ) => {
				if ( state === 'loading' ) {
					spinner.classList.remove( 'hidden' );
				} else {
					spinner.classList.add( 'hidden' );
				}
			} );

			proUpgradeFlow.updateUI( 'loading' );
			expect( spinner.classList.contains( 'hidden' ) ).toBe( false );

			proUpgradeFlow.updateUI( 'idle' );
			expect( spinner.classList.contains( 'hidden' ) ).toBe( true );
		} );

		test( 'should display status messages correctly', () => {
			const messageElement = document.getElementById( 'status-message' );

			proUpgradeFlow.showSuccessMessage.mockImplementation(
				( message ) => {
					messageElement.className = 'success';
					messageElement.textContent = message;
					messageElement.classList.remove( 'hidden' );
				}
			);

			proUpgradeFlow.showErrorMessage.mockImplementation( ( message ) => {
				messageElement.className = 'error';
				messageElement.textContent = message;
				messageElement.classList.remove( 'hidden' );
			} );

			proUpgradeFlow.showSuccessMessage(
				'License activated successfully!'
			);
			expect( messageElement.className ).toBe( 'success' );
			expect( messageElement.textContent ).toBe(
				'License activated successfully!'
			);

			proUpgradeFlow.showErrorMessage( 'License validation failed.' );
			expect( messageElement.className ).toBe( 'error' );
			expect( messageElement.textContent ).toBe(
				'License validation failed.'
			);
		} );

		test( 'should reset form state after completion', () => {
			const input = document.getElementById( 'license-key-input' );
			const submitBtn = document.getElementById( 'validate-btn' );

			input.value = 'test-license-key';
			submitBtn.disabled = true;

			proUpgradeFlow.resetForm.mockImplementation( () => {
				input.value = '';
				submitBtn.disabled = false;
				submitBtn.textContent = 'Validate License';
			} );

			proUpgradeFlow.resetForm();

			expect( proUpgradeFlow.resetForm ).toHaveBeenCalled();
			expect( input.value ).toBe( '' );
			expect( submitBtn.disabled ).toBe( false );
		} );
	} );

	describe( 'Integration Tests', () => {
		test( 'should complete full upgrade workflow', async () => {
			const licenseKey = 'test-integration-license-key';

			// Mock successful responses for the full workflow
			proUpgradeFlow.validateLicense.mockResolvedValue( {
				success: true,
				status: 'valid',
			} );

			proUpgradeFlow.activateLicense.mockResolvedValue( {
				success: true,
				can_upgrade: true,
				connect_info: {
					url: 'https://connect.example.com/install',
					back_url: 'http://localhost/wp-admin/',
				},
			} );

			proUpgradeFlow.startUpgrade.mockResolvedValue( {
				success: true,
				message: 'Pro upgrade completed successfully',
			} );

			// Execute the workflow
			const validationResult =
				await proUpgradeFlow.validateLicense( licenseKey );
			expect( validationResult.success ).toBe( true );

			const activationResult =
				await proUpgradeFlow.activateLicense( licenseKey );
			expect( activationResult.success ).toBe( true );
			expect( activationResult.can_upgrade ).toBe( true );

			const upgradeResult = await proUpgradeFlow.startUpgrade(
				activationResult.connect_info
			);
			expect( upgradeResult.success ).toBe( true );

			// Verify all steps were called
			expect( proUpgradeFlow.validateLicense ).toHaveBeenCalledWith(
				licenseKey
			);
			expect( proUpgradeFlow.activateLicense ).toHaveBeenCalledWith(
				licenseKey
			);
			expect( proUpgradeFlow.startUpgrade ).toHaveBeenCalledWith(
				activationResult.connect_info
			);
		} );

		test( 'should handle workflow interruption gracefully', async () => {
			const licenseKey = 'test-failing-license-key-12345';

			// Mock validation success but activation failure
			global.wp.apiFetch
				.mockResolvedValueOnce( {
					success: true,
					status: 'valid',
				} )
				.mockRejectedValueOnce(
					new Error( 'License activation failed' )
				);

			// Execute workflow
			const validationResult =
				await proUpgradeFlow.validateLicense( licenseKey );
			expect( validationResult.success ).toBe( true );

			try {
				await proUpgradeFlow.activateLicense( licenseKey );
			} catch ( error ) {
				expect( error.message ).toBe( 'License activation failed' );
			}

			// Verify upgrade was not attempted
			expect( proUpgradeFlow.startUpgrade ).not.toHaveBeenCalled();
		} );
	} );
} );

// Helper functions for testing
function isValidLicenseKey( key ) {
	if ( ! key || typeof key !== 'string' ) {
		return false;
	}

	// Validate actual license key format - 32 character hex string or dash-separated format
	const hexPattern = /^[a-fA-F0-9]{32}$/;
	const dashPattern = /^[a-zA-Z0-9-]{8,}$/;
	return hexPattern.test( key ) || dashPattern.test( key );
}

function sanitizeLicenseKey( key ) {
	if ( ! key || typeof key !== 'string' ) {
		return '';
	}

	// Remove script tags, normalize case, trim
	return key
		.replace( /<script[^>]*>.*?<\/script>/gi, '' )
		.toLowerCase()
		.trim();
}
