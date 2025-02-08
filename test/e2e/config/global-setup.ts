/**
 * External dependencies
 */
import { request } from '@playwright/test';
import type { FullConfig } from '@playwright/test';

/**
 * WordPress dependencies
 */
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

async function globalSetup( config: FullConfig ) {
	const { storageState, baseURL } = config.projects[ 0 ].use;
	const storageStatePath =
		typeof storageState === 'string' ? storageState : undefined;

	const requestContext = await request.newContext( {
		baseURL,
	} );

	const requestUtils = new RequestUtils( requestContext, {
		storageStatePath,
	} );

	// Authenticate and save the storageState to disk.
	await requestUtils.setupRest();

	await requestUtils.deleteAllUsers();

	// Reset the test environment before running the tests.
	await Promise.all( [
		requestUtils.activateTheme( 'twentytwentyone' ),
		// Disable this test plugin as it's conflicting with some of the tests.
		// We already have reduced motion enabled and Playwright will wait for most of the animations anyway.
		requestUtils.deleteAllPosts(),
		requestUtils.deleteAllBlocks(),
		requestUtils.resetPreferences(),

		// requestUtils.createUser( {
		// 	username: 'admin',
		// 	password: 'password',
		// 	email: 'admin@example.com',
		// 	roles: [ 'administrator' ],
		// } ),

		requestUtils.createUser( {
			username: 'subscriber',
			password: 'password',
			email: 'subscriber@example.com',
			roles: [ 'subscriber' ],
		} ),
	] );

	await requestContext.dispose();
}

export default globalSetup;
