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

// Extract unreleased changes using string manipulation for reliability
const unreleasedStart = changelogContent.indexOf( '## Unreleased' );
const nextVersionStart = changelogContent.indexOf(
	'## v',
	unreleasedStart + 1
);

if ( unreleasedStart === -1 ) {
	console.error( 'No "## Unreleased" section found in CHANGELOG.md.' );
	process.exit( 1 );
}

if ( nextVersionStart === -1 ) {
	console.error(
		'No version section found after "## Unreleased" in CHANGELOG.md.'
	);
	process.exit( 1 );
}

// Extract content between "## Unreleased\n\n" and the next "## v" section
const unreleasedContentStart = unreleasedStart + '## Unreleased\n\n'.length;
const unreleasedChangesText = changelogContent
	.substring( unreleasedContentStart, nextVersionStart )
	.trim();

// Count meaningful lines (non-empty, non-heading) for reporting
const unreleasedLines = unreleasedChangesText
	.split( '\n' )
	.filter( ( line ) => {
		const trimmed = line.trim();
		return trimmed !== '' && ! /^\*\*.*\*\*$/.test( trimmed );
	} );
const changeCount = unreleasedLines.length;

// Format unreleased changes for console output if verbose option is used
if ( isVerbose ) {
	console.log( 'Unreleased Changes:\n' + unreleasedChangesText );
}

// Use the original formatting for files (preserve structure)
const formattedFileChanges = unreleasedChangesText;

// Update CHANGELOG.md with new version using string manipulation
const beforeUnreleased = changelogContent.substring( 0, unreleasedStart );
const afterUnreleased = changelogContent.substring( nextVersionStart );
const updatedChangelog =
	beforeUnreleased +
	`## Unreleased\n\n## v${ newVersion } - ${ releaseDate }\n\n${ formattedFileChanges }\n\n` +
	afterUnreleased;

fs.writeFileSync( changelogFilePath, updatedChangelog, 'utf8' );

// Insert unreleased changes into readme.txt
const readmeContent = fs.readFileSync( readmeFilePath, 'utf8' );

// Find the changelog section and first version entry
const changelogPattern =
	/== Changelog ==\n\nFor the latest updates and release information:.*?\n\n(= v?\d+\.\d+\.\d+ - \d{4}-\d{2}-\d{2} =)/s;
const changelogMatch = readmeContent.match( changelogPattern );

if ( ! changelogMatch ) {
	console.error( 'Could not find changelog section or first version entry' );
	process.exit( 1 );
}

// Detect if existing entries use "v" prefix and match that format
const firstVersionEntry = changelogMatch[ 1 ];
const usesVPrefix = firstVersionEntry.includes( '= v' );
const versionPrefix = usesVPrefix ? 'v' : '';

// Create the new version entry
const newVersionEntry = `= ${ versionPrefix }${ newVersion } - ${ releaseDate } =\n\n${ formattedFileChanges }\n\n`;

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
