const eslintConfig = {
	root: true,
	extends: [ 'plugin:@code-atlantic/eslint-plugin/recommended' ],
	globals: {
		wp: 'readonly',
		wpApiSettings: 'readonly',
		pum_vars: 'readonly',
		pum_site_vars: 'readonly',
		pum_admin_vars: 'readonly',
		pum_block_editor_vars: 'readonly',
		window: 'readonly',
	},
	env: {
		jquery: true,
	},
	settings: {
		jsdoc: {
			mode: 'typescript',
		},
	},
	rules: {
		// Types live in TypeScript — don't require JSDoc param/type blocks.
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
				// Hate it but this is most reliable way to handle it since its already loaded.
				// @popup-maker/* are pnpm workspace packages — the resolver
				// can't always follow their symlinks in CI, but they resolve
				// correctly at runtime via tsconfig paths / webpack aliases.
				ignore: [ 'jquery', '^@popup-maker/' ],
			},
		],
	},
	overrides: [
		{
			// Turns off some of esnext rules for our assets JS until we migrate to babel or other.
			files: [ 'assets/js/**/*.js' ],
			rules: {
				'arrow-parens': 'off',
				'arrow-spacing': 'off',
				'computed-property-spacing': 'off',
				'constructor-super': 'off',
				'no-const-assign': 'off',
				'no-dupe-class-members': 'off',
				'no-duplicate-imports': 'off',
				'no-useless-computed-key': 'off',
				'no-useless-constructor': 'off',
				'no-var': 'off',
				'object-shorthand': 'off',
				'wrap-iife': 'off',
				camelcase: 0,
			},
		},
	],
};

module.exports = eslintConfig;
