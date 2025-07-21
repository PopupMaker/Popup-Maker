#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );
const {
	scanPackageImports,
	normalizePopupMakerImport,
	sortObjectKeys,
} = require( './dependency-analyzer' );

/**
 * Validates a single package's dependency tree
 *
 * @param {string} packageDir - The path to the package to validate
 * @return {Object} An object containing the validation results
 */
function validatePackage( packageDir ) {
	const result = scanPackageImports( packageDir );

	if ( result.error ) {
		return result;
	}

	const {
		packageName,
		packageJsonPath,
		packageJson,
		allImports,
		popupMakerImports,
		importsByFile,
	} = result;

	// Check package.json dependencies
	const allDeps = {
		...( packageJson.dependencies || {} ),
		...( packageJson.devDependencies || {} ),
		...( packageJson.peerDependencies || {} ),
	};

	const missingFromPackageJson = [];
	const extraInPackageJson = [];

	allImports.forEach( ( imp ) => {
		if ( ! allDeps[ imp ] ) {
			missingFromPackageJson.push( imp );
		}
	} );

	Object.keys( allDeps ).forEach( ( dep ) => {
		if ( dep.startsWith( '@popup-maker/' ) && ! allImports.includes( dep ) ) {
			extraInPackageJson.push( dep );
		}
	} );

	// Check tsconfig references and paths (only for @popup-maker imports)
	const extraInTsconfig = [];
	let missingFromTsconfig = [];
	let tsconfigRefs = [];
	const extraInPaths = [];
	let missingFromPaths = [];
	let tsconfigPaths = [];

	const tsconfigPath = path.join( packageDir, 'tsconfig.json' );

	if ( fs.existsSync( tsconfigPath ) ) {
		const tsconfig = JSON.parse( fs.readFileSync( tsconfigPath, 'utf8' ) );

		// Check references
		tsconfigRefs = ( tsconfig.references || [] )
			.map( ( ref ) => {
				const refPath = path.resolve( packageDir, ref.path );
				const refPackageJson = path.join( refPath, 'package.json' );
				if ( fs.existsSync( refPackageJson ) ) {
					return JSON.parse(
						fs.readFileSync( refPackageJson, 'utf8' )
					).name;
				}
				return null;
			} )
			.filter( Boolean );

		// Only check @popup-maker imports for tsconfig references
		popupMakerImports.forEach( ( imp ) => {
			if ( ! tsconfigRefs.includes( imp ) ) {
				missingFromTsconfig.push( imp );
			}
		} );

		tsconfigRefs.forEach( ( ref ) => {
			if (
				ref.startsWith( '@popup-maker/' ) &&
				! popupMakerImports.includes( ref )
			) {
				extraInTsconfig.push( ref );
			}
		} );

		// Check paths
		const paths = tsconfig.compilerOptions?.paths || {};
		tsconfigPaths = Object.keys( paths ).filter( ( p ) =>
			p.startsWith( '@popup-maker/' )
		);

		// Only check @popup-maker imports for tsconfig paths
		popupMakerImports.forEach( ( imp ) => {
			if ( ! tsconfigPaths.includes( imp ) ) {
				missingFromPaths.push( imp );
			}
		} );

		tsconfigPaths.forEach( ( pathKey ) => {
			if ( ! popupMakerImports.includes( pathKey ) ) {
				extraInPaths.push( pathKey );
			}
		} );
	} else {
		missingFromTsconfig = [ ...popupMakerImports ];
		missingFromPaths = [ ...popupMakerImports ];
	}

	return {
		packageName,
		imports: allImports,
		popupMakerImports,
		importsByFile,
		packageJsonPath,
		tsconfigPath,
		packageJson: {
			missing: missingFromPackageJson,
			extra: extraInPackageJson,
		},
		tsconfig: {
			missing: missingFromTsconfig,
			extra: extraInTsconfig,
			current: tsconfigRefs,
		},
		paths: {
			missing: missingFromPaths,
			extra: extraInPaths,
			current: tsconfigPaths,
		},
		hasIssues:
			missingFromPackageJson.length > 0 ||
			extraInPackageJson.length > 0 ||
			missingFromTsconfig.length > 0 ||
			extraInTsconfig.length > 0 ||
			missingFromPaths.length > 0 ||
			extraInPaths.length > 0,
	};
}

/**
 * Fixes missing dependencies in package.json
 *
 * @param {string}   packageJsonPath - Path to package.json
 * @param {string[]} missingDeps     - Array of missing dependency names
 * @return {boolean} True if changes were made
 */
function fixPackageJson( packageJsonPath, missingDeps ) {
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
 * Fixes missing references in tsconfig.json
 *
 * @param {string}   tsconfigPath - Path to tsconfig.json
 * @param {string[]} missingRefs  - Array of missing reference names
 * @return {boolean} True if changes were made
 */
function fixTsconfig( tsconfigPath, missingRefs ) {
	const tsconfig = JSON.parse( fs.readFileSync( tsconfigPath, 'utf8' ) );

	if ( ! tsconfig.references ) {
		tsconfig.references = [];
	}

	let changed = false;
	
	// Add missing references
	missingRefs.forEach( ( refName ) => {
		// Convert package name to relative path
		const packageName = refName.replace( '@popup-maker/', '' );

		// Check if reference already exists
		const existingRef = tsconfig.references.find(
			( ref ) => ref.path === `../${ packageName }`
		);

		if ( ! existingRef ) {
			tsconfig.references.push( { path: `../${ packageName }` } );
			changed = true;
		}
	} );

	// Always sort references alphabetically by path if references exist
	if ( tsconfig.references && tsconfig.references.length > 0 ) {
		const originalOrder = tsconfig.references.map( ref => ref.path );
		tsconfig.references.sort( ( a, b ) => a.path.localeCompare( b.path ) );
		const newOrder = tsconfig.references.map( ref => ref.path );
		
		// Check if the order changed
		if ( JSON.stringify( originalOrder ) !== JSON.stringify( newOrder ) ) {
			changed = true;
		}
	}

	if ( changed ) {
		fs.writeFileSync(
			tsconfigPath,
			JSON.stringify( tsconfig, null, '\t' ) + '\n'
		);
	}

	return changed;
}

/**
 * Fixes missing paths in tsconfig.json
 *
 * @param {string}   tsconfigPath - Path to tsconfig.json
 * @param {string[]} missingPaths - Array of missing path names
 * @return {boolean} True if changes were made
 */
function fixTsconfigPaths( tsconfigPath, missingPaths ) {
	const tsconfig = JSON.parse( fs.readFileSync( tsconfigPath, 'utf8' ) );

	if ( ! tsconfig.compilerOptions ) {
		tsconfig.compilerOptions = {};
	}

	if ( ! tsconfig.compilerOptions.paths ) {
		tsconfig.compilerOptions.paths = {};
	}

	let changed = false;
	
	// Add missing paths
	missingPaths.forEach( ( pathName ) => {
		// Convert package name to relative path
		const packageName = pathName.replace( '@popup-maker/', '' );
		const srcPath = `../${ packageName }/src`;

		if ( ! tsconfig.compilerOptions.paths[ pathName ] ) {
			tsconfig.compilerOptions.paths[ pathName ] = [ srcPath ];
			changed = true;
		}
	} );

	// Always sort paths alphabetically by key if paths exist
	if ( tsconfig.compilerOptions.paths && Object.keys( tsconfig.compilerOptions.paths ).length > 0 ) {
		const currentKeys = Object.keys( tsconfig.compilerOptions.paths );
		const sortedKeys = [ ...currentKeys ].sort();
		
		// Check if the order changed
		if ( JSON.stringify( currentKeys ) !== JSON.stringify( sortedKeys ) ) {
			tsconfig.compilerOptions.paths = sortObjectKeys( tsconfig.compilerOptions.paths );
			changed = true;
		}
	}

	if ( changed ) {
		fs.writeFileSync(
			tsconfigPath,
			JSON.stringify( tsconfig, null, '\t' ) + '\n'
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
Usage: node bin/validate-dep-tree.js [packages-dir] [options]

Options:
  --fix        Automatically fix missing dependencies and tsconfig references/paths
  --help       Show this help message

Examples:
  npm run validate:dep-tree                 # Validate only
  npm run validate:dep-tree -- --fix       # Auto-fix missing dependencies

What it validates:
  • package.json dependencies for ALL imports (JS/TS + SCSS)
  • tsconfig.json references for @popup-maker packages
  • tsconfig.json paths for TypeScript path mapping
  • Handles subpath imports correctly (e.g., @popup-maker/pkg/sub -> @popup-maker/pkg)
  • Detects SCSS @import "~package" statements
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

	console.log( `🔍 Validating dependency trees for ${ packages.length } packages...\n` );

	let hasAnyIssues = false;
	const isFixMode = args.includes( '--fix' );

	packages.forEach( ( pkg ) => {
		const packageDir = path.join( packagesDir, pkg );
		const result = validatePackage( packageDir );

		if ( result.error ) {
			console.log( `❌ ${ pkg }: ${ result.error }` );
			return;
		}

		if ( ! result.hasIssues ) {
			// Even if no issues, try to sort existing paths in fix mode
			if ( isFixMode && fs.existsSync( result.tsconfigPath ) ) {
				const changed = fixTsconfigPaths( result.tsconfigPath, [] );
				if ( changed ) {
					console.log( `🔤 ${ result.packageName }: Sorted tsconfig.json paths alphabetically` );
				} else {
					console.log(
						`✅ ${ result.packageName }: All dependencies properly configured`
					);
				}
			} else {
				console.log(
					`✅ ${ result.packageName }: All dependencies properly configured`
				);
			}
			return;
		}

		hasAnyIssues = true;
		console.log( `⚠️  ${ result.packageName }:` );

		if ( result.imports.length > 0 ) {
			console.log(
				`   📦 Found imports: ${ result.imports.join( ', ' ) }`
			);
		}

		if ( result.packageJson.missing.length > 0 ) {
			console.log(
				`   ❌ Missing from ${
					result.packageJsonPath
				}: ${ result.packageJson.missing.join( ', ' ) }`
			);

			if ( isFixMode ) {
				const changed = fixPackageJson(
					result.packageJsonPath,
					result.packageJson.missing
				);
				if ( changed ) {
					console.log( `   ✅ Added to package.json` );
				}
			}
		}

		if ( result.packageJson.extra.length > 0 ) {
			console.log(
				`   ⚠️  Extra in ${
					result.packageJsonPath
				}: ${ result.packageJson.extra.join( ', ' ) }`
			);
		}

		if ( result.tsconfig.missing.length > 0 ) {
			console.log(
				`   ❌ Missing from ${
					result.tsconfigPath
				}: ${ result.tsconfig.missing.join( ', ' ) }`
			);

			if ( isFixMode ) {
				const changed = fixTsconfig(
					result.tsconfigPath,
					result.tsconfig.missing
				);
				if ( changed ) {
					console.log( `   ✅ Added to tsconfig.json` );
				}
			}
		} else if ( isFixMode && fs.existsSync( result.tsconfigPath ) ) {
			// Even if no missing references, try to sort existing references
			const changed = fixTsconfig( result.tsconfigPath, [] );
			if ( changed ) {
				console.log( `   🔤 Sorted tsconfig.json references alphabetically` );
			}
		}

		if ( result.tsconfig.extra.length > 0 ) {
			console.log(
				`   ⚠️  Extra in ${
					result.tsconfigPath
				}: ${ result.tsconfig.extra.join( ', ' ) }`
			);
		}

		if ( result.paths.missing.length > 0 ) {
			console.log(
				`   ❌ Missing from ${
					result.tsconfigPath
				} paths: ${ result.paths.missing.join( ', ' ) }`
			);

			if ( isFixMode ) {
				const changed = fixTsconfigPaths(
					result.tsconfigPath,
					result.paths.missing
				);
				if ( changed ) {
					console.log( `   ✅ Added to tsconfig.json paths` );
				}
			}
		} else if ( isFixMode && fs.existsSync( result.tsconfigPath ) ) {
			// Even if no missing paths, try to sort existing paths
			const changed = fixTsconfigPaths( result.tsconfigPath, [] );
			if ( changed ) {
				console.log( `   🔤 Sorted tsconfig.json paths alphabetically` );
			}
		}

		if ( result.paths.extra.length > 0 ) {
			console.log(
				`   ⚠️  Extra in ${
					result.tsconfigPath
				} paths: ${ result.paths.extra.join( ', ' ) }`
			);
		}

		console.log();
	} );

	if ( hasAnyIssues ) {
		console.log(
			'❌ Found dependency tree issues. Run with --fix to auto-fix some issues.'
		);
		process.exit( 1 );
	} else {
		console.log( '✅ All packages have properly configured dependency trees!' );
	}
}

if ( require.main === module ) {
	main();
}

module.exports = { validatePackage };

/* eslint-enable no-console */