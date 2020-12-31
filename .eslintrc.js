const eslintConfig = {
	root: true,
	extends: [
		'eslint:recommended',
		'plugin:@wordpress/eslint-plugin/recommended-with-formatting',
		'plugin:@wordpress/eslint-plugin/jsdoc',
		'plugin:eslint-comments/recommended',
	],
	plugins: [ 'standard', 'import', 'promise' ],
	globals: {
		_: 'readonly',
		wp: 'readonly',
		pum_vars: 'readonly',
		pum_site_vars: 'readonly',
		pum_admin_vars: 'readonly',
		pum_block_editor_vars: 'readonly',
	},
	env: {
		browser: true,
		jquery: true,
	},
	settings: {
		jsdoc: {
			mode: 'typescript',
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
			},
		},
	],
};

module.exports = eslintConfig;
