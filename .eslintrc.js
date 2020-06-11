module.exports = {
	"extends": [
		"plugin:@wordpress/eslint-plugin/recommended",
	],
	'overrides': [
		{
			// Turns off some of esnext rules for our assets JS until we migrate to babel or other.
			'files': ['assets/js/**/*.js'],
			'rules': {
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
			}
		}
	],
	'env': {
		browser: true,
		jquery: true,
	}
};
