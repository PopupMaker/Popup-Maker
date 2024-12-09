const originalConfig = require( '@code-atlantic/eslint-plugin/lib/configs/recommended' );

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
		browser: true,
		jquery: true,
	},
	settings: {
		'import/resolver': {
			node: {
				moduleDirectory: [ 'node_modules' ],
			},
		},
	},
	parserOptions: {
		requireConfigFile: false,
		babelOptions: {
			presets: [ require.resolve( '@wordpress/babel-preset-default' ) ],
		},
	},
	rules: {},
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
