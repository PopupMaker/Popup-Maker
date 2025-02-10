/**
 * External dependencies
 */
import { request } from '@playwright/test';
import type { FullConfig } from '@playwright/test';

/**
 * WordPress dependencies
 */
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

async function deleteAllCallToActions( requestUtils: RequestUtils ) {
	// List all posts of specified type
	const posts = await requestUtils.rest( {
		path: `/popup-maker/v2/ctas`,
		params: {
			per_page: 100,
			status: 'publish,future,draft,pending,private,trash',
		},
	} );

	// Delete all posts one by one
	await Promise.all(
		posts.map( ( post ) =>
			requestUtils.rest( {
				method: 'DELETE',
				path: `/popup-maker/v2/ctas/${ post.id }`,
				params: {
					force: true,
				},
			} )
		)
	);
}

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

	// Delete all call to actions pum_cta post type.
	await deleteAllCallToActions( requestUtils );

	// Reset the test environment before running the tests.
	await Promise.all( [
		requestUtils.activateTheme( 'twentytwentyone' ),
		// Disable this test plugin as it's conflicting with some of the tests.
		// We already have reduced motion enabled and Playwright will wait for most of the animations anyway.
		requestUtils.deleteAllPosts(),

		requestUtils.deleteAllBlocks(),
		requestUtils.resetPreferences(),

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
