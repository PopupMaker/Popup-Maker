module.exports = {
	...require( '@wordpress/scripts/config/jest-unit.config' ),
	rootDir: '../../',
	// testMatch: [ '<rootDir>/packages/*/src/**/__tests__/**/*.test.[jt]s?(x)' ],
	testPathIgnorePatterns: [
		'/specs/',
		'/node_modules/',
		'/build/',
		'/build-types/',
		'\\.d\\.ts$',
	],
};
