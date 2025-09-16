/**
 * Test Utilities for Pro Upgrade E2E Tests
 *
 * Shared helper functions and utilities for end-to-end testing
 * of the Pro upgrade workflow.
 */

import { Page, expect } from '@playwright/test';

/**
 * Test configuration and constants
 */
export const TEST_CONFIG = {
	baseUrl: process.env.WP_BASE_URL || 'http://localhost:8889',
	adminUser: {
		username: process.env.WP_ADMIN_USERNAME || 'admin',
		password: process.env.WP_ADMIN_PASSWORD || 'password',
	},
	subscriberUser: {
		username: process.env.WP_SUBSCRIBER_USERNAME || 'subscriber',
		password: process.env.WP_SUBSCRIBER_PASSWORD || 'password',
	},
	timeout: {
		default: 30000,
		slow: 60000,
		navigation: 30000,
	},
};

/**
 * Mock API responses for testing
 */
export const MOCK_API_RESPONSES = {
	license: {
		valid: {
			license_key: 'test-valid-license-key',
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
				back_url: `${ TEST_CONFIG.baseUrl }/wp-admin/`,
			},
		},
		invalid: {
			code: 'invalid_license',
			message: 'The license key is invalid or expired.',
			data: { status: 400 },
		},
		expired: {
			code: 'license_expired',
			message: 'Your license key has expired.',
			data: { status: 400 },
		},
	},
	webhook: {
		verified: {
			verified: true,
			message: 'Connection verified successfully.',
		},
		failed: {
			verified: false,
			message: 'Connection verification failed.',
			code: 'verification_failed',
		},
	},
	errors: {
		network: {
			code: 'network_error',
			message: 'Network connection failed.',
			data: { status: 500 },
		},
		timeout: {
			code: 'request_timeout',
			message: 'Request timed out.',
			data: { status: 408 },
		},
		rateLimit: {
			code: 'rate_limit_exceeded',
			message: 'Too many requests. Please try again later.',
			data: { status: 429 },
		},
	},
};

/**
 * Authentication utilities
 */
export class AuthHelper {
	private page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Log in as WordPress admin user
	 */
	async loginAsAdmin(): Promise< void > {
		await this.login(
			TEST_CONFIG.adminUser.username,
			TEST_CONFIG.adminUser.password
		);
	}

	/**
	 * Log in as WordPress subscriber user
	 */
	async loginAsSubscriber(): Promise< void > {
		await this.login(
			TEST_CONFIG.subscriberUser.username,
			TEST_CONFIG.subscriberUser.password
		);
	}

	/**
	 * Generic login function
	 * @param {string} username - The username to log in with.
	 * @param {string} password - The password to log in with.
	 */
	async login( username: string, password: string ): Promise< void > {
		await this.page.goto( `${ TEST_CONFIG.baseUrl }/wp-login.php` );

		// Only fill form if not already logged in
		if ( await this.page.locator( '#loginform' ).isVisible() ) {
			await this.page.fill( '#user_login', username );
			await this.page.fill( '#user_pass', password );
			await this.page.click( '#wp-submit' );

			// Wait for redirect to admin dashboard
			await this.page.waitForURL( '**/wp-admin/**', {
				timeout: TEST_CONFIG.timeout.navigation,
			} );
		}
	}

	/**
	 * Log out current user
	 */
	async logout(): Promise< void > {
		await this.page.goto(
			`${ TEST_CONFIG.baseUrl }/wp-login.php?action=logout`
		);
		await this.page.click( '#wp-submit' );
		await this.page.waitForURL( '**/wp-login.php**' );
	}

	/**
	 * Check if user is logged in
	 */
	async isLoggedIn(): Promise< boolean > {
		await this.page.goto( `${ TEST_CONFIG.baseUrl }/wp-admin/` );
		return ! ( await this.page.locator( '#loginform' ).isVisible() );
	}
}

/**
 * API mocking utilities
 */
export class ApiMocker {
	private page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Set up comprehensive API mocking for Pro upgrade workflow
	 */
	async setupProUpgradeMocks(): Promise< void > {
		// Mock license API endpoints
		await this.mockLicenseEndpoints();

		// Mock webhook endpoints
		await this.mockWebhookEndpoints();

		// Mock external Connect service
		await this.mockConnectService();
	}

	/**
	 * Mock license-related API endpoints
	 */
	async mockLicenseEndpoints(): Promise< void > {
		await this.page.route(
			'**/wp-json/popup-maker/v2/license**',
			async ( route ) => {
				const url = route.request().url();
				const method = route.request().method();

				if ( url.includes( '/license/activate' ) ) {
					await this.handleLicenseActivation( route );
				} else if ( url.includes( '/license' ) && method === 'GET' ) {
					await this.handleLicenseStatus( route );
				} else {
					await route.continue();
				}
			}
		);
	}

	/**
	 * Mock webhook-related API endpoints
	 */
	async mockWebhookEndpoints(): Promise< void > {
		await this.page.route(
			'**/wp-json/popup-maker/v2/connect/**',
			async ( route ) => {
				const url = route.request().url();

				if ( url.includes( '/verify' ) ) {
					await route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify(
							MOCK_API_RESPONSES.webhook.verified
						),
					} );
				} else if ( url.includes( '/install' ) ) {
					await route.fulfill( {
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify( {
							success: true,
							message: 'Installation started successfully.',
						} ),
					} );
				} else {
					await route.continue();
				}
			}
		);
	}

	/**
	 * Mock external Connect service
	 */
	async mockConnectService(): Promise< void > {
		await this.page.route(
			'https://connect.example.com/**',
			async ( route ) => {
				await route.fulfill( {
					status: 200,
					contentType: 'text/html',
					body: this.getConnectServiceHTML(),
				} );
			}
		);
	}

	/**
	 * Handle license activation requests
	 * @param {any} route - The route object.
	 */
	private async handleLicenseActivation( route: any ): Promise< void > {
		const postData = route.request().postData();
		const requestData = postData ? JSON.parse( postData ) : {};
		const licenseKey = requestData.license_key || '';

		if ( licenseKey.includes( 'valid' ) ) {
			await route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( {
					success: true,
					message: 'License activated successfully.',
					...MOCK_API_RESPONSES.license.valid,
				} ),
			} );
		} else if ( licenseKey.includes( 'expired' ) ) {
			await route.fulfill( {
				status: 400,
				contentType: 'application/json',
				body: JSON.stringify( MOCK_API_RESPONSES.license.expired ),
			} );
		} else {
			await route.fulfill( {
				status: 400,
				contentType: 'application/json',
				body: JSON.stringify( MOCK_API_RESPONSES.license.invalid ),
			} );
		}
	}

	/**
	 * Handle license status requests
	 * @param {any} route - The route object.
	 */
	private async handleLicenseStatus( route: any ): Promise< void > {
		await route.fulfill( {
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify( MOCK_API_RESPONSES.license.valid ),
		} );
	}

	/**
	 * Get HTML for Connect service simulation
	 */
	private getConnectServiceHTML(): string {
		return `
			<!DOCTYPE html>
			<html>
				<head>
					<title>Popup Maker Pro Installation</title>
					<style>
						body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
						.progress { width: 100%; height: 20px; background: #f0f0f0; margin: 20px 0; }
						.progress-bar { height: 100%; background: #007cba; width: 0%; transition: width 0.5s; }
						.status { margin: 20px 0; font-size: 18px; }
					</style>
				</head>
				<body>
					<h1>Installing Popup Maker Pro...</h1>
					<div class="status" id="status">Preparing installation...</div>
					<div class="progress">
						<div class="progress-bar" id="progress-bar"></div>
					</div>
					<div id="progress-text">0%</div>

					<script>
						let progress = 0;
						const progressBar = document.getElementById('progress-bar');
						const progressText = document.getElementById('progress-text');
						const status = document.getElementById('status');

						const steps = [
							'Downloading plugin files...',
							'Verifying integrity...',
							'Installing plugin...',
							'Activating plugin...',
							'Configuring settings...',
							'Installation complete!'
						];

						let stepIndex = 0;

						const interval = setInterval(() => {
							progress += 16.67; // 6 steps, so ~16.67% per step

							if (stepIndex < steps.length) {
								status.textContent = steps[stepIndex];
								stepIndex++;
							}

							progressBar.style.width = progress + '%';
							progressText.textContent = Math.round(progress) + '%';

							if (progress >= 100) {
								clearInterval(interval);
								status.textContent = 'Redirecting back to WordPress...';

								setTimeout(() => {
									window.location.href = '${ TEST_CONFIG.baseUrl }/wp-admin/?pro-upgrade-complete=1';
								}, 2000);
							}
						}, 800);
					</script>
				</body>
			</html>
		`;
	}

	/**
	 * Simulate network errors
	 * @param {string} pattern - The pattern to match.
	 */
	async simulateNetworkError(
		pattern: string = '**/wp-json/**'
	): Promise< void > {
		await this.page.route( pattern, ( route ) => {
			route.abort( 'failed' );
		} );
	}

	/**
	 * Simulate slow responses
	 * @param {string} pattern - The pattern to match.
	 * @param {number} delay   - The delay in milliseconds.
	 */
	async simulateSlowResponse(
		pattern: string = '**/wp-json/**',
		delay: number = 2000
	): Promise< void > {
		await this.page.route( pattern, async ( route ) => {
			await new Promise( ( resolve ) => setTimeout( resolve, delay ) );
			await route.continue();
		} );
	}

	/**
	 * Simulate rate limiting
	 * @param {string} pattern     - The pattern to match.
	 * @param {number} maxRequests - The maximum number of requests.
	 */
	async simulateRateLimit(
		pattern: string = '**/wp-json/**',
		maxRequests: number = 3
	): Promise< void > {
		let requestCount = 0;

		await this.page.route( pattern, async ( route ) => {
			requestCount++;

			if ( requestCount > maxRequests ) {
				await route.fulfill( {
					status: 429,
					contentType: 'application/json',
					body: JSON.stringify( MOCK_API_RESPONSES.errors.rateLimit ),
				} );
			} else {
				await route.continue();
			}
		} );
	}
}

/**
 * Page navigation utilities
 */
export class NavigationHelper {
	private page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Navigate to Pro upgrade page
	 */
	async goToUpgradePage(): Promise< void > {
		await this.page.goto(
			`${ TEST_CONFIG.baseUrl }/wp-admin/admin.php?page=popup-maker-pro-upgrade`
		);
		await this.page.waitForLoadState( 'networkidle' );
	}

	/**
	 * Navigate to plugin settings page
	 */
	async goToSettingsPage(): Promise< void > {
		await this.page.goto(
			`${ TEST_CONFIG.baseUrl }/wp-admin/admin.php?page=popup-maker-settings`
		);
		await this.page.waitForLoadState( 'networkidle' );
	}

	/**
	 * Navigate to popup editor
	 * @param {string} popupId - The ID of the popup to navigate to.
	 */
	async goToPopupEditor( popupId?: string ): Promise< void > {
		const url = popupId
			? `${ TEST_CONFIG.baseUrl }/wp-admin/post.php?post=${ popupId }&action=edit`
			: `${ TEST_CONFIG.baseUrl }/wp-admin/post-new.php?post_type=popup`;

		await this.page.goto( url );
		await this.page.waitForLoadState( 'networkidle' );
	}

	/**
	 * Wait for page redirect with timeout
	 * @param {string} expectedUrl - The expected URL.
	 * @param {number} timeout     - The timeout in milliseconds.
	 */
	async waitForRedirect(
		expectedUrl: string,
		timeout: number = TEST_CONFIG.timeout.navigation
	): Promise< void > {
		await this.page.waitForURL( expectedUrl, { timeout } );
	}
}

/**
 * Form interaction utilities
 */
export class FormHelper {
	private page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Fill license key input
	 * @param {string} licenseKey - The license key to fill in the input field.
	 */
	async fillLicenseKey( licenseKey: string ): Promise< void > {
		const input = this.page.locator(
			'#license-key-input, input[name="license_key"]'
		);
		await input.fill( licenseKey );
	}

	/**
	 * Submit license validation form
	 */
	async submitLicenseValidation(): Promise< void > {
		await this.page.click(
			'#validate-license-btn, button[name="validate_license"]'
		);
	}

	/**
	 * Activate license for Pro upgrade
	 */
	async activateLicenseForPro(): Promise< void > {
		await this.page.click(
			'#activate-pro-btn, button[name="activate_pro"]'
		);
	}

	/**
	 * Start Pro upgrade process
	 */
	async startUpgrade(): Promise< void > {
		await this.page.click(
			'#start-upgrade-btn, button[name="start_upgrade"]'
		);
	}

	/**
	 * Complete full upgrade workflow
	 * @param {string} licenseKey - The license key to fill in the input field.
	 */
	async completeUpgradeWorkflow( licenseKey: string ): Promise< void > {
		await this.fillLicenseKey( licenseKey );
		await this.submitLicenseValidation();

		// Wait for validation success
		await expect(
			this.page.locator( '.license-status.success' )
		).toBeVisible();

		await this.activateLicenseForPro();

		// Wait for activation success
		await expect(
			this.page.locator( '.activation-status.success' )
		).toBeVisible();

		await this.startUpgrade();
	}
}

/**
 * Popup interaction utilities
 */
export class PopupHelper {
	private page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Wait for popup to appear
	 * @param {string} popupId - The ID of the popup to wait for.
	 * @param {number} timeout - The timeout in milliseconds.
	 */
	async waitForPopup(
		popupId: string,
		timeout: number = TEST_CONFIG.timeout.default
	): Promise< void > {
		await this.page.locator( `#${ popupId }` ).waitFor( {
			state: 'visible',
			timeout,
		} );
	}

	/**
	 * Close popup
	 * @param {string} popupId - The ID of the popup to close.
	 */
	async closePopup( popupId: string ): Promise< void > {
		await this.page.locator( `#${ popupId } .pum-close` ).click();
		await this.page
			.locator( `#${ popupId }` )
			.waitFor( { state: 'hidden' } );
	}

	/**
	 * Verify popup structure
	 * @param {string} popupId - The ID of the popup to verify.
	 */
	async verifyPopupStructure( popupId: string ): Promise< void > {
		const popup = this.page.locator( `#${ popupId }` );
		await expect( popup.locator( '.pum-container' ) ).toBeVisible();
		await expect( popup.locator( '.pum-content' ) ).toBeVisible();
	}

	/**
	 * Get popup content
	 * @param {string} popupId - The ID of the popup to get the content of.
	 */
	async getPopupContent( popupId: string ): Promise< string > {
		return (
			( await this.page
				.locator( `#${ popupId } .pum-content` )
				.textContent() ) || ''
		);
	}
}

/**
 * Assertion utilities
 */
export class AssertionHelper {
	private page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Assert successful license validation
	 */
	async assertLicenseValidationSuccess(): Promise< void > {
		await expect(
			this.page.locator( '.license-status.success' )
		).toBeVisible();
		await expect(
			this.page.locator( '.license-status.success' )
		).toContainText( /valid|active/i );
	}

	/**
	 * Assert license validation error
	 * @param {string} expectedMessage - The expected message.
	 */
	async assertLicenseValidationError(
		expectedMessage?: string
	): Promise< void > {
		await expect(
			this.page.locator( '.license-status.error' )
		).toBeVisible();

		if ( expectedMessage ) {
			await expect(
				this.page.locator( '.license-status.error' )
			).toContainText( expectedMessage );
		}
	}

	/**
	 * Assert upgrade completion
	 */
	async assertUpgradeCompletion(): Promise< void > {
		await expect(
			this.page.locator( '.notice.notice-success' )
		).toBeVisible();
		await expect(
			this.page.locator( '.notice.notice-success' )
		).toContainText( /upgrade.*complete/i );
	}

	/**
	 * Assert error message display
	 * @param {string} expectedMessage - The expected message.
	 */
	async assertErrorMessage( expectedMessage: string ): Promise< void > {
		await expect(
			this.page.locator( '.error-message, .notice-error' )
		).toBeVisible();
		await expect(
			this.page.locator( '.error-message, .notice-error' )
		).toContainText( expectedMessage );
	}

	/**
	 * Assert loading state
	 */
	async assertLoadingState(): Promise< void > {
		await expect(
			this.page.locator( '.loading-spinner, .is-loading' )
		).toBeVisible();
	}

	/**
	 * Assert no loading state
	 */
	async assertNotLoading(): Promise< void > {
		await expect(
			this.page.locator( '.loading-spinner, .is-loading' )
		).not.toBeVisible();
	}
}

/**
 * Performance monitoring utilities
 */
export class PerformanceHelper {
	private page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Measure page load time
	 * @param {string} url - The URL to measure the load time of.
	 */
	async measurePageLoadTime( url: string ): Promise< number > {
		const startTime = Date.now();
		await this.page.goto( url );
		await this.page.waitForLoadState( 'networkidle' );
		return Date.now() - startTime;
	}

	/**
	 * Measure API response time
	 * @param {string} apiPattern - The pattern to match.
	 */
	async measureApiResponseTime( apiPattern: string ): Promise< number > {
		const startTime = Date.now();

		return new Promise( ( resolve ) => {
			this.page.on( 'response', ( response ) => {
				if ( response.url().includes( apiPattern ) ) {
					resolve( Date.now() - startTime );
				}
			} );
		} );
	}

	/**
	 * Monitor network requests
	 */
	async monitorNetworkRequests(): Promise< string[] > {
		const requests: string[] = [];

		this.page.on( 'request', ( request ) => {
			requests.push( request.url() );
		} );

		return requests;
	}
}

/**
 * Accessibility testing utilities
 */
export class AccessibilityHelper {
	private page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Check for ARIA labels
	 */
	async checkAriaLabels(): Promise< void > {
		const interactiveElements = await this.page
			.locator( 'button, input, select, textarea' )
			.all();

		for ( const element of interactiveElements ) {
			const hasAriaLabel = await element.getAttribute( 'aria-label' );
			const hasAriaLabelledby =
				await element.getAttribute( 'aria-labelledby' );
			const hasLabel = await element.evaluate( ( el ) => {
				const id = el.getAttribute( 'id' );
				return id
					? document.querySelector( `label[for="${ id }"]` ) !== null
					: false;
			} );

			expect(
				hasAriaLabel || hasAriaLabelledby || hasLabel
			).toBeTruthy();
		}
	}

	/**
	 * Test keyboard navigation
	 */
	async testKeyboardNavigation(): Promise< void > {
		// Start from first focusable element
		await this.page.keyboard.press( 'Tab' );

		// Verify focus moves through interactive elements
		const focusableElements = await this.page
			.locator(
				'button:visible, input:visible, select:visible, textarea:visible, a:visible'
			)
			.all();

		for ( let i = 0; i < Math.min( focusableElements.length, 10 ); i++ ) {
			const focusedElement = this.page.locator( ':focus' );
			await expect( focusedElement ).toBeVisible();
			await this.page.keyboard.press( 'Tab' );
		}
	}

	/**
	 * Check for screen reader announcements
	 */
	async checkScreenReaderAnnouncements(): Promise< void > {
		const liveRegions = await this.page.locator( '[aria-live]' ).all();
		expect( liveRegions.length ).toBeGreaterThan( 0 );

		for ( const region of liveRegions ) {
			const ariaLive = await region.getAttribute( 'aria-live' );
			expect( [ 'polite', 'assertive', 'off' ] ).toContain( ariaLive );
		}
	}
}

// Export all helpers as a convenience
export function createTestHelpers( page: Page ) {
	return {
		auth: new AuthHelper( page ),
		api: new ApiMocker( page ),
		navigation: new NavigationHelper( page ),
		form: new FormHelper( page ),
		popup: new PopupHelper( page ),
		assert: new AssertionHelper( page ),
		performance: new PerformanceHelper( page ),
		accessibility: new AccessibilityHelper( page ),
	};
}
