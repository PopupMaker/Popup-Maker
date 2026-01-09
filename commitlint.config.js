/**
 * Commitlint configuration for Popup Maker
 *
 * Aligned with existing GitHub issue labels (labels.json):
 * - Types map to type-* labels (feature, bug, improvement)
 * - Scopes map to component-*, integration-*, and scope-* labels
 *
 * Version bump rules:
 * - MAJOR: BREAKING CHANGE in commit footer
 * - MINOR: feat (new features)
 * - PATCH: fix, improve, perf (fixes, enhancements, performance)
 * - NO BUMP: refactor, docs, style, test, build, ci, chore
 */

module.exports = {
	extends: [ '@commitlint/config-conventional' ],
	rules: {
		'type-enum': [
			2,
			'always',
			[
				// Version-bumping types (aligned with labels.json)
				'feat', // type-feature: New feature (MINOR bump)
				'fix', // type-bug: Bug fix (PATCH bump)
				'improve', // type-improvement: Enhancement to existing (PATCH bump)
				'perf', // Performance optimization (PATCH bump)

				// Non-version-bumping types
				'refactor', // Code refactoring (no bump)
				'docs', // Documentation only (no bump)
				'style', // Code formatting (no bump)
				'test', // Tests only (no bump)
				'build', // Build system (no bump)
				'ci', // CI/CD changes (no bump)
				'chore', // Maintenance (no bump)
				'revert', // Revert previous commit
			],
		],
		'scope-enum': [
			2,
			'always',
			[
				// Components (component-* labels)
				'admin', // component-admin
				'conditions', // component-conditions
				'cookies', // component-cookies
				'frontend', // component-front-end
				'popup', // component-popup
				'theme', // component-theme
				'triggers', // component-triggers

				// Integrations (integration-* labels)
				'forms', // integration-forms
				'extensions', // integration-extension
				'integrations', // integration-other (general third-party)

				// Cross-cutting scopes (scope-* labels)
				'accessibility', // scope-accessibility
				'performance', // scope-performance
				'ui', // scope-ui
				'ux', // scope-ux
				'build', // scope-build
				'deps', // scope-dependency
				'tests', // scope-unit-tests
				'api', // scope-developer-apis

				// Development/Infrastructure
				'core', // Core functionality
				'docs', // Documentation
				'release', // Release process
				'support', // scope-support (support team tooling)
			],
		],
		'subject-max-length': [ 2, 'always', 72 ],
		'body-max-line-length': [ 2, 'always', 100 ],
		// Warn instead of error for case - allows proper nouns, project names, etc.
		'subject-case': [ 1, 'never', [ 'upper-case' ] ],
	},
};
