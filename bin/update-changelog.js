const fs = require( 'fs' );

// Read the current version number from package.json
const newVersion = process.argv[ 2 ];
const releaseDate = new Date().toISOString().split( 'T' )[ 0 ]; // Format as YYYY-MM-DD

if ( ! newVersion ) {
	console.error( 'Please provide the new version number as an argument.' );
	process.exit( 1 );
}

// Check if --verbose or -v is passed
const isVerbose =
	process.argv.includes( '--verbose' ) || process.argv.includes( '-v' );

// Read and process CHANGELOG.md
const changelogFilePath = 'CHANGELOG.md';
const readmeFilePath = 'readme.txt';

let changelogContent = fs.readFileSync( changelogFilePath, 'utf8' );

// Improved pattern to capture unreleased changes
const unreleasedPattern = /^## Unreleased\s([\s\S]*?)(?=\n## |\n$)/m;
const unreleasedMatch = changelogContent.match( unreleasedPattern );

if ( ! unreleasedMatch ) {
	console.error( 'No unreleased changes found in CHANGELOG.md.' );
	process.exit( 1 );
}

const unreleasedChanges = unreleasedMatch[ 1 ]
	.trim()
	.split( '\n' )
	.filter( ( line ) => line.trim() !== '' );
const changeCount = unreleasedChanges.length;

// Format unreleased changes into a numbered list if verbose option is used
if ( isVerbose ) {
	const formattedConsoleChanges = unreleasedChanges
		.map(
			( change, index ) =>
				`${ index + 1 }. ${ change.replace( /^\*\s*/, '' ) }`
		)
		.join( '\n' );
	console.log( 'Unreleased Changes:\n' + formattedConsoleChanges );
}

// Format unreleased changes into a bullet list for files
const formattedFileChanges = unreleasedChanges
	.map( ( change ) => `* ${ change.replace( /^\*\s*/, '' ) }` )
	.join( '\n' );

// Update CHANGELOG.md with new version
const updatedChangelog = changelogContent.replace(
	unreleasedPattern,
	`## Unreleased\n\n## v${ newVersion } - ${ releaseDate }\n\n${ formattedFileChanges }`
);

fs.writeFileSync( changelogFilePath, updatedChangelog, 'utf8' );

// Insert unreleased changes into readme.txt
const readmeContent = fs.readFileSync( readmeFilePath, 'utf8' );

// Find the changelog section and first version entry
const changelogPattern = /== Changelog ==\n\nFor the latest updates and release information:.*?\n\n(= v\d+\.\d+\.\d+ - \d{4}-\d{2}-\d{2} =)/s;
const changelogMatch = readmeContent.match( changelogPattern );

if ( ! changelogMatch ) {
	console.error( 'Could not find changelog section or first version entry' );
	process.exit( 1 );
}

// Create the new version entry
const newVersionEntry = `= v${ newVersion } - ${ releaseDate } =\n\n${ formattedFileChanges }\n\n`;

// Insert the new version entry before the first existing version
const newChangelog = readmeContent.replace(
	changelogPattern,
	( match, firstVersion ) => {
		return match.replace(
			firstVersion,
			`${ newVersionEntry }${ firstVersion }`
		);
	}
);

fs.writeFileSync( readmeFilePath, newChangelog.trim(), 'utf8' );

// Output the count of changes
console.log(
	`Changelog updated successfully with ${ changeCount } change(s).`
);
