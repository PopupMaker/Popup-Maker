const wordpressConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );
const storybook = require( 'eslint-plugin-storybook' );

module.exports = [
	{
		ignores: [
			'build/**',
			'build-types/**',
			'dist/**',
			'node_modules/**',
			'vendor/**',
			'.vscode/**',
			'release/**',
			'assets/js/*.js',
			'assets/js/*.js.map',
			'assets/js/src/**/*.js',
		],
	},
	...wordpressConfig,
	...storybook.configs[ 'flat/recommended' ],
	{
		languageOptions: {
			globals: {
				wp: 'readonly',
				wpApiSettings: 'readonly',
				pum_vars: 'readonly',
				pum_site_vars: 'readonly',
				pum_admin_vars: 'readonly',
				pum_block_editor_vars: 'readonly',
				window: 'readonly',
			},
		},
		settings: {
			jsdoc: {
				mode: 'typescript',
			},
		},
		rules: {
			'jsdoc/require-param': 'off',
			'jsdoc/require-param-type': 'off',
			'jsdoc/require-returns-type': 'off',
			'@wordpress/i18n-text-domain': [
				'error',
				{
					allowedTextDomain: [ 'popup-maker' ],
				},
			],
			'import/no-unresolved': [
				'error',
				{
					ignore: [ 'jquery', '^@popup-maker/' ],
				},
			],
		},
	},
];
