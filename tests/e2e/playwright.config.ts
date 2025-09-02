/**
 * External dependencies
 */
import os from 'os';
import { fileURLToPath } from 'url';
import { defineConfig, devices } from '@playwright/test';

/**
 * WordPress dependencies
 */
import baseConfig from '@wordpress/scripts/config/playwright.config.js';

const config = defineConfig( {
	...baseConfig,
	reporter: process.env.CI ? [ [ 'github' ] ] : 'list',
	workers: 1,
	globalSetup: fileURLToPath(
		new URL( './config/global-setup.ts', 'file:' + __filename ).href
	),
	use: {
		...baseConfig.use,
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8880',
	},
	webServer: undefined, // Don't auto-start wp-env, handle manually
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
			grepInvert: /-chromium/,
		},
		{
			name: 'webkit',
			use: {
				...devices[ 'Desktop Safari' ],
				/**
				 * Headless webkit won't receive dataTransfer with custom types in the
				 * drop event on Linux. The solution is to use `xvfb-run` to run the tests.
				 * ```sh
				 * xvfb-run npm run test:e2e
				 * ```
				 * See `.github/workflows/end2end-test-playwright.yml` for advanced usages.
				 */
				headless: os.type() !== 'Linux',
			},
			grep: /@webkit/,
			grepInvert: /-webkit/,
		},
		{
			name: 'firefox',
			use: { ...devices[ 'Desktop Firefox' ] },
			grep: /@firefox/,
			grepInvert: /-firefox/,
		},
	],
} );

export default config;
