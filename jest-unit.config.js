module.exports = {
	...require( '@wordpress/scripts/config/jest-unit.config' ),
	// testMatch: [ '<rootDir>/packages/*/src/**/__tests__/**/*.test.[jt]s?(x)' ],
	testPathIgnorePatterns: [
		'/node_modules/',
		'/build/',
		'/build-types/',
		'\\.d\\.ts$',
	],
};
