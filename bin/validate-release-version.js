#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );

function readFile( projectRoot, fileName ) {
	return fs.readFileSync( path.join( projectRoot, fileName ), 'utf8' );
}

function extractMatch( contents, pattern, label ) {
	const match = contents.match( pattern );

	if ( ! match ) {
		throw new Error( `Could not read ${ label }.` );
	}

	return match[ 1 ];
}

function compareVersions( left, right ) {
	const leftParts = left.split( '.' ).map( Number );
	const rightParts = right.split( '.' ).map( Number );

	for ( let index = 0; index < 3; index++ ) {
		if ( leftParts[ index ] !== rightParts[ index ] ) {
			return leftParts[ index ] - rightParts[ index ];
		}
	}

	return 0;
}

function validateReleaseVersion( {
	projectRoot = process.cwd(),
	version,
	previousVersion,
} ) {
	if ( ! /^\d+\.\d+\.\d+$/.test( version || '' ) ) {
		throw new Error( 'Release version must use stable X.Y.Z format.' );
	}

	if (
		previousVersion &&
		( ! /^\d+\.\d+\.\d+$/.test( previousVersion ) ||
			compareVersions( version, previousVersion ) <= 0 )
	) {
		throw new Error(
			`Release ${ version } must be newer than ${ previousVersion }.`
		);
	}

	const packageVersion = JSON.parse(
		readFile( projectRoot, 'package.json' )
	).version;
	const pluginContents = readFile( projectRoot, 'popup-maker.php' );
	const readmeContents = readFile( projectRoot, 'readme.txt' );
	const changelogContents = readFile( projectRoot, 'CHANGELOG.md' );
	const versions = {
		'package.json': packageVersion,
		'popup-maker.php header': extractMatch(
			pluginContents,
			/^\s*\*\s*Version:\s*([^\s]+)\s*$/m,
			'plugin header version'
		),
		'popup-maker.php config': extractMatch(
			pluginContents,
			/'version'\s*=>\s*'([^']+)'/,
			'plugin config version'
		),
		'readme.txt stable tag': extractMatch(
			readmeContents,
			/^Stable tag:\s*([^\s]+)\s*$/m,
			'readme stable tag'
		),
	};
	const mismatches = Object.entries( versions ).filter(
		( [ , foundVersion ] ) => foundVersion !== version
	);

	if ( mismatches.length ) {
		throw new Error(
			`Version mismatch: ${ mismatches
				.map(
					( [ label, foundVersion ] ) =>
						`${ label }=${ foundVersion }`
				)
				.join( ', ' ) }; expected ${ version }.`
		);
	}

	const escapedVersion = version.replace( /\./g, '\\.' );
	const datedHeading = `\\d{4}-\\d{2}-\\d{2}`;

	if (
		! new RegExp(
			`^## v${ escapedVersion } - ${ datedHeading }$`,
			'm'
		).test( changelogContents )
	) {
		throw new Error( `CHANGELOG.md has no dated v${ version } entry.` );
	}

	if (
		! new RegExp(
			`^= ${ escapedVersion } - ${ datedHeading } =$`,
			'm'
		).test( readmeContents )
	) {
		throw new Error(
			`readme.txt has no dated ${ version } changelog entry.`
		);
	}

	return versions;
}

function getArgument( name ) {
	const index = process.argv.indexOf( name );
	return index === -1 ? '' : process.argv[ index + 1 ] || '';
}

if ( require.main === module ) {
	try {
		const version = getArgument( '--version' );
		const previousVersion = getArgument( '--previous-version' );
		validateReleaseVersion( { version, previousVersion } );
		console.log(
			`Release version ${ version } is consistent${
				previousVersion ? ` and newer than ${ previousVersion }` : ''
			}.`
		);
	} catch ( error ) {
		console.error( error.message );
		process.exit( 1 );
	}
}

module.exports = {
	compareVersions,
	validateReleaseVersion,
};

/* eslint-enable no-console */
