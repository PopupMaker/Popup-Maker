#!/usr/bin/env node
/* eslint-disable no-console */

/**
 * Two-stage release tool for Popup Maker
 *
 * Stage 1 - Prepare release:
 *   node bin/prepare-release.js start [version] [options]
 *
 * Stage 2 - Finish release:
 *   node bin/prepare-release.js finish [options]
 *
 * Flags:
 *   --major, --minor, --patch    Version increment type (start only)
 *   --skip-tests                 Bypass CI checks (start only)
 *   --skip-build                 Skip build step (start only)
 *   --test                       Create test tag (finish only)
 *   --auto                       Skip confirmations
 *   --dry-run                    Show what would happen without changes
 */

const fs = require( 'fs' );
const { execSync } = require( 'child_process' );
const minimist = require( 'minimist' );
const readline = require( 'readline' );

const argv = minimist( process.argv.slice( 2 ) );
const command = argv._[ 0 ] || 'auto';
const dryRun = argv[ 'dry-run' ];
const autoMode = argv.auto;

// Colors for console output.
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

// Show help.
if ( argv.help || argv.h ) {
	console.log( `
${ colorize( 'bold', '🚀 Popup Maker Release Tool' ) }

${ colorize( 'cyan', 'USAGE:' ) }
  node bin/prepare-release.js start [version] [options]
  node bin/prepare-release.js finish [options]

${ colorize( 'cyan', 'STAGE 1 - START:' ) }
  Prepare release on new branch, run CI checks, build, and commit.

  node bin/prepare-release.js start                     # Patch increment
  node bin/prepare-release.js start --minor             # Minor increment
  node bin/prepare-release.js start --major             # Major increment
  node bin/prepare-release.js start 1.2.3               # Specific version
  node bin/prepare-release.js start --skip-tests        # Skip CI
  node bin/prepare-release.js start --skip-build        # Skip build
  node bin/prepare-release.js start --auto              # No prompts
  node bin/prepare-release.js start --dry-run           # Preview only

${ colorize( 'cyan', 'STAGE 2 - FINISH:' ) }
  Merge to master, tag, merge back to develop, push.

  node bin/prepare-release.js finish                    # Create stable tag
  node bin/prepare-release.js finish --test             # Create -test tag
  node bin/prepare-release.js finish --auto             # No prompts
  node bin/prepare-release.js finish --dry-run          # Preview only

${ colorize( 'cyan', 'AUTO MODE:' ) }
  If no subcommand given, auto-detects:
  - On release/* branch → runs finish
  - On develop branch   → runs start
  - Otherwise           → shows help

${ colorize( 'cyan', 'WORKFLOW:' ) }
  1. npm run prepare-release start         # Prepare on release branch
  2. [Review zip in release/ folder]
  3. npm run prepare-release finish        # Merge, tag, push
` );
	process.exit( 0 );
}

// Check if we're in the right directory.
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

// Get current version from package.json.
function getCurrentVersion() {
	const packageJson = JSON.parse( fs.readFileSync( 'package.json', 'utf8' ) );
	return packageJson.version;
}

// Calculate next version based on current version and increment type.
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

// Determine version to use.
function getTargetVersion() {
	if ( argv._[ 1 ] ) {
		const version = argv._[ 1 ];
		if ( ! /^\d+\.\d+\.\d+$/.test( version ) ) {
			error( 'Invalid version format. Use semver: X.Y.Z' );
			process.exit( 1 );
		}
		return version;
	}

	const currentVersion = getCurrentVersion();

	// Determine increment type.
	let incrementType = 'patch'; // default.
	if ( argv.major ) {
		incrementType = 'major';
	} else if ( argv.minor ) {
		incrementType = 'minor';
	} else if ( argv.patch ) {
		incrementType = 'patch';
	}

	return calculateNextVersion( currentVersion, incrementType );
}

// Execute command and return output.
function execCommand( cmd, options = {} ) {
	if ( dryRun ) {
		info( `[DRY RUN] Would execute: ${ cmd }` );
		return '';
	}

	try {
		return execSync( cmd, {
			encoding: 'utf8',
			stdio: options.silent ? 'pipe' : 'inherit',
			...options,
		} );
	} catch ( err ) {
		if ( ! options.allowFailure ) {
			error( `Command failed: ${ cmd }` );
			error( err.message );
			process.exit( 1 );
		}
		return null;
	}
}

// Prompt user for confirmation.
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

// Get current branch.
function getCurrentBranch() {
	const branch = execCommand( 'git rev-parse --abbrev-ref HEAD', { silent: true } );
	return branch.trim();
}

// Check git status.
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

// Stage 1: START
async function stageStart( targetVersion ) {
	log(
		colorize( 'bold', '🚀 Starting Release Preparation' ),
		'magenta'
	);
	console.log( '' );

	checkProjectRoot();
	checkGitStatus();

	const currentBranch = getCurrentBranch();
	if ( currentBranch !== 'develop' ) {
		error( `Must be on develop branch. Currently on: ${ currentBranch }` );
		process.exit( 1 );
	}

	const currentVersion = getCurrentVersion();

	log( `Current version: ${ currentVersion }`, 'yellow' );
	log( `Target version:  ${ targetVersion }`, 'green' );
	console.log( '' );

	if ( dryRun ) {
		warn( 'DRY RUN MODE - No changes will be made' );
		console.log( '' );
	}

	// Confirmation.
	const confirmed = await prompt(
		`Proceed with release ${ targetVersion }?`
	);
	if ( ! confirmed ) {
		info( 'Release cancelled by user' );
		process.exit( 0 );
	}

	console.log( '' );

	// Pre-flight: Run CI checks (unless --skip-tests).
	if ( ! argv[ 'skip-tests' ] ) {
		log( 'Running pre-flight CI checks', 'cyan' );

		const checks = [
			{ name: 'PHPCS Lint', cmd: 'composer run lint --quiet' },
			{ name: 'ESLint', cmd: 'npx eslint ./packages/**/src/*.ts* --no-ignore --quiet' },
			{ name: 'Unit Tests', cmd: 'npm run test:unit' },
			{ name: 'Security Audit', cmd: 'npm audit --audit-level=high' },
		];

		for ( const check of checks ) {
			log( `  • ${ check.name }...`, 'blue' );
			execCommand( check.cmd );
			success( `  ✓ ${ check.name }` );
		}

		console.log( '' );
	}

	// Create release branch.
	log( `Creating release branch: release/${ targetVersion }`, 'cyan' );
	execCommand( `git checkout -b release/${ targetVersion }` );
	success( `Release branch created` );
	console.log( '' );

	// Update versions.
	log( `Updating versions to ${ targetVersion }`, 'cyan' );
	execCommand( `node bin/update-versions.js ${ targetVersion }` );
	success( 'Versions updated' );

	// Update changelog.
	log( `Updating changelog for ${ targetVersion }`, 'cyan' );
	execCommand( `node bin/update-changelog.js ${ targetVersion }` );
	success( 'Changelog updated' );

	// Update package-lock.json.
	log( 'Updating package-lock.json', 'cyan' );
	execCommand( 'npm install --package-lock-only' );
	success( 'Package lock updated' );

	// Build release (unless --skip-build).
	if ( ! argv[ 'skip-build' ] ) {
		log( 'Building release assets', 'cyan' );
		execCommand( 'npm run build:production' );
		execCommand( 'node bin/build-release.js' );
		success( 'Release assets built' );
	} else {
		warn( 'Skipping build step (--skip-build flag)' );
	}

	console.log( '' );

	// Commit changes.
	log( 'Adding and committing changes', 'cyan' );
	execCommand( 'git add -A' );
	execCommand( `git commit -m "chore: prepare release ${ targetVersion }"` );
	success( `Committed release preparation` );

	console.log( '' );
	success( `✅ Release ${ targetVersion } prepared on branch release/${ targetVersion }` );
	console.log( '' );
	log( 'Next steps:', 'cyan' );
	info( '  • Inspect the release zip in release/' );
	info( '  • Update readme.txt if needed' );
	info( '  • Commit any additional changes' );
	info( `  • Ship it:  npm run prepare-release finish` );
	info( `  • Test it:  npm run prepare-release finish -- --test` );
}

// Stage 2: FINISH
async function stageFinish() {
	log(
		colorize( 'bold', '🚀 Finishing Release' ),
		'magenta'
	);
	console.log( '' );

	checkProjectRoot();
	checkGitStatus();

	const currentBranch = getCurrentBranch();
	const releaseMatch = currentBranch.match( /^release\/(.+)$/ );

	if ( ! releaseMatch ) {
		error( `Must be on release/* branch. Currently on: ${ currentBranch }` );
		process.exit( 1 );
	}

	const version = releaseMatch[ 1 ];
	const isTest = argv.test;
	const tagSuffix = isTest ? '-test' : '';
	const tag = `${ version }${ tagSuffix }`;

	log( `Version: ${ version }`, 'yellow' );
	log( `Tag:     ${ tag }`, 'green' );
	console.log( '' );

	if ( dryRun ) {
		warn( 'DRY RUN MODE - No changes will be made' );
		console.log( '' );
	}

	// Confirmation.
	const confirmed = await prompt( `Create ${ isTest ? 'test' : 'stable' } release ${ tag }?` );
	if ( ! confirmed ) {
		info( 'Release cancelled by user' );
		process.exit( 0 );
	}

	console.log( '' );

	if ( isTest ) {
		// Test mode: tag from release branch, push tag only. No merge.
		log( `Creating test tag from release branch: ${ tag }`, 'cyan' );
		execCommand( `git tag ${ tag }` );
		success( `Tag created: ${ tag }` );

		log( 'Pushing test tag to remote', 'cyan' );
		execCommand( `git push origin ${ tag }` );
		success( 'Test tag pushed' );
	} else {
		// Stable release: full merge flow.
		log( 'Merging to master branch', 'cyan' );
		execCommand( 'git checkout master' );
		execCommand( `git merge --no-ff release/${ version } -m "Merge release ${ version }"` );
		success( 'Merged to master' );

		// Create tag on master.
		log( `Creating tag: ${ tag }`, 'cyan' );
		execCommand( `git tag ${ tag }` );
		success( `Tag created: ${ tag }` );

		// Merge back to develop.
		log( 'Merging back to develop branch', 'cyan' );
		execCommand( 'git checkout develop' );
		execCommand( `git merge --no-ff master -m "Merge release ${ version } back to develop"` );
		success( 'Merged back to develop' );

		// Delete release branch.
		log( `Deleting release branch: release/${ version }`, 'cyan' );
		execCommand( `git branch -d release/${ version }` );
		success( 'Release branch deleted' );

		// Push everything.
		log( 'Pushing to remote', 'cyan' );
		execCommand( 'git push origin master develop --tags' );
		success( 'Pushed to remote' );
	}

	console.log( '' );
	if ( isTest ) {
		log( `🧪 Test tag ${ tag } pushed. GitHub Actions will dry-run the release pipeline.`, 'cyan' );
		console.log( '' );
		info( 'Next steps:' );
		info( '  1. Watch GitHub Actions for pipeline results' );
		info( '  2. Verify draft release, SVN dry-run, Slack notification' );
		info( '  3. Clean up test tag: git tag -d ' + tag + ' && git push origin :refs/tags/' + tag );
		info( '  4. Ship for real: npm run prepare-release finish' );
	} else {
		log( `🚀 Release ${ tag } shipped! GitHub Actions will handle the rest.`, 'cyan' );
	}
}

// Auto-detect command based on branch.
async function autoDetect() {
	checkProjectRoot();

	const currentBranch = getCurrentBranch();

	if ( currentBranch.startsWith( 'release/' ) ) {
		// On release branch → finish.
		await stageFinish();
	} else if ( currentBranch === 'develop' ) {
		// On develop → start.
		const targetVersion = getTargetVersion();
		await stageStart( targetVersion );
	} else {
		error( 'Unknown state. Please specify start or finish:' );
		info( '  npm run prepare-release start    # Prepare new release' );
		info( '  npm run prepare-release finish   # Finish release' );
		process.exit( 1 );
	}
}

// Main execution.
async function main() {
	try {
		if ( command === 'start' ) {
			const targetVersion = getTargetVersion();
			await stageStart( targetVersion );
		} else if ( command === 'finish' ) {
			await stageFinish();
		} else if ( command === 'auto' ) {
			await autoDetect();
		} else {
			error( `Unknown command: ${ command }` );
			info( 'Use --help for usage information' );
			process.exit( 1 );
		}
	} catch ( err ) {
		console.log( '' );
		error( 'Release process failed:' );
		console.error( err.message );
		process.exit( 1 );
	}
}

// Handle Ctrl+C gracefully.
process.on( 'SIGINT', () => {
	console.log( '' );
	warn( 'Release process interrupted by user' );
	process.exit( 130 );
} );

// Run the script.
if ( require.main === module ) {
	main();
}
