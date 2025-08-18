/**
 * Jest configuration for Pro Upgrader JavaScript unit tests.
 */

module.exports = {
	// Extend the main Jest configuration
	...require( '../jest.config.js' ),

	// Test directory
	rootDir: '../../../',
	testMatch: [ '<rootDir>/tests/unit/js/**/*.test.js' ],

	// Setup files
	setupFilesAfterEnv: [ '<rootDir>/tests/unit/js/setup.js' ],

	// Module directories
	moduleDirectories: [ 'node_modules', '<rootDir>/assets/js/src' ],

	// Coverage settings
	collectCoverage: true,
	collectCoverageFrom: [
		'assets/js/src/**/*.js',
		'!assets/js/src/**/*.min.js',
		'!assets/js/src/**/vendor/**',
		'!**/node_modules/**',
	],
	coverageDirectory: '<rootDir>/tests/unit/js/coverage',
	coverageReporters: [ 'text', 'lcov', 'html' ],
	coverageThreshold: {
		global: {
			branches: 70,
			functions: 80,
			lines: 80,
			statements: 80,
		},
	},

	// Test environment
	testEnvironment: 'jsdom',

	// Module name mapping for WordPress dependencies
	moduleNameMapper: {
		'^@wordpress/(.*)$': '@wordpress/$1',
		'^jquery$': 'jquery',
	},

	// Transform settings
	transform: {
		'^.+\\.jsx?$': 'babel-jest',
	},

	// Ignore patterns
	testPathIgnorePatterns: [
		'/node_modules/',
		'/vendor/',
		'/dist/',
		'/build/',
	],

	// Verbose output
	verbose: true,

	// Timeout settings
	testTimeout: 10000,

	// Clear mocks between tests
	clearMocks: true,

	// Restore mocks after each test
	restoreMocks: true,
};
