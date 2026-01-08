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
 * Extract content for a specific version
 * @param {string} content - Full changelog content
 * @param {string} version - Version to extract (e.g., "1.0.3")
 * @return {string|null} - Extracted content or null if not found
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
 * Extract unreleased changes
 * @param {string} content - Full changelog content
 * @return {string|null} - Extracted unreleased content or null if not found
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
 * Extract latest released version content
 * @param {string} content - Full changelog content
 * @return {object|null} - Object with version and content, or null if not found
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
 * Format content for GitHub release body
 * @param {string} content - Raw changelog content
 * @param {string} version - Version number
 * @return {string} - Formatted content
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
