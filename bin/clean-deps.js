#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );
const {
	scanPackageImports,
	isTypesPackageUsed,
	sortObjectKeys,
} = require( './dependency-analyzer' );

/**
 * Analyzes a single package for all dependency issues
 *
 * @param {string} packageDir - The path to the package to analyze
 * @return {Object} An object containing the analysis results
 */
function analyzePackage( packageDir ) {
	const result = scanPackageImports( packageDir );

	if ( result.error ) {
		return result;
	}

	const {
		packageName,
		packageJsonPath,
		packageJson,
		allImports,
		importsByFile,
	} = result;

	// Get all declared dependencies
	const allDeclaredDeps = {
		...( packageJson.dependencies || {} ),
		...( packageJson.devDependencies || {} ),
		...( packageJson.peerDependencies || {} ),
	};

	// Find missing dependencies (used but not declared)
	const missingDeps = [];
	allImports.forEach( ( imp ) => {
		if ( ! allDeclaredDeps[ imp ] ) {
			missingDeps.push( imp );
		}
	} );

	// Find unused dependencies (declared but not used)
	const unusedDeps = [];
	const allImportsSet = new Set( allImports );

	Object.keys( allDeclaredDeps ).forEach( ( dep ) => {
		if ( ! allImportsSet.has( dep ) ) {
			// Check if this is a @types/* package and its base package is used
			if ( isTypesPackageUsed( dep, allImportsSet ) ) {
				return; // Skip adding to unusedDeps
			}
			unusedDeps.push( dep );
		}
	} );

	return {
		packageName,
		packageJsonPath,
		usedImports: allImports,
		importsByFile,
		missingDeps,
		unusedDeps,
		hasIssues: missingDeps.length > 0 || unusedDeps.length > 0,
	};
}

/**
 * Removes unused dependencies from package.json
 *
 * @param {string}   packageJsonPath - Path to package.json
 * @param {string[]} unusedDeps     - Array of unused dependency names
 * @return {boolean} True if changes were made
 */
function removeUnusedDeps( packageJsonPath, unusedDeps ) {
	if ( unusedDeps.length === 0 ) {
		return false;
	}

	const packageJson = JSON.parse(
		fs.readFileSync( packageJsonPath, 'utf8' )
	);
	let changed = false;

	// Only remove from dependencies and peerDependencies, not devDependencies
	[ 'dependencies', 'peerDependencies' ].forEach( ( depType ) => {
		if ( packageJson[ depType ] ) {
			unusedDeps.forEach( ( dep ) => {
				if ( packageJson[ depType ][ dep ] ) {
					delete packageJson[ depType ][ dep ];
					changed = true;
				}
			} );
		}
	} );

	// Sort all dependency types alphabetically
	if ( changed ) {
		[ 'dependencies', 'devDependencies', 'peerDependencies' ].forEach(
			( depType ) => {
				if ( packageJson[ depType ] ) {
					packageJson[ depType ] = sortObjectKeys( packageJson[ depType ] );
				}
			}
		);
	}

	if ( changed ) {
		fs.writeFileSync(
			packageJsonPath,
			JSON.stringify( packageJson, null, '\t' ) + '\n'
		);
	}

	return changed;
}

/**
 * Adds missing dependencies to package.json
 *
 * @param {string}   packageJsonPath - Path to package.json
 * @param {string[]} missingDeps     - Array of missing dependency names
 * @return {boolean} True if changes were made
 */
function addMissingDeps( packageJsonPath, missingDeps ) {
	if ( missingDeps.length === 0 ) {
		return false;
	}

	const packageJson = JSON.parse(
		fs.readFileSync( packageJsonPath, 'utf8' )
	);

	if ( ! packageJson.dependencies ) {
		packageJson.dependencies = {};
	}

	let changed = false;
	missingDeps.forEach( ( dep ) => {
		if ( ! packageJson.dependencies[ dep ] ) {
			packageJson.dependencies[ dep ] = '*';
			changed = true;
		}
	} );

	// Sort dependencies alphabetically
	if ( changed && packageJson.dependencies ) {
		packageJson.dependencies = sortObjectKeys( packageJson.dependencies );
	}

	if ( changed ) {
		fs.writeFileSync(
			packageJsonPath,
			JSON.stringify( packageJson, null, '\t' ) + '\n'
		);
	}

	return changed;
}

/**
 * Main function
 *
 * @return {void}
 */
function main() {
	const args = process.argv.slice( 2 );
	const packagesDir =
		args.find( ( arg ) => ! arg.startsWith( '--' ) ) || 'packages';

	if ( args.includes( '--help' ) ) {
		console.log(
			`
Usage: node bin/clean-deps.js [packages-dir] [options]

Options:
  --dry-run         Show what would be changed without making changes (default)
  --auto            Automatically fix both missing and unused dependencies
  --add-missing     Add missing dependencies to package.json
  --remove-unused   Remove unused dependencies from package.json (excludes devDependencies)
  --help            Show this help message

Examples:
  npm run clean:deps                      # Dry run mode (default)
  npm run clean:deps -- --auto            # Fix both missing and unused
  npm run clean:deps -- --add-missing     # Only add missing deps
  npm run clean:deps -- --remove-unused   # Only remove unused deps

What it analyzes:
  • All JavaScript/TypeScript files in each package
  • All SCSS files for @import "~package" statements
  • import statements, require() calls, dynamic imports
  • All types of dependencies (dependencies, devDependencies, peerDependencies)
  • Finds both missing (used but not declared) and unused (declared but not used)
  • Note: devDependencies are only added when missing, never removed when unused
  • Handles @types/* packages intelligently (keeps them if base package is used)
		`.trim()
		);
		return;
	}

	if ( ! fs.existsSync( packagesDir ) ) {
		console.error( `❌ Packages directory not found: ${ packagesDir }` );
		process.exit( 1 );
	}

	const packages = fs
		.readdirSync( packagesDir )
		.filter( ( dir ) =>
			fs.statSync( path.join( packagesDir, dir ) ).isDirectory()
		)
		.filter( ( dir ) =>
			fs.existsSync( path.join( packagesDir, dir, 'package.json' ) )
		);

	console.log(
		`🔍 Scanning ${ packages.length } packages for dependency issues...\n`
	);

	const isDryRun = args.includes( '--dry-run' );
	const isAutoMode = args.includes( '--auto' );
	const fixMissing = args.includes( '--add-missing' );
	const removeUnused = args.includes( '--remove-unused' );

	const actuallyDryRun =
		isDryRun || ( ! isAutoMode && ! fixMissing && ! removeUnused );

	if ( actuallyDryRun ) {
		console.log( '🔬 DRY RUN MODE - No changes will be made\n' );
	}

	let hasAnyIssues = false;
	let totalFixed = 0;

	packages.forEach( ( pkg ) => {
		const packageDir = path.join( packagesDir, pkg );
		const result = analyzePackage( packageDir );

		if ( result.error ) {
			console.log( `❌ ${ pkg }: ${ result.error }` );
			return;
		}

		if ( ! result.hasIssues ) {
			console.log(
				`✅ ${ result.packageName }: All dependencies properly declared`
			);
			return;
		}

		hasAnyIssues = true;
		console.log( `⚠️  ${ result.packageName }:` );

		if ( result.usedImports.length > 0 ) {
			console.log(
				`   📦 Found ${ result.usedImports.length } unique imports (JS/TS + SCSS)`
			);
		}

		if ( result.missingDeps.length > 0 ) {
			console.log(
				`   ❌ Missing from ${
					result.packageJsonPath
				}: ${ result.missingDeps.join( ', ' ) }`
			);

			if ( ( isAutoMode || fixMissing ) && ! actuallyDryRun ) {
				const changed = addMissingDeps(
					result.packageJsonPath,
					result.missingDeps
				);
				if ( changed ) {
					console.log(
						`   ✅ Added ${ result.missingDeps.length } missing dependencies`
					);
					totalFixed += result.missingDeps.length;
				}
			}
		}

		if ( result.unusedDeps.length > 0 ) {
			console.log(
				`   🗑️  Unused in ${
					result.packageJsonPath
				}: ${ result.unusedDeps.join( ', ' ) }`
			);

			if ( ( isAutoMode || removeUnused ) && ! actuallyDryRun ) {
				const changed = removeUnusedDeps(
					result.packageJsonPath,
					result.unusedDeps
				);
				if ( changed ) {
					console.log(
						`   ✂️  Removed ${ result.unusedDeps.length } unused dependencies`
					);
					totalFixed += result.unusedDeps.length;
				}
			}
		}

		console.log();
	} );

	if ( hasAnyIssues ) {
		if ( ! actuallyDryRun ) {
			console.log( `✨ Fixed ${ totalFixed } dependency issues!` );
		} else {
			console.log(
				'🧹 Found dependency issues. Use --auto to fix them automatically.'
			);
			console.log(
				'   Or use --add-missing / --remove-unused for specific actions.'
			);
		}
		if ( actuallyDryRun ) {
			process.exit( 1 );
		}
	} else {
		console.log( '✅ No dependency issues found in any package!' );
	}
}

if ( require.main === module ) {
	main();
}

module.exports = { analyzePackage };

/* eslint-enable no-console */