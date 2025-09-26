#!/usr/bin/env node
/* eslint-disable no-console */

/**
 * Release preparation script for Popup Maker
 *
 * This script automates the release preparation workflow:
 * 1. Calculates next version (or uses provided version)
 * 2. Starts git flow release branch
 * 3. Updates versions in all files
 * 4. Updates changelog
 * 5. Updates package-lock.json
 * 6. Builds release assets
 * 7. Confirms and commits changes
 * 8. Finishes git flow release (with non-interactive mode)
 * 9. Offers to push changes
 *
 * Usage:
 *   node bin/prepare-release.js [version] [options]
 *
 * Parameters:
 *   [version]    Specific version to release (optional, defaults to patch increment)
 *   --major      Increment major version (X+1.0.0)
 *   --minor      Increment minor version (X.Y+1.0)
 *   --patch      Increment patch version (X.Y.Z+1) [default]
 *   --dry-run    Show what would be done without making changes
 *   --no-build   Skip the release build step
 *   --auto       Skip all confirmations (dangerous!)
 */

const fs = require( 'fs' );
const { execSync, spawn } = require( 'child_process' );
const minimist = require( 'minimist' );
const readline = require( 'readline' );

const argv = minimist( process.argv.slice( 2 ) );

// Colors for console output 🎨
const colors = {
	red: '\x1b[31m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	magenta: '\x1b[35m',
	cyan: '\x1b[36m',
	reset: '\x1b[0m',
	bold: '\x1b[1m',
};

function colorize( color, text ) {
	return `${ colors[ color ] }${ text }${ colors.reset }`;
}

function log( message, color = 'reset' ) {
	console.log( colorize( color, message ) );
}

function error( message ) {
	console.error( colorize( 'red', `❌ ${ message }` ) );
}

function success( message ) {
	console.log( colorize( 'green', `✅ ${ message }` ) );
}

function warn( message ) {
	console.log( colorize( 'yellow', `⚠️  ${ message }` ) );
}

function info( message ) {
	console.log( colorize( 'blue', `ℹ️  ${ message }` ) );
}

// Show help
if ( argv.help || argv.h ) {
	console.log( `
${ colorize( 'bold', '🚀 Popup Maker Release Preparation Script' ) }

${ colorize( 'cyan', 'USAGE:' ) }
  node bin/prepare-release.js [version] [options]

${ colorize( 'cyan', 'VERSION:' ) }
  [version]    Specific version to release (e.g., 1.21.5)
               If not provided, uses increment flags

${ colorize( 'cyan', 'INCREMENT OPTIONS:' ) }
  --major      Increment major version (X+1.0.0)
  --minor      Increment minor version (X.Y+1.0)
  --patch      Increment patch version (X.Y.Z+1) [default]

${ colorize( 'cyan', 'CONTROL OPTIONS:' ) }
  --dry-run    Show what would be done without making changes
  --no-build   Skip the release build step
  --auto       Skip all confirmations (dangerous!)
  --help, -h   Show this help message

${ colorize( 'cyan', 'EXAMPLES:' ) }
  node bin/prepare-release.js                    # Patch increment (1.21.4 → 1.21.5)
  node bin/prepare-release.js --minor            # Minor increment (1.21.4 → 1.22.0)
  node bin/prepare-release.js --major            # Major increment (1.21.4 → 2.0.0)
  node bin/prepare-release.js 2.1.0              # Specific version
  node bin/prepare-release.js 1.21.5 --dry-run  # Test without changes
  node bin/prepare-release.js --auto --no-build  # Automated without build

${ colorize( 'cyan', 'WORKFLOW:' ) }
  1. Creates git flow release branch
  2. Updates versions in all files
  3. Updates changelog
  4. Updates package-lock.json
  5. Builds release assets
  6. Commits changes
  7. Finishes git flow release
  8. Offers to push changes
` );
	process.exit( 0 );
}

// Configuration
const dryRun = argv[ 'dry-run' ];
const noBuild = argv[ 'no-build' ];
const autoMode = argv.auto;

// Check if we're in the right directory
function checkProjectRoot() {
	if (
		! fs.existsSync( 'package.json' ) ||
		! fs.existsSync( 'popup-maker.php' )
	) {
		error(
			'This script must be run from the popup-maker plugin root directory.'
		);
		process.exit( 1 );
	}
}

// Get current version from package.json
function getCurrentVersion() {
	const packageJson = JSON.parse( fs.readFileSync( 'package.json', 'utf8' ) );
	return packageJson.version;
}

// Calculate next version based on current version and increment type
function calculateNextVersion( currentVersion, incrementType = 'patch' ) {
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const [ major, minor, patch ] = currentVersion.split( '.' ).map( Number );

	switch ( incrementType ) {
		case 'major':
			return `${ major + 1 }.0.0`;
		case 'minor':
			return `${ major }.${ minor + 1 }.0`;
		case 'patch':
		default:
			return `${ major }.${ minor }.${ patch + 1 }`;
	}
}

// Determine version to use
function getTargetVersion() {
	if ( argv._[ 0 ] ) {
		// Specific version provided
		return argv._[ 0 ];
	}

	const currentVersion = getCurrentVersion();

	// Determine increment type
	let incrementType = 'patch'; // default
	if ( argv.major ) {
		incrementType = 'major';
	} else if ( argv.minor ) {
		incrementType = 'minor';
	} else if ( argv.patch ) {
		incrementType = 'patch';
	}

	return calculateNextVersion( currentVersion, incrementType );
}

// Execute command and return output
function execCommand( command, options = {} ) {
	if ( dryRun ) {
		info( `[DRY RUN] Would execute: ${ command }` );
		return '';
	}

	try {
		return execSync( command, {
			encoding: 'utf8',
			stdio: options.silent ? 'pipe' : 'inherit',
			...options,
		} );
	} catch ( err ) {
		if ( ! options.allowFailure ) {
			error( `Command failed: ${ command }` );
			error( err.message );
			process.exit( 1 );
		}
		return null;
	}
}

// Prompt user for confirmation
function prompt( question ) {
	if ( autoMode || dryRun ) {
		info( `[AUTO/DRY-RUN] Would prompt: ${ question }` );
		return Promise.resolve( true );
	}

	const rl = readline.createInterface( {
		input: process.stdin,
		output: process.stdout,
	} );

	return new Promise( ( resolve ) => {
		rl.question( `${ question } (y/N): `, ( answer ) => {
			rl.close();
			resolve(
				answer.toLowerCase() === 'y' || answer.toLowerCase() === 'yes'
			);
		} );
	} );
}

// Check git status
function checkGitStatus() {
	const status = execCommand( 'git status --porcelain', { silent: true } );
	if ( status && status.trim() ) {
		error(
			'Working directory is not clean. Please commit or stash your changes.'
		);
		console.log( status );
		process.exit( 1 );
	}
	success( 'Working directory is clean' );
}

// Check if git flow is available
function checkGitFlow() {
	try {
		execCommand( 'git flow version', { silent: true } );
		success( 'Git flow is available' );
	} catch {
		error( 'Git flow is not installed. Please install git-flow-avh.' );
		info( 'Install with: brew install git-flow-avh' );
		process.exit( 1 );
	}
}

// Start git flow release
function startGitFlowRelease( version ) {
	log( `Starting git flow release: ${ version }`, 'cyan' );
	execCommand( `git flow release start ${ version }` );
	success( `Started release branch: release/${ version }` );
}

// Update versions in all files
function updateVersions( version ) {
	log( `Updating versions to ${ version }`, 'cyan' );
	execCommand( `node bin/update-versions.js ${ version }` );
	success( 'Versions updated' );
}

// Update changelog
function updateChangelog( version ) {
	log( `Updating changelog for ${ version }`, 'cyan' );
	execCommand( `node bin/update-changelog.js ${ version }` );
	success( 'Changelog updated' );
}

// Update package-lock.json
function updatePackageLock() {
	log( 'Updating package-lock.json', 'cyan' );
	execCommand( 'npm install' );
	success( 'Package lock updated' );
}

// Build release
function buildRelease() {
	if ( noBuild ) {
		warn( 'Skipping build step (--no-build flag)' );
		return;
	}

	log( 'Building release assets', 'cyan' );
	execCommand( 'npm run release' );
	success( 'Release assets built' );
}

// Show git diff for review
function showChanges() {
	log( 'Changes to be committed:', 'cyan' );
	execCommand( 'git diff --cached --stat' );
	console.log( '' );
	execCommand( 'git diff --cached' );
}

// Commit changes
function commitChanges( version ) {
	log( 'Adding and committing changes', 'cyan' );

	// Add all changed files
	execCommand( 'git add .' );

	// Create commit message
	const commitMessage = `update version & changelog for v${ version }`;

	execCommand( `git commit -m "${ commitMessage }"` );
	success( `Committed changes for v${ version }` );
}

// Finish git flow release (non-interactive)
function finishGitFlowRelease( version ) {
	log( `Finishing git flow release: ${ version }`, 'cyan' );

	if ( dryRun ) {
		info(
			`[DRY RUN] Would execute: git flow release finish ${ version } -m "v${ version }"`
		);
		success( `Finished release: v${ version }` );
		success( 'Release branch merged and tagged' );
		return Promise.resolve();
	}

	// Set environment variables for non-interactive mode
	const env = {
		...process.env,
		GIT_MERGE_AUTOEDIT: 'no',
	};

	try {
		// Use spawn for better control over git flow finish
		const gitFlow = spawn(
			'git',
			[ 'flow', 'release', 'finish', version, '-m', `v${ version }` ],
			{
				stdio: 'inherit',
				env,
			}
		);

		return new Promise( ( resolve, reject ) => {
			gitFlow.on( 'close', ( code ) => {
				if ( code === 0 ) {
					success( `Finished release: v${ version }` );
					success( 'Release branch merged and tagged' );
					resolve();
				} else {
					error(
						`Git flow release finish failed with code ${ code }`
					);
					reject(
						new Error( `Git flow failed with code ${ code }` )
					);
				}
			} );
		} );
	} catch ( err ) {
		error( 'Failed to finish git flow release' );
		throw err;
	}
}

// Push changes with options
async function handlePush() {
	log( 'Release is complete locally. Choose push option:', 'cyan' );
	console.log( '1. Push tags only' );
	console.log( '2. Push branches only' );
	console.log( '3. Push both tags and branches' );
	console.log( '4. Skip pushing (manual push later)' );

	if ( autoMode || dryRun ) {
		info(
			'[AUTO/DRY-RUN] Would prompt for push option, selecting push both (3)'
		);
		execCommand( 'git push origin --tags' );
		execCommand( 'git push origin --all' );
		return;
	}

	const rl = readline.createInterface( {
		input: process.stdin,
		output: process.stdout,
	} );

	return new Promise( ( resolve ) => {
		rl.question( 'Enter your choice (1-4): ', ( answer ) => {
			rl.close();

			switch ( answer.trim() ) {
				case '1':
					execCommand( 'git push origin --tags' );
					success( 'Tags pushed' );
					break;
				case '2':
					execCommand( 'git push origin --all' );
					success( 'Branches pushed' );
					break;
				case '3':
					execCommand( 'git push origin --tags' );
					execCommand( 'git push origin --all' );
					success( 'Tags and branches pushed' );
					break;
				case '4':
					info( 'Skipping push. Remember to push manually:' );
					info( '  git push origin --tags' );
					info( '  git push origin --all' );
					break;
				default:
					warn( 'Invalid option, skipping push' );
					break;
			}
			resolve();
		} );
	} );
}

// Main execution
async function main() {
	try {
		log(
			colorize( 'bold', '🚀 Popup Maker Release Preparation' ),
			'magenta'
		);
		console.log( '' );

		// Pre-flight checks
		checkProjectRoot();
		checkGitStatus();
		checkGitFlow();

		const currentVersion = getCurrentVersion();
		const targetVersion = getTargetVersion();

		log( `Current version: ${ currentVersion }`, 'yellow' );
		log( `Target version:  ${ targetVersion }`, 'green' );
		console.log( '' );

		if ( dryRun ) {
			warn( 'DRY RUN MODE - No changes will be made' );
			console.log( '' );
		}

		// Confirmation
		const confirmed = await prompt(
			`Proceed with release ${ targetVersion }?`
		);
		if ( ! confirmed ) {
			info( 'Release cancelled by user' );
			process.exit( 0 );
		}

		console.log( '' );

		// Execute release steps
		startGitFlowRelease( targetVersion );
		updateVersions( targetVersion );
		updateChangelog( targetVersion );
		updatePackageLock();
		buildRelease();

		console.log( '' );

		// Review changes
		if ( ! dryRun && ! autoMode ) {
			showChanges();
			console.log( '' );

			const commitConfirmed = await prompt( 'Commit these changes?' );
			if ( ! commitConfirmed ) {
				error( 'Aborting release - changes not committed' );
				info(
					'You are still on the release branch. Clean up manually:'
				);
				info( `  git flow release delete ${ targetVersion }` );
				process.exit( 1 );
			}
		}

		commitChanges( targetVersion );
		console.log( '' );

		// Finish git flow release
		await finishGitFlowRelease( targetVersion );
		console.log( '' );

		// Handle pushing
		await handlePush();

		console.log( '' );
		success( `Release ${ targetVersion } completed successfully! 🎉` );

		// Final instructions
		log( 'Next steps:', 'cyan' );
		info( '1. Verify the release tag was created' );
		info( '2. Check that branches were merged correctly' );
		info( '3. Create GitHub release from the tag' );
		info( '4. Test the release build' );
	} catch ( err ) {
		console.log( '' );
		error( 'Release preparation failed:' );
		console.error( err.message );

		if ( ! dryRun ) {
			warn( 'You may need to clean up manually:' );
			info(
				'  git flow release delete <version>  # if release branch exists'
			);
			info(
				'  git checkout develop             # return to develop branch'
			);
		}

		process.exit( 1 );
	}
}

// Handle Ctrl+C gracefully
process.on( 'SIGINT', () => {
	console.log( '' );
	warn( 'Release preparation interrupted by user' );
	process.exit( 130 );
} );

// Run the script
if ( require.main === module ) {
	main();
}
