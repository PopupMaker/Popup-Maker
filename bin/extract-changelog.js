#!/usr/bin/env node
/* eslint-disable no-console */

/**
 * Extract changelog content for a specific version from CHANGELOG.md
 *
 * Usage:
 *   node bin/extract-changelog.js [version]
 *   node bin/extract-changelog.js 1.0.3
 *   node bin/extract-changelog.js --latest  # Extract latest released version
 *   node bin/extract-changelog.js --unreleased  # Extract unreleased changes
 */

const fs = require( 'fs' );
const path = require( 'path' );

// Parse command line arguments
const args = process.argv.slice( 2 );
const targetVersion = args[ 0 ];

if ( ! targetVersion ) {
	console.error(
		'❌ Usage: node bin/extract-changelog.js [version|--latest|--unreleased]'
	);
	console.error( '   Examples:' );
	console.error( '     node bin/extract-changelog.js 1.0.3' );
	console.error( '     node bin/extract-changelog.js --latest' );
	console.error( '     node bin/extract-changelog.js --unreleased' );
	process.exit( 1 );
}

const changelogPath = path.join( process.cwd(), 'CHANGELOG.md' );

if ( ! fs.existsSync( changelogPath ) ) {
	console.error( '❌ CHANGELOG.md not found in current directory' );
	process.exit( 1 );
}

const changelogContent = fs.readFileSync( changelogPath, 'utf8' );

/**
 * Extract the changelog section for a specific released version.
 * @param {string} content - The full CHANGELOG.md text.
 * @param {string} version - The version identifier to locate (e.g., "1.0.3").
 * @returns {string|null} The trimmed changelog content for the given version, or `null` if the version is not present.
 */
function extractVersionContent( content, version ) {
	// Support multiple version formats: ## v1.0.3, ## 1.0.3, etc.
	const versionPattern = new RegExp(
		`^## (?:v)?${ version }(?:\\s*-\\s*[0-9]{4}-[0-9]{2}-[0-9]{2})?\\s*\\n([\\s\\S]*?)(?=\\n## |\\n$)`,
		'm'
	);

	const match = content.match( versionPattern );
	return match ? match[ 1 ].trim() : null;
}

/**
 * Extract the content under the "Unreleased" section of a changelog.
 *
 * Captures the text after the "## Unreleased" heading up to the next "##" heading or end of file and trims surrounding whitespace.
 * @param {string} content - Full changelog text.
 * @returns {string|null} The trimmed content of the "Unreleased" section, or `null` if the section is missing or empty.
 */
function extractUnreleasedContent( content ) {
	const unreleasedPattern = /^## Unreleased\s*([\s\S]*?)(?=\n## |\n$)/m;
	const match = content.match( unreleasedPattern );

	if ( ! match || ! match[ 1 ].trim() ) {
		return null;
	}

	return match[ 1 ].trim();
}

/**
 * Find the first released version entry after the "Unreleased" section and return its version and associated changelog text.
 *
 * @param {string} content - Complete CHANGELOG.md text.
 * @returns {{version: string, content: string}|null} An object with `version` (semantic version string) and `content` (trimmed section text) when a released version is found, `null` otherwise.
 */
function extractLatestVersion( content ) {
	// Find the first version heading after "Unreleased"
	const versionPattern =
		/^## (?:v)?(\d+\.\d+\.\d+)(?:\s*-\s*[0-9]{4}-[0-9]{2}-[0-9]{2})?\s*\n([\s\S]*?)(?=\n## |\n$)/m;
	const matches = content.match( versionPattern );

	if ( ! matches ) {
		return null;
	}

	return {
		version: matches[ 1 ],
		content: matches[ 2 ].trim(),
	};
}

/**
 * Prepare changelog content for use as a GitHub release body.
 *
 * Normalizes list formatting and trims surrounding whitespace. If `content` is falsy,
 * returns a header that includes `version` and the message "No changelog content available."
 * @param {string} content - Raw changelog content to format.
 * @param {string} version - Version label used when no content is available.
 * @returns {string} Formatted changelog text suitable for a GitHub release body.
 */
function formatForGitHubRelease( content, version ) {
	if ( ! content ) {
		return `## ${ version }\n\nNo changelog content available.`;
	}

	let formatted = content;

	// Ensure proper formatting for GitHub markdown
	formatted = formatted
		.replace( /^([^#\n*-])/gm, '$1' ) // Don't modify lines starting with #, *, or -
		.replace( /^\s*[-*]\s+/gm, '- ' ) // Normalize bullet points
		.trim();

	return formatted;
}

// Main execution
try {
	let extractedContent = '';
	let versionNumber = '';

	if ( targetVersion === '--unreleased' ) {
		extractedContent = extractUnreleasedContent( changelogContent );
		versionNumber = 'Unreleased';

		if ( ! extractedContent ) {
			console.error( '❌ No unreleased changes found in CHANGELOG.md' );
			process.exit( 1 );
		}
	} else if ( targetVersion === '--latest' ) {
		const latest = extractLatestVersion( changelogContent );

		if ( ! latest ) {
			console.error( '❌ No released versions found in CHANGELOG.md' );
			process.exit( 1 );
		}

		extractedContent = latest.content;
		versionNumber = latest.version;
	} else {
		// Extract specific version
		extractedContent = extractVersionContent(
			changelogContent,
			targetVersion
		);
		versionNumber = targetVersion;

		if ( ! extractedContent ) {
			console.error(
				`❌ Version ${ targetVersion } not found in CHANGELOG.md`
			);
			process.exit( 1 );
		}
	}

	// Format and output the content
	const formattedContent = formatForGitHubRelease(
		extractedContent,
		versionNumber
	);

	// Output to stdout (can be captured by GitHub Actions)
	console.log( formattedContent );
} catch ( error ) {
	console.error( '❌ Error processing changelog:', error.message );
	process.exit( 1 );
}

/* eslint-enable no-console */