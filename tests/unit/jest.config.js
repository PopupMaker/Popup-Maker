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
	transformIgnorePatterns: [
		'<rootDir>/node_modules/.pnpm/(?!(uuid)@)',
		'node_modules/(?!.pnpm|uuid)',
	],
	moduleNameMapper: {
		'^@popup-maker/([^/]+)$': '<rootDir>/packages/$1/src/index.ts',
		'^@wordpress/theme$': '<rootDir>/tests/unit/mocks/wordpress-theme.js',
	},
};
