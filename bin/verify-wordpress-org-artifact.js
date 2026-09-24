#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );

const requiredPaths = [
	'popup-maker.php',
	'readme.txt',
	'languages/index.php',
	'languages/popup-maker.pot',
];

/**
 * Load the WordPress.org-only exclusion patterns.
 *
 * @param {string} manifestPath Exclusion manifest path.
 * @return {string[]} Exclusion patterns.
 */
function loadExcludePatterns( manifestPath ) {
	if ( ! fs.existsSync( manifestPath ) ) {
		throw new Error(
			`WordPress.org exclusion manifest not found: ${ manifestPath }`
		);
	}

	const patterns = fs
		.readFileSync( manifestPath, 'utf8' )
		.split( /\r?\n/ )
		.map( ( line ) => line.trim() )
		.filter( ( line ) => line && ! line.startsWith( '#' ) );

	if ( ! patterns.length ) {
		throw new Error( 'WordPress.org exclusion manifest is empty.' );
	}

	for ( const pattern of patterns ) {
		const normalized = pattern.replace( /^\//, '' );

		if (
			path.isAbsolute( normalized ) ||
			normalized.split( '/' ).includes( '..' )
		) {
			throw new Error(
				`Unsafe WordPress.org exclusion pattern: ${ pattern }`
			);
		}
	}

	return patterns;
}

/**
 * Convert a Git-style exclusion glob to a regular expression.
 *
 * @param {string} pattern Exclusion pattern.
 * @return {RegExp} Matching expression.
 */
function globPatternToRegExp( pattern ) {
	let expression = '^';
	const normalized = pattern.replace( /^\//, '' );

	for ( let index = 0; index < normalized.length; index++ ) {
		const character = normalized[ index ];

		if ( '*' === character ) {
			if ( '*' === normalized[ index + 1 ] ) {
				expression += '.*';
				index++;
			} else {
				expression += '[^/]*';
			}
		} else if ( '?' === character ) {
			expression += '[^/]';
		} else {
			expression += character.replace( /[|\\{}()[\]^$+?.]/g, '\\$&' );
		}
	}

	return new RegExp( `${ expression }$` );
}

/**
 * List files beneath a build directory.
 *
 * @param {string} directory Directory to inspect.
 * @param {string} prefix    Relative path prefix.
 * @return {string[]} Relative file paths.
 */
function listFiles( directory, prefix = '' ) {
	const files = [];

	for ( const entry of fs.readdirSync( directory, {
		withFileTypes: true,
	} ) ) {
		const relativePath = prefix
			? `${ prefix }/${ entry.name }`
			: entry.name;
		const absolutePath = path.join( directory, entry.name );

		if ( entry.isDirectory() ) {
			files.push( ...listFiles( absolutePath, relativePath ) );
		} else {
			files.push( relativePath );
		}
	}

	return files;
}

/**
 * Find files forbidden by the WordPress.org exclusion contract.
 *
 * @param {string}   buildDir Build directory.
 * @param {string[]} patterns Exclusion patterns.
 * @return {string[]} Forbidden relative paths.
 */
function findExcludedPaths( buildDir, patterns ) {
	const expressions = patterns.map( globPatternToRegExp );

	return listFiles( buildDir )
		.filter( ( relativePath ) =>
			expressions.some( ( expression ) =>
				expression.test( relativePath )
			)
		)
		.sort();
}

/**
 * Verify the staged WordPress.org artifact.
 *
 * @param {string} buildDir     Build directory.
 * @param {string} manifestPath Exclusion manifest path.
 * @return {string[]} Failures.
 */
function verifyWordPressOrgArtifact( buildDir, manifestPath ) {
	if (
		! fs.existsSync( buildDir ) ||
		! fs.statSync( buildDir ).isDirectory()
	) {
		return [ `WordPress.org build directory not found: ${ buildDir }` ];
	}

	const patterns = loadExcludePatterns( manifestPath );
	const failures = findExcludedPaths( buildDir, patterns ).map(
		( relativePath ) =>
			`excluded WordPress.org path present: ${ relativePath }`
	);

	for ( const requiredPath of requiredPaths ) {
		if ( ! fs.existsSync( path.join( buildDir, requiredPath ) ) ) {
			failures.push( `missing WordPress.org path: ${ requiredPath }` );
		}
	}

	return failures;
}

function main() {
	const buildDir = process.argv[ 2 ];
	const manifestPath = process.argv[ 3 ] || '.wordpress-org-excludes';

	if ( ! buildDir ) {
		console.error(
			'Usage: node bin/verify-wordpress-org-artifact.js <build-dir> [manifest]'
		);
		process.exitCode = 1;
		return;
	}

	const failures = verifyWordPressOrgArtifact( buildDir, manifestPath );
	if ( failures.length ) {
		console.error( 'WordPress.org artifact verification failed:' );
		for ( const failure of failures ) {
			console.error( `- ${ failure }` );
		}
		process.exitCode = 1;
		return;
	}

	console.log( 'WordPress.org artifact verification passed.' );
}

if ( require.main === module ) {
	main();
}

module.exports = {
	findExcludedPaths,
	globPatternToRegExp,
	listFiles,
	loadExcludePatterns,
	verifyWordPressOrgArtifact,
};

/* eslint-enable no-console */
