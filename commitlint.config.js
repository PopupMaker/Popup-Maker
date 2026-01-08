module.exports = {
	extends: [ '@commitlint/config-conventional' ],
	rules: {
		'scope-enum': [
			2,
			'always',
			[
				'core', // Core functionality
				'admin', // Admin interface
				'editor', // Block editor
				'cta', // CTA system
				'triggers', // Trigger system
				'analytics', // Analytics/tracking
				'api', // API changes
				'deps', // Dependencies
				'docs', // Documentation
				'release', // Release process
			],
		],
		'type-enum': [
			2,
			'always',
			[
				'feat', // New feature (MINOR bump)
				'fix', // Bug fix (PATCH bump)
				'perf', // Performance improvement (PATCH bump)
				'refactor', // Code refactoring (no version bump)
				'docs', // Documentation only (no version bump)
				'style', // Code style changes (no version bump)
				'test', // Adding/updating tests (no version bump)
				'chore', // Maintenance tasks (no version bump)
				'revert', // Revert previous commit
				'ci', // CI/CD changes (no version bump)
			],
		],
		'subject-max-length': [ 2, 'always', 72 ],
		'body-max-line-length': [ 2, 'always', 100 ],
	},
};
