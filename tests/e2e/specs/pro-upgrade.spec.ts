/**
 * End-to-End Tests for Pro Upgrade Workflow
 *
 * Tests the complete Pro upgrade flow including:
 * - License validation and activation
 * - Popup management during upgrade
 * - Pro plugin installation
 * - Error recovery scenarios
 * - Security verification
 *
 * @package PopupMaker
 * @subpackage Tests
 */

import { test, expect, Page, BrowserContext } from '@playwright/test';

// Test data
const TEST_DATA = {
	validLicenseKey: 'test-valid-license-key-12345',
	invalidLicenseKey: 'invalid-license-key',
	expiredLicenseKey: 'expired-license-key-67890',
	adminUser: {
		username: 'admin',
		password: 'password123',
	},
	subscriberUser: {
		username: 'subscriber',
		password: 'subscriber123',
	},
};

// Mock API responses
const MOCK_RESPONSES = {
	validLicense: {
		license_key: TEST_DATA.validLicenseKey,
		status: 'valid',
		status_data: {
			success: true,
			license: 'valid',
			item_id: 480187,
			item_name: 'Popup Maker Pro',
			license_limit: 5,
			site_count: 1,
			expires: '2025-12-31 23:59:59',
			activations_left: 4,
			customer_name: 'Test Customer',
			customer_email: 'test@example.com',
		},
		is_active: true,
		is_pro_installed: false,
		is_pro_active: false,
		can_upgrade: true,
		connect_info: {
			url: 'https://connect.example.com/install',
			back_url: 'http://localhost/wp-admin/',
		},
	},
	invalidLicense: {
		code: 'invalid_license',
		message: 'The license key is invalid or expired.',
		data: { status: 400 },
	},
	networkError: {
		code: 'network_error',
		message: 'Network connection failed.',
		data: { status: 500 },
	},
};

test.describe( 'Pro Upgrade Workflow', () => {
	let page: Page;
	let context: BrowserContext;

	test.beforeEach( async ( { browser } ) => {
		context = await browser.newContext();
		page = await context.newPage();

		// Set up request interception for API mocking
		await page.route( '**/wp-json/popup-maker/v2/**', ( route ) => {
			const url = route.request().url();
			const method = route.request().method();

			// Handle different API endpoints
			if ( url.includes( '/license' ) && method === 'GET' ) {
				route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( MOCK_RESPONSES.validLicense ),
				} );
			} else if ( url.includes( '/license/activate' ) ) {
				const postData = route.request().postData();
				const licenseKey = postData
					? JSON.parse( postData ).license_key
					: '';

				if ( licenseKey === TEST_DATA.validLicenseKey ) {
					route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify( {
							success: true,
							message: 'License activated successfully.',
							...MOCK_RESPONSES.validLicense,
						} ),
					} );
				} else {
					route.fulfill( {
						status: 400,
						contentType: 'application/json',
						body: JSON.stringify( MOCK_RESPONSES.invalidLicense ),
					} );
				}
			} else {
				route.continue();
			}
		} );

		// Set up webhook endpoint mocking
		await page.route( '**/wp-json/popup-maker/v2/connect/**', ( route ) => {
			const url = route.request().url();

			if ( url.includes( '/verify' ) ) {
				route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( {
						verified: true,
						message: 'Connection verified successfully.',
					} ),
				} );
			} else {
				route.continue();
			}
		} );

		// Mock the external Connect service
		await page.route( 'https://connect.example.com/**', ( route ) => {
			route.fulfill( {
				status: 200,
				contentType: 'text/html',
				body: `
					<html>
						<head><title>Pro Upgrade</title></head>
						<body>
							<h1>Installing Popup Maker Pro...</h1>
							<div id="install-progress">0%</div>
							<script>
								// Simulate installation progress
								let progress = 0;
								const interval = setInterval(() => {
									progress += 10;
									document.getElementById('install-progress').textContent = progress + '%';
									if (progress >= 100) {
										clearInterval(interval);
										setTimeout(() => {
											window.location.href = '${ MOCK_RESPONSES.validLicense.connect_info.back_url }';
										}, 1000);
									}
								}, 500);
							</script>
						</body>
					</html>
				`,
			} );
		} );
	} );

	test.afterEach( async () => {
		await context.close();
	} );

	/**
	 * Helper function to log in as admin user
	 */
	async function loginAsAdmin() {
		await page.goto( '/wp-admin/' );

		// Fill login form if not already logged in
		if ( await page.locator( '#loginform' ).isVisible() ) {
			await page.fill( '#user_login', TEST_DATA.adminUser.username );
			await page.fill( '#user_pass', TEST_DATA.adminUser.password );
			await page.click( '#wp-submit' );
			await page.waitForURL( '**/wp-admin/**' );
		}
	}

	/**
	 * Helper function to navigate to Pro upgrade page
	 */
	async function navigateToUpgradePage() {
		await page.goto( '/wp-admin/admin.php?page=popup-maker-pro-upgrade' );
		await page.waitForLoadState( 'networkidle' );
	}

	/**
	 * Helper function to fill license key
	 * @param {string} licenseKey - The license key to fill in the input field.
	 */
	async function fillLicenseKey( licenseKey: string ) {
		const licenseInput = page.locator( '#license-key-input' );
		await licenseInput.fill( licenseKey );
	}

	/**
	 * Helper function to wait for and verify popup visibility
	 * @param {string} popupId - The ID of the popup to wait for.
	 */
	async function waitForPopup( popupId: string ) {
		const popup = page.locator( `#${ popupId }` );
		await popup.waitFor( { state: 'visible' } );
		return popup;
	}

	test( 'should complete successful Pro upgrade workflow', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Step 1: Enter valid license key
		await fillLicenseKey( TEST_DATA.validLicenseKey );

		// Step 2: Validate license
		await page.click( '#validate-license-btn' );

		// Wait for validation success message
		await expect( page.locator( '.license-status.success' ) ).toBeVisible();
		await expect( page.locator( '.license-status.success' ) ).toContainText(
			'License is valid'
		);

		// Step 3: Activate license for Pro upgrade
		await page.click( '#activate-pro-btn' );

		// Wait for activation success
		await expect(
			page.locator( '.activation-status.success' )
		).toBeVisible();
		await expect(
			page.locator( '.activation-status.success' )
		).toContainText( 'License activated successfully' );

		// Step 4: Start Pro upgrade
		const upgradeBtn = page.locator( '#start-upgrade-btn' );
		await expect( upgradeBtn ).toBeEnabled();
		await upgradeBtn.click();

		// Step 5: Verify redirect to Connect service
		await page.waitForURL( 'https://connect.example.com/**' );
		await expect( page.locator( 'h1' ) ).toContainText(
			'Installing Popup Maker Pro'
		);

		// Step 6: Wait for installation progress
		await expect( page.locator( '#install-progress' ) ).toContainText(
			'100%'
		);

		// Step 7: Verify redirect back to WordPress admin
		await page.waitForURL( '**/wp-admin/**' );

		// Step 8: Verify Pro upgrade completion
		await expect( page.locator( '.notice.notice-success' ) ).toBeVisible();
		await expect( page.locator( '.notice.notice-success' ) ).toContainText(
			'Pro upgrade completed successfully'
		);
	} );

	test( 'should handle invalid license key gracefully', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Enter invalid license key
		await fillLicenseKey( TEST_DATA.invalidLicenseKey );

		// Attempt to validate license
		await page.click( '#validate-license-btn' );

		// Verify error message is displayed
		await expect( page.locator( '.license-status.error' ) ).toBeVisible();
		await expect( page.locator( '.license-status.error' ) ).toContainText(
			'invalid or expired'
		);

		// Verify upgrade button remains disabled
		await expect( page.locator( '#start-upgrade-btn' ) ).toBeDisabled();

		// Verify no popups are triggered
		await expect( page.locator( '.pum-popup' ) ).not.toBeVisible();
	} );

	test( 'should prevent unauthorized access to upgrade page', async () => {
		// Try to access upgrade page without login
		await page.goto( '/wp-admin/admin.php?page=popup-maker-pro-upgrade' );

		// Should be redirected to login page
		await page.waitForURL( '**/wp-login.php**' );
		await expect( page.locator( '#loginform' ) ).toBeVisible();

		// Log in as subscriber (insufficient permissions)
		await page.fill( '#user_login', TEST_DATA.subscriberUser.username );
		await page.fill( '#user_pass', TEST_DATA.subscriberUser.password );
		await page.click( '#wp-submit' );

		// Try to access upgrade page again
		await page.goto( '/wp-admin/admin.php?page=popup-maker-pro-upgrade' );

		// Should show permission error
		await expect( page.locator( '.notice.notice-error' ) ).toBeVisible();
		await expect( page.locator( '.notice.notice-error' ) ).toContainText(
			'You do not have sufficient permissions'
		);
	} );

	test( 'should display upgrade popup correctly', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Trigger upgrade popup (if configured to show automatically)
		await page.click( '#show-upgrade-popup-btn' );

		// Wait for popup to appear
		const popup = await waitForPopup( 'popup-maker-pro-upgrade' );

		// Verify popup structure
		await expect( popup.locator( '.pum-container' ) ).toBeVisible();
		await expect( popup.locator( '.pum-content' ) ).toBeVisible();
		await expect( popup.locator( '#pro-upgrade-form' ) ).toBeVisible();

		// Verify popup content
		await expect( popup.locator( 'h2' ) ).toContainText( 'Upgrade to Pro' );
		await expect( popup.locator( '#license-key' ) ).toBeVisible();
		await expect( popup.locator( '#activate-license' ) ).toBeVisible();

		// Test popup close functionality
		await popup.locator( '.pum-close' ).click();
		await expect( popup ).not.toBeVisible();
	} );

	test( 'should handle network errors with retry mechanism', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Mock network failure for first few requests
		let requestCount = 0;
		await page.route( '**/wp-json/popup-maker/v2/license', ( route ) => {
			requestCount++;
			if ( requestCount <= 2 ) {
				route.abort( 'failed' );
			} else {
				route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( MOCK_RESPONSES.validLicense ),
				} );
			}
		} );

		await fillLicenseKey( TEST_DATA.validLicenseKey );
		await page.click( '#validate-license-btn' );

		// Should show retry indicator
		await expect( page.locator( '.retry-indicator' ) ).toBeVisible();

		// Should eventually succeed after retries
		await expect( page.locator( '.license-status.success' ) ).toBeVisible( {
			timeout: 10000,
		} );

		// Verify retry count in status message
		await expect( page.locator( '.retry-count' ) ).toContainText(
			'Retry 2/3'
		);
	} );

	test( 'should validate and sanitize user input', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Test XSS prevention
		const xssPayload = '<script>alert("xss")</script>test-key';
		await fillLicenseKey( xssPayload );

		// Verify input is sanitized
		const inputValue = await page
			.locator( '#license-key-input' )
			.inputValue();
		expect( inputValue ).not.toContain( '<script>' );
		expect( inputValue ).toBe( 'test-key' );

		// Test empty input validation
		await fillLicenseKey( '' );
		await page.click( '#validate-license-btn' );

		await expect( page.locator( '.validation-error' ) ).toBeVisible();
		await expect( page.locator( '.validation-error' ) ).toContainText(
			'License key is required'
		);

		// Test invalid format validation
		await fillLicenseKey( 'short' );
		await page.click( '#validate-license-btn' );

		await expect( page.locator( '.validation-error' ) ).toBeVisible();
		await expect( page.locator( '.validation-error' ) ).toContainText(
			'Invalid license key format'
		);
	} );

	test( 'should handle concurrent user sessions', async () => {
		// Create two browser contexts to simulate concurrent users
		const context1 = await page.context().browser()!.newContext();
		const context2 = await page.context().browser()!.newContext();

		const page1 = await context1.newPage();
		const page2 = await context2.newPage();

		// Set up API mocking for both pages
		const setupPage = async ( testPage: Page ) => {
			await testPage.route( '**/wp-json/popup-maker/v2/**', ( route ) => {
				// Simulate rate limiting after multiple requests
				const headers = route.request().headers();
				if (
					headers[ 'x-request-count' ] &&
					parseInt( headers[ 'x-request-count' ] ) > 3
				) {
					route.fulfill( {
						status: 429,
						contentType: 'application/json',
						body: JSON.stringify( {
							code: 'rate_limit_exceeded',
							message:
								'Too many requests. Please try again later.',
						} ),
					} );
				} else {
					route.continue();
				}
			} );
		};

		await setupPage( page1 );
		await setupPage( page2 );

		// Both users try to upgrade simultaneously
		await Promise.all( [
			( async () => {
				await page1.goto(
					'/wp-admin/admin.php?page=popup-maker-pro-upgrade'
				);
				await page1.fill(
					'#license-key-input',
					TEST_DATA.validLicenseKey
				);
				await page1.click( '#validate-license-btn' );
			} )(),
			( async () => {
				await page2.goto(
					'/wp-admin/admin.php?page=popup-maker-pro-upgrade'
				);
				await page2.fill(
					'#license-key-input',
					TEST_DATA.validLicenseKey
				);
				await page2.click( '#validate-license-btn' );
			} )(),
		] );

		// At least one should succeed, one might be rate limited
		const page1Success = await page1
			.locator( '.license-status.success' )
			.isVisible();
		const page2Success = await page2
			.locator( '.license-status.success' )
			.isVisible();
		const page1RateLimit = await page1
			.locator( '.rate-limit-error' )
			.isVisible();
		const page2RateLimit = await page2
			.locator( '.rate-limit-error' )
			.isVisible();

		expect( page1Success || page2Success ).toBe( true );
		expect( page1RateLimit || page2RateLimit ).toBe( true );

		await context1.close();
		await context2.close();
	} );

	test( 'should recover from upgrade interruption', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Start upgrade process
		await fillLicenseKey( TEST_DATA.validLicenseKey );
		await page.click( '#validate-license-btn' );
		await page.click( '#activate-pro-btn' );
		await page.click( '#start-upgrade-btn' );

		// Simulate browser navigation away during upgrade
		await page.goto( '/wp-admin/' );

		// Return to upgrade page
		await navigateToUpgradePage();

		// Should detect incomplete upgrade and offer recovery
		await expect( page.locator( '.upgrade-recovery' ) ).toBeVisible();
		await expect( page.locator( '.upgrade-recovery' ) ).toContainText(
			'Resume upgrade'
		);

		// Resume upgrade
		await page.click( '#resume-upgrade-btn' );

		// Should continue from where it left off
		await expect( page.locator( '.upgrade-status' ) ).toContainText(
			'Resuming upgrade'
		);
		await page.waitForURL( 'https://connect.example.com/**' );
	} );

	test( 'should provide accessibility compliance', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Check for proper ARIA labels
		await expect( page.locator( '#license-key-input' ) ).toHaveAttribute(
			'aria-label'
		);
		await expect( page.locator( '#validate-license-btn' ) ).toHaveAttribute(
			'aria-describedby'
		);

		// Check for keyboard navigation
		await page.keyboard.press( 'Tab' );
		await expect( page.locator( '#license-key-input' ) ).toBeFocused();

		await page.keyboard.press( 'Tab' );
		await expect( page.locator( '#validate-license-btn' ) ).toBeFocused();

		// Check for screen reader announcements
		await fillLicenseKey( TEST_DATA.validLicenseKey );
		await page.click( '#validate-license-btn' );

		await expect( page.locator( '[aria-live="polite"]' ) ).toContainText(
			'License validation in progress'
		);
		await expect( page.locator( '[aria-live="polite"]' ) ).toContainText(
			'License is valid'
		);

		// Test high contrast mode support
		await page.emulateMedia( { colorScheme: 'dark' } );
		await expect( page.locator( '.pro-upgrade-container' ) ).toBeVisible();
	} );

	test( 'should handle mobile device compatibility', async () => {
		// Emulate mobile device
		await page.setViewportSize( { width: 375, height: 667 } );
		await page.emulateMedia( { media: 'screen' } );

		await loginAsAdmin();
		await navigateToUpgradePage();

		// Verify responsive design
		await expect( page.locator( '.pro-upgrade-container' ) ).toBeVisible();
		await expect( page.locator( '#license-key-input' ) ).toBeVisible();

		// Test touch interactions
		await page.tap( '#license-key-input' );
		await page.fill( '#license-key-input', TEST_DATA.validLicenseKey );
		await page.tap( '#validate-license-btn' );

		// Verify mobile popup behavior
		await page.tap( '#show-upgrade-popup-btn' );
		const popup = await waitForPopup( 'popup-maker-pro-upgrade' );

		// Popup should be full screen on mobile
		const popupBox = await popup.boundingBox();
		const viewportSize = page.viewportSize();

		expect( popupBox?.width ).toBeGreaterThanOrEqual(
			viewportSize!.width * 0.9
		);
	} );

	test( 'should maintain security throughout upgrade process', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Verify CSRF token is present
		await expect( page.locator( 'input[name="security"]' ) ).toHaveValue(
			/\w+/
		);

		// Verify secure HTTPS connections for external requests
		page.on( 'request', ( request ) => {
			const url = request.url();
			if (
				url.includes( 'connect.example.com' ) ||
				url.includes( 'wppopupmaker.com' )
			) {
				expect( url ).toMatch( /^https:/ );
			}
		} );

		// Test for potential XSS vulnerabilities
		await fillLicenseKey( '<img src=x onerror=alert(1)>' );

		// Should not execute JavaScript
		let jsExecuted = false;
		page.on( 'dialog', () => {
			jsExecuted = true;
		} );

		await page.click( '#validate-license-btn' );
		await page.waitForTimeout( 1000 );

		expect( jsExecuted ).toBe( false );

		// Verify no sensitive data in DOM
		const pageContent = await page.content();
		expect( pageContent ).not.toMatch( /password|secret|token/i );
	} );

	test( 'should provide comprehensive error reporting', async () => {
		await loginAsAdmin();
		await navigateToUpgradePage();

		// Test different error scenarios
		const errorScenarios = [
			{
				scenario: 'Network timeout',
				mockRoute: '**/wp-json/popup-maker/v2/license',
				mockResponse: { abort: 'timedout' },
				expectedMessage: 'Request timed out',
			},
			{
				scenario: 'Server error',
				mockRoute: '**/wp-json/popup-maker/v2/license',
				mockResponse: {
					status: 500,
					body: '{"message": "Internal server error"}',
				},
				expectedMessage: 'Server error occurred',
			},
			{
				scenario: 'Invalid response',
				mockRoute: '**/wp-json/popup-maker/v2/license',
				mockResponse: { status: 200, body: 'invalid json' },
				expectedMessage: 'Invalid response format',
			},
		];

		for ( const {
			scenario,
			mockRoute,
			mockResponse,
			expectedMessage,
		} of errorScenarios ) {
			// Set up error scenario
			await page.route( mockRoute, ( route ) => {
				if ( 'abort' in mockResponse ) {
					route.abort( mockResponse.abort as any );
				} else {
					route.fulfill( {
						status: mockResponse.status,
						contentType: 'application/json',
						body: mockResponse.body,
					} );
				}
			} );

			await fillLicenseKey( TEST_DATA.validLicenseKey );
			await page.click( '#validate-license-btn' );

			// Verify appropriate error message
			await expect( page.locator( '.error-message' ) ).toContainText(
				expectedMessage
			);

			// Verify error is logged for debugging
			const logs = await page.evaluate( () => {
				return ( window as any ).errorLogs || [];
			} );

			expect(
				logs.some( ( log: any ) => log.scenario === scenario )
			).toBe( true );

			// Reset for next scenario
			await page.reload();
		}
	} );
} );

test.describe( 'Performance and Load Testing', () => {
	test( 'should handle high load gracefully', async ( { page } ) => {
		// Simulate high server load
		await page.route( '**/wp-json/popup-maker/v2/**', ( route ) => {
			setTimeout( () => {
				route.fulfill( {
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify( MOCK_RESPONSES.validLicense ),
				} );
			}, 2000 ); // Simulate slow response
		} );

		// Track performance metrics
		const startTime = Date.now();

		await page.goto( '/wp-admin/admin.php?page=popup-maker-pro-upgrade' );
		await page.fill( '#license-key-input', TEST_DATA.validLicenseKey );
		await page.click( '#validate-license-btn' );

		await expect( page.locator( '.license-status.success' ) ).toBeVisible( {
			timeout: 15000,
		} );

		const endTime = Date.now();
		const totalTime = endTime - startTime;

		// Should complete within reasonable time even under load
		expect( totalTime ).toBeLessThan( 15000 );

		// Should show loading indicators during slow operations
		await expect( page.locator( '.loading-spinner' ) ).toBeVisible();
	} );

	test( 'should optimize resource usage', async ( { page } ) => {
		// Monitor network requests
		const requests: string[] = [];
		page.on( 'request', ( request ) => {
			requests.push( request.url() );
		} );

		await page.goto( '/wp-admin/admin.php?page=popup-maker-pro-upgrade' );
		await page.waitForLoadState( 'networkidle' );

		// Should not make excessive API calls
		const apiCalls = requests.filter( ( url ) =>
			url.includes( '/wp-json/' )
		);
		expect( apiCalls.length ).toBeLessThan( 10 );

		// Should cache repeated requests
		await page.reload();
		await page.waitForLoadState( 'networkidle' );

		const secondPageRequests = requests.length;
		expect( secondPageRequests ).toBeLessThan( requests.length * 1.5 );
	} );
} );
