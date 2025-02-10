import { test, expect, Page } from '@playwright/test';

/**
 * URL to your WordPress site and the path to your plugin's CTA editor page.
 * Update these to match your local environment.
 */
const BASE_URL = 'http://localhost:8889';
const CTA_EDITOR_PATH =
	'/wp-admin/edit.php?post_type=popup&page=popup-maker-call-to-actions';

// Helper function to log in to WordPress.
async function loginAsUser(
	page: Page,
	username: string,
	password: string
): Promise< void > {
	// Navigate to the WP login page.
	await page.goto( `${ BASE_URL }/wp-login.php`, {
		waitUntil: 'networkidle',
	} );

	// Fill in login form.
	await page.fill( '#user_login', username );
	await page.fill( '#user_pass', password );

	// Click the 'Log In' button.
	await page.click( '#wp-submit' );
	// Wait for navigation to complete.
	await page.waitForURL( ( url ) => url.href.includes( '/wp-admin' ) );
}

test.describe( 'Call to Actions E2E Tests', () => {
	test.describe( 'User with Permissions', () => {
		// Logs in with a user who *can* edit CTAs before each test.
		test.beforeEach( async ( { page } ) => {
			await loginAsUser( page, 'admin', 'password' );

			// Go to the CTA Editor page.
			await page.goto( `${ BASE_URL }${ CTA_EDITOR_PATH }`, {
				waitUntil: 'networkidle',
			} );
		} );

		test( 'Should load the Call to Actions page', async ( { page } ) => {
			// Expect the root container for the CTA editor to be visible.
			await expect(
				page.locator( '#popup-maker-call-to-actions-root-container' )
			).toBeVisible();
		} );

		test( 'Should display Call to Actions header text', async ( {
			page,
		} ) => {
			// The Call to Actions header might be in .call-to-action-list
			// waiting to ensure it’s rendered.
			await page.waitForSelector( '.call-to-action-list' );

			// Check for header text. Adjust the selector to match your actual header.
			const headerText = await page.textContent(
				'.call-to-action-list h1, .call-to-action-list h2, .call-to-action-list h3'
			);
			expect( headerText ).toMatch( /Call to Actions/i );
		} );

		test( 'Should display a list of existing Call to Actions (if any)', async ( {
			page,
		} ) => {
			// Wait for the CTA list container (adjust selector to your UI).
			const listContainer = page.locator(
				'.call-to-action-list .list-table-container'
			);
			await expect( listContainer ).toBeVisible();

			// Count CTA items in the list (example: items have class .list-item).
			const itemCount = await page.locator( 'tbody tr' ).count();

			// If none are created yet, itemCount might be 0; the presence of
			// the list container is enough to confirm the list is rendered.
			expect( itemCount ).toBeGreaterThanOrEqual( 0 );
		} );

		test( 'Should open the "Add New CTA" modal/editor', async ( {
			page,
		} ) => {
			// Suppose there's a button with class .add-call-to-action to open new CTA form.
			const addNewButton = page.locator(
				'.call-to-action-list .add-call-to-action'
			);
			await expect( addNewButton ).toBeVisible();

			// Click it to open editor or modal.
			await addNewButton.click();

			// The modal/editor might have a specific class or heading.
			// Adjust the selector to your plugin.
			const modalHeader = page.locator(
				'.call-to-action-editor-modal h1'
			);
			await expect( modalHeader ).toBeVisible();

			// Check text content.
			const headerText = await modalHeader.textContent();
			expect( headerText ).toContain( 'Edit Call to Action' );
		} );
	} );

	test.describe( 'User without Permissions', () => {
		// Logs in with a user who does *not* have the edit_ctas capability.
		test.beforeEach( async ( { page } ) => {
			await loginAsUser( page, 'subscriber', 'password' );
		} );

		test( 'Should display "Permission Denied" message', async ( {
			page,
		} ) => {
			// Attempt to go to CTA editor page.
			await page.goto( `${ BASE_URL }${ CTA_EDITOR_PATH }`, {
				waitUntil: 'networkidle',
			} );

			// Expect a permission denied screen.
			const deniedContainer = page.locator( '#error-page' );
			await expect( deniedContainer ).toBeVisible();

			// Verify the text inside it.
			const deniedText = await deniedContainer.textContent();
			expect( deniedText ).toMatch(
				/Sorry, you are not allowed to access this page./i
			);
		} );
	} );
} );
