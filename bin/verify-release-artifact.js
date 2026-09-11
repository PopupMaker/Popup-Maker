#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const { execFileSync } = require( 'child_process' );

const pluginRoot = 'popup-maker';

const requiredPaths = [
	'popup-maker.php',
	'readme.txt',
	'dist/assets/site.css',
	'dist/assets/site-readable.css',
	'dist/assets/site-rtl.css',
	'dist/assets/site-rtl-readable.css',
	'vendor-prefixed/autoload.php',
];

const forbiddenPathPrefixes = [
	'.git/',
	'.github/',
	'.svn/',
	'__MACOSX/',
	'bin/',
	'docs/',
	'node_modules/',
	'packages/',
	'tests/',
	'vendor/',
];

/**
 * Convert an archive entry to a path relative to the plugin root.
 *
 * @param {string} entry Archive entry.
 * @return {string} Relative path.
 */
function relativeArtifactPath( entry ) {
	const prefix = `${ pluginRoot }/`;
	const normalized = entry.replace( /\\/g, '/' ).replace( /\/$/, '' );

	return normalized.startsWith( prefix )
		? normalized.slice( prefix.length )
		: normalized;
}

/**
 * Verify that every archive entry is safely contained by one plugin root.
 *
 * @param {string[]} entries Archive entries.
 * @return {string[]} Failures.
 */
function verifyArchiveStructure( entries ) {
	const failures = [];
	const seenEntries = new Set();

	for ( const entry of entries ) {
		const normalized = entry.replace( /\\/g, '/' );
		const pathWithoutTrailingSlash = normalized.replace( /\/$/, '' );
		const pathParts = pathWithoutTrailingSlash.split( '/' );

		if (
			entry !== normalized ||
			normalized.startsWith( '/' ) ||
			pathWithoutTrailingSlash.includes( '//' ) ||
			pathParts.includes( '.' ) ||
			pathParts.includes( '..' )
		) {
			failures.push( `unsafe archive path: ${ entry }` );
		}

		if (
			pluginRoot !== pathWithoutTrailingSlash &&
			! normalized.startsWith( `${ pluginRoot }/` )
		) {
			failures.push( `unexpected archive root: ${ entry }` );
		}

		if ( seenEntries.has( normalized ) ) {
			failures.push( `duplicate archive path: ${ entry }` );
		}

		seenEntries.add( normalized );
	}

	return [ ...new Set( failures ) ];
}

/**
 * Verify that the release contains only distributable paths.
 *
 * @param {string[]} entries Archive entries.
 * @return {string[]} Failures.
 */
function verifyReleaseManifest( entries ) {
	const failures = [];
	const relativePaths = new Set( entries.map( relativeArtifactPath ) );

	for ( const requiredPath of requiredPaths ) {
		if ( ! relativePaths.has( requiredPath ) ) {
			failures.push( `missing release path: ${ requiredPath }` );
		}
	}

	for ( const relativePath of relativePaths ) {
		const pathParts = relativePath.split( '/' );
		const fileName = pathParts[ pathParts.length - 1 ];
		const hasForbiddenPrefix = forbiddenPathPrefixes.some( ( prefix ) =>
			relativePath.startsWith( prefix )
		);
		const isRootMarkdown = /^[^/]+\.md$/i.test( relativePath );
		const isPlatformJunk =
			'.DS_Store' === fileName ||
			'Thumbs.db' === fileName ||
			fileName.startsWith( '._' );

		if ( hasForbiddenPrefix || isRootMarkdown || isPlatformJunk ) {
			failures.push( `forbidden release path: ${ relativePath }` );
		}
	}

	return [ ...new Set( failures ) ];
}

/**
 * Verify that ZIP entries are regular files or directories only.
 *
 * @param {string[]} entries    Archive entries.
 * @param {string[]} entryTypes Entry type characters from unzip.
 * @return {string[]} Failures.
 */
function verifyArchiveEntryTypes( entries, entryTypes ) {
	if ( entryTypes.length !== entries.length ) {
		return [
			`could not verify archive entry types: expected ${ entries.length }, found ${ entryTypes.length }`,
		];
	}

	return entryTypes.flatMap( ( entryType, index ) => {
		const entry = entries[ index ];

		if (
			( 'd' === entryType && ! entry.endsWith( '/' ) ) ||
			( '-' === entryType && entry.endsWith( '/' ) )
		) {
			return [ `archive entry type/name mismatch in ${ entry }` ];
		}

		return '-' === entryType || 'd' === entryType
			? []
			: [ `forbidden archive entry type in ${ entry }` ];
	} );
}

/**
 * Verify a release ZIP's structure and manifest.
 *
 * Plugin Check owns WordPress.org policy validation. This verifier only
 * protects the release archive boundary produced by this repository.
 *
 * @param {string} zipPath ZIP path.
 * @return {{entries:string[],failures:string[]}} Verification result.
 */
function verifyZip( zipPath ) {
	if ( ! fs.existsSync( zipPath ) ) {
		throw new Error( `Artifact not found: ${ zipPath }` );
	}

	const entries = execFileSync( 'unzip', [ '-Z1', zipPath ], {
		encoding: 'utf8',
	} )
		.split( '\n' )
		.filter( Boolean );
	const listing = execFileSync( 'unzip', [ '-Z', '-l', zipPath ], {
		encoding: 'utf8',
	} );
	const entryTypes = listing
		.split( '\n' )
		.filter( ( line ) => /^[bcdlps-].{9}\s/.test( line ) )
		.map( ( line ) => line.charAt( 0 ) );
	const failures = [
		...verifyArchiveStructure( entries ),
		...verifyArchiveEntryTypes( entries, entryTypes ),
		...verifyReleaseManifest( entries ),
	];

	return { entries, failures: [ ...new Set( failures ) ] };
}

function main() {
	const zipPath = process.argv[ 2 ] || 'popup-maker-latest.zip';
	const { entries, failures } = verifyZip( zipPath );

	if ( failures.length ) {
		console.error( 'Release artifact verification failed:' );
		for ( const failure of failures ) {
			console.error( `- ${ failure }` );
		}
		process.exitCode = 1;
		return;
	}

	console.log(
		`Release artifact verification passed (${ entries.length } entries checked).`
	);
}

if ( require.main === module ) {
	main();
}

module.exports = {
	relativeArtifactPath,
	verifyArchiveEntryTypes,
	verifyArchiveStructure,
	verifyReleaseManifest,
	verifyZip,
};

/* eslint-enable no-console */
