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
 * Cache for dependency versions across all packages
 */
let dependencyVersionCache = null;

/**
 * Scans all packages in multiple locations to build a version cache
 *
 * @param {string} currentPackageDir - Current package directory to determine context
 * @return {Object} Map of dependency name to most common version
 */
function buildDependencyVersionCache( currentPackageDir ) {
	if ( dependencyVersionCache ) {
		return dependencyVersionCache;
	}

	const versionCounts = {}; // dep -> { version -> count }
	const packagePaths = [];

	// Get the current plugin context to know which paths to scan
	const pathInfo = getPluginPathInfo( currentPackageDir );
	const absolutePath = path.resolve( currentPackageDir );

	// Always scan core packages if accessible
	if ( pathInfo.coreRelativePath ) {
		const corePackagesPath = path.resolve(
			currentPackageDir,
			pathInfo.coreRelativePath
		);
		if ( fs.existsSync( corePackagesPath ) ) {
			packagePaths.push( corePackagesPath );
		}
	}

	// Scan pro packages if accessible
	if ( pathInfo.proRelativePath ) {
		const proPackagesPath = path.resolve(
			currentPackageDir,
			pathInfo.proRelativePath
		);
		if ( fs.existsSync( proPackagesPath ) ) {
			packagePaths.push( proPackagesPath );
		}
	}

	// Scan LMS/addon packages if accessible
	if ( pathInfo.lmsRelativePath ) {
		const lmsPackagesPath = path.resolve(
			currentPackageDir,
			pathInfo.lmsRelativePath
		);
		if ( fs.existsSync( lmsPackagesPath ) ) {
			packagePaths.push( lmsPackagesPath );
		}
	}

	// Scan all discovered package directories
	packagePaths.forEach( ( packagesPath ) => {
		if ( ! fs.existsSync( packagesPath ) ) return;

		const packages = fs
			.readdirSync( packagesPath )
			.filter( ( dir ) =>
				fs.statSync( path.join( packagesPath, dir ) ).isDirectory()
			)
			.filter( ( dir ) =>
				fs.existsSync( path.join( packagesPath, dir, 'package.json' ) )
			);

		packages.forEach( ( pkg ) => {
			const packageJsonPath = path.join(
				packagesPath,
				pkg,
				'package.json'
			);
			try {
				const packageJson = JSON.parse(
					fs.readFileSync( packageJsonPath, 'utf8' )
				);
				const allDeps = {
					...( packageJson.dependencies || {} ),
					...( packageJson.devDependencies || {} ),
					...( packageJson.peerDependencies || {} ),
				};

				Object.entries( allDeps ).forEach( ( [ depName, version ] ) => {
					// Skip file: dependencies and popup-maker packages
					if (
						version.startsWith( 'file:' ) ||
						depName.startsWith( '@popup-maker' )
					) {
						return;
					}

					if ( ! versionCounts[ depName ] ) {
						versionCounts[ depName ] = {};
					}

					versionCounts[ depName ][ version ] =
						( versionCounts[ depName ][ version ] || 0 ) + 1;
				} );
			} catch ( error ) {
				// Skip packages with invalid JSON
			}
		} );
	} );

	// Build cache with most common version for each dependency
	dependencyVersionCache = {};
	Object.entries( versionCounts ).forEach( ( [ depName, versions ] ) => {
		const sortedVersions = Object.entries( versions ).sort(
			( a, b ) => b[ 1 ] - a[ 1 ]
		);
		if ( sortedVersions.length > 0 ) {
			dependencyVersionCache[ depName ] = sortedVersions[ 0 ][ 0 ]; // Most common version
		}
	} );

	return dependencyVersionCache;
}

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
		if (
			dep.startsWith( '@popup-maker/' ) &&
			! allImports.includes( dep )
		) {
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
 * Gets the correct dependency reference for a package
 *
 * @param {string} depName    - The package name (e.g., '@popup-maker/components')
 * @param {Object} pathInfo   - Path information from getPluginPathInfo
 * @param {string} packageDir - Current package directory for version lookup
 * @param {string} [depType]  - The dependency type: 'dependencies', 'devDependencies', or 'peerDependencies'
 * @return {string} The dependency reference (file: path or version)
 */
function getDependencyReference(
	depName,
	pathInfo,
	packageDir,
	depType = 'dependencies'
) {
	const packageName = depName.replace(
		/^@popup-maker(-pro|-lms-popups)?\//,
		''
	);

	// Handle core packages (@popup-maker/*)
	if (
		depName.startsWith( '@popup-maker/' ) &&
		! depName.startsWith( '@popup-maker-' )
	) {
		if ( ! pathInfo.coreRelativePath ) return '*'; // Fallback
		const basePath =
			pathInfo.type === 'core'
				? `../${ packageName }`
				: `${ pathInfo.coreRelativePath }${ packageName }`;
		return `file:${ basePath }`;
	}

	// Handle pro packages (@popup-maker-pro/*)
	if ( depName.startsWith( '@popup-maker-pro/' ) ) {
		if ( ! pathInfo.proRelativePath ) return '*'; // Fallback
		return `file:${ pathInfo.proRelativePath }${ packageName }`;
	}

	// Handle LMS packages (@popup-maker-lms-popups/*)
	if ( depName.startsWith( '@popup-maker-lms-popups/' ) ) {
		if ( ! pathInfo.lmsRelativePath ) return '*'; // Fallback
		return `file:${ pathInfo.lmsRelativePath }${ packageName }`;
	}

	// Special handling for jQuery - always use peer dependency pattern
	if ( depName === 'jquery' && depType === 'peerDependencies' ) {
		const versionCache = buildDependencyVersionCache( packageDir );
		return versionCache[ depName ] || '^3.5.32'; // Default to common version
	}

	// For non-popup-maker packages, try to find existing version
	const versionCache = buildDependencyVersionCache( packageDir );
	return versionCache[ depName ] || '*';
}

/**
 * Fixes missing dependencies in package.json with special handling for jQuery
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

	// Initialize dependency sections if they don't exist
	if ( ! packageJson.dependencies ) {
		packageJson.dependencies = {};
	}
	if ( ! packageJson.devDependencies ) {
		packageJson.devDependencies = {};
	}
	if ( ! packageJson.peerDependencies ) {
		packageJson.peerDependencies = {};
	}

	let changed = false;

	// Get path info for current package
	const packageDir = path.dirname( packageJsonPath );
	const pathInfo = getPluginPathInfo( packageDir );

	missingDeps.forEach( ( dep ) => {
		// Special handling for jQuery
		if ( dep === 'jquery' ) {
			// Add @types/jquery to devDependencies if not present
			if ( ! packageJson.devDependencies[ '@types/jquery' ] ) {
				const jqueryTypesVersion = getDependencyReference(
					'@types/jquery',
					pathInfo,
					packageDir,
					'devDependencies'
				);
				packageJson.devDependencies[ '@types/jquery' ] =
					jqueryTypesVersion;
				changed = true;
			}

			// Add jquery to peerDependencies if not present
			if ( ! packageJson.peerDependencies[ 'jquery' ] ) {
				const jqueryVersion = getDependencyReference(
					'jquery',
					pathInfo,
					packageDir,
					'peerDependencies'
				);
				packageJson.peerDependencies[ 'jquery' ] = jqueryVersion;
				changed = true;
			}

			// Remove jquery from regular dependencies if present
			if ( packageJson.dependencies[ 'jquery' ] ) {
				delete packageJson.dependencies[ 'jquery' ];
				changed = true;
			}
		} else {
			// Handle other dependencies normally
			if ( ! packageJson.dependencies[ dep ] ) {
				const depReference = getDependencyReference(
					dep,
					pathInfo,
					packageDir
				);
				packageJson.dependencies[ dep ] = depReference;
				changed = true;
			}
		}
	} );

	// Sort all dependency sections alphabetically
	if ( changed ) {
		if ( Object.keys( packageJson.dependencies ).length > 0 ) {
			packageJson.dependencies = sortObjectKeys(
				packageJson.dependencies
			);
		} else {
			delete packageJson.dependencies; // Clean up empty dependencies
		}

		if ( Object.keys( packageJson.devDependencies ).length > 0 ) {
			packageJson.devDependencies = sortObjectKeys(
				packageJson.devDependencies
			);
		} else {
			delete packageJson.devDependencies; // Clean up empty devDependencies
		}

		if ( Object.keys( packageJson.peerDependencies ).length > 0 ) {
			packageJson.peerDependencies = sortObjectKeys(
				packageJson.peerDependencies
			);
		} else {
			delete packageJson.peerDependencies; // Clean up empty peerDependencies
		}
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
 * Determines the relative path structure based on the current plugin directory
 *
 * @param {string} packageDir - The current package directory
 * @return {Object} Object containing path information
 */
function getPluginPathInfo( packageDir ) {
	// Get absolute path and find which plugin we're in
	const absolutePath = path.resolve( packageDir );

	// Check if we're in core (popup-maker)
	if ( absolutePath.includes( '/plugins/popup-maker/packages/' ) ) {
		return {
			type: 'core',
			coreRelativePath: '../',
			proRelativePath: null, // Core doesn't reference pro
			lmsRelativePath: null, // Core doesn't reference LMS
		};
	}

	// Check if we're in pro
	if ( absolutePath.includes( '/plugins/popup-maker-pro/packages/' ) ) {
		return {
			type: 'pro',
			coreRelativePath: '../../../popup-maker/packages/',
			proRelativePath: '../',
			lmsRelativePath: null, // Pro doesn't reference LMS
		};
	}

	// Check if we're in LMS (or any future addon that extends pro)
	if (
		absolutePath.includes( '/plugins/popup-maker-lms-popups/packages/' ) ||
		absolutePath.match( /\/plugins\/popup-maker-[^/]+\/packages\// )
	) {
		return {
			type: 'addon',
			coreRelativePath: '../../../popup-maker/packages/',
			proRelativePath: '../../../popup-maker-pro/packages/',
			lmsRelativePath: '../', // Self-references within addon
		};
	}

	// Default fallback (shouldn't reach here)
	return {
		type: 'unknown',
		coreRelativePath: '../',
		proRelativePath: null,
		lmsRelativePath: null,
	};
}

/**
 * Gets the correct relative path for a package reference
 *
 * @param {string} refName    - The package name (e.g., '@popup-maker/components')
 * @param {Object} pathInfo   - Path information from getPluginPathInfo
 * @return {string|null} The relative path or null if reference shouldn't exist
 */
function getRelativePathForPackage( refName, pathInfo ) {
	const packageName = refName.replace(
		/^@popup-maker(-pro|-lms-popups)?\//,
		''
	);

	// Handle core packages (@popup-maker/*)
	if (
		refName.startsWith( '@popup-maker/' ) &&
		! refName.startsWith( '@popup-maker-' )
	) {
		if ( ! pathInfo.coreRelativePath ) return null;
		return `${ pathInfo.coreRelativePath }${ packageName }`;
	}

	// Handle pro packages (@popup-maker-pro/*)
	if ( refName.startsWith( '@popup-maker-pro/' ) ) {
		if ( ! pathInfo.proRelativePath ) return null;
		return `${ pathInfo.proRelativePath }${ packageName }`;
	}

	// Handle LMS packages (@popup-maker-lms-popups/*)
	if ( refName.startsWith( '@popup-maker-lms-popups/' ) ) {
		if ( ! pathInfo.lmsRelativePath ) return null;
		return `${ pathInfo.lmsRelativePath }${ packageName }`;
	}

	return null;
}

/**
 * Fixes missing references in tsconfig.json and adds jQuery types if needed
 *
 * @param {string}   tsconfigPath - Path to tsconfig.json
 * @param {string[]} missingRefs  - Array of missing reference names
 * @param {boolean}  hasJquery    - Whether the package imports jQuery
 * @return {boolean} True if changes were made
 */
function fixTsconfig( tsconfigPath, missingRefs, hasJquery = false ) {
	const tsconfig = JSON.parse( fs.readFileSync( tsconfigPath, 'utf8' ) );

	if ( ! tsconfig.references ) {
		tsconfig.references = [];
	}

	let changed = false;

	// Get path info for current package
	const packageDir = path.dirname( tsconfigPath );
	const pathInfo = getPluginPathInfo( packageDir );

	// Add missing references
	missingRefs.forEach( ( refName ) => {
		const relativePath = getRelativePathForPackage( refName, pathInfo );

		if ( ! relativePath ) {
			console.warn(
				`Warning: Cannot create reference for ${ refName } from ${ pathInfo.type } context`
			);
			return;
		}

		// Check if reference already exists
		const existingRef = tsconfig.references.find(
			( ref ) => ref.path === relativePath
		);

		if ( ! existingRef ) {
			tsconfig.references.push( { path: relativePath } );
			changed = true;
		}
	} );

	// Handle jQuery types
	if ( hasJquery ) {
		if ( ! tsconfig.types ) {
			tsconfig.types = [];
		}

		if ( ! tsconfig.types.includes( 'jquery' ) ) {
			tsconfig.types.push( 'jquery' );
			tsconfig.types.sort(); // Keep types sorted
			changed = true;
		}
	}

	// Always sort references alphabetically by path if references exist
	if ( tsconfig.references && tsconfig.references.length > 0 ) {
		const originalOrder = tsconfig.references.map( ( ref ) => ref.path );
		tsconfig.references.sort( ( a, b ) => a.path.localeCompare( b.path ) );
		const newOrder = tsconfig.references.map( ( ref ) => ref.path );

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
 * Gets the correct TypeScript path mapping for a package
 *
 * @param {string} pathName - The package name (e.g., '@popup-maker/components')
 * @param {Object} pathInfo - Path information from getPluginPathInfo
 * @return {string[]|null} The TypeScript path array or null if path shouldn't exist
 */
function getTypeScriptPathForPackage( pathName, pathInfo ) {
	const packageName = pathName.replace(
		/^@popup-maker(-pro|-lms-popups)?\//,
		''
	);

	// Handle core packages (@popup-maker/*)
	if (
		pathName.startsWith( '@popup-maker/' ) &&
		! pathName.startsWith( '@popup-maker-' )
	) {
		if ( ! pathInfo.coreRelativePath ) return null;
		const basePath =
			pathInfo.type === 'core' ? '../' : pathInfo.coreRelativePath;
		return [ `${ basePath }${ packageName }/build-types` ];
	}

	// Handle pro packages (@popup-maker-pro/*)
	if ( pathName.startsWith( '@popup-maker-pro/' ) ) {
		if ( ! pathInfo.proRelativePath ) return null;
		return [ `${ pathInfo.proRelativePath }${ packageName }/build-types` ];
	}

	// Handle LMS packages (@popup-maker-lms-popups/*)
	if ( pathName.startsWith( '@popup-maker-lms-popups/' ) ) {
		if ( ! pathInfo.lmsRelativePath ) return null;
		return [ `${ pathInfo.lmsRelativePath }${ packageName }/build-types` ];
	}

	return null;
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

	// Get path info for current package
	const packageDir = path.dirname( tsconfigPath );
	const pathInfo = getPluginPathInfo( packageDir );

	// Add missing paths
	missingPaths.forEach( ( pathName ) => {
		const tsPath = getTypeScriptPathForPackage( pathName, pathInfo );

		if ( ! tsPath ) {
			console.warn(
				`Warning: Cannot create TypeScript path for ${ pathName } from ${ pathInfo.type } context`
			);
			return;
		}

		if ( ! tsconfig.compilerOptions.paths[ pathName ] ) {
			tsconfig.compilerOptions.paths[ pathName ] = tsPath;
			changed = true;
		}
	} );

	// Always sort paths alphabetically by key if paths exist
	if (
		tsconfig.compilerOptions.paths &&
		Object.keys( tsconfig.compilerOptions.paths ).length > 0
	) {
		const currentKeys = Object.keys( tsconfig.compilerOptions.paths );
		const sortedKeys = [ ...currentKeys ].sort();

		// Check if the order changed
		if ( JSON.stringify( currentKeys ) !== JSON.stringify( sortedKeys ) ) {
			tsconfig.compilerOptions.paths = sortObjectKeys(
				tsconfig.compilerOptions.paths
			);
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

	console.log(
		`🔍 Validating dependency trees for ${ packages.length } packages...\n`
	);

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
					console.log(
						`🔤 ${ result.packageName }: Sorted tsconfig.json paths alphabetically`
					);
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

			if ( isFixMode && fs.existsSync( result.tsconfigPath ) ) {
				const hasJquery = result.imports.includes( 'jquery' );
				const changed = fixTsconfig(
					result.tsconfigPath,
					result.tsconfig.missing,
					hasJquery
				);
				if ( changed ) {
					console.log( `   ✅ Added to tsconfig.json` );
				}
			}
		} else if ( isFixMode && fs.existsSync( result.tsconfigPath ) ) {
			// Even if no missing references, try to sort existing references and handle jQuery
			const hasJquery = result.imports.includes( 'jquery' );
			const changed = fixTsconfig( result.tsconfigPath, [], hasJquery );
			if ( changed ) {
				console.log(
					`   🔤 Sorted tsconfig.json references alphabetically`
				);
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

			if ( isFixMode && fs.existsSync( result.tsconfigPath ) ) {
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
				console.log(
					`   🔤 Sorted tsconfig.json paths alphabetically`
				);
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
		console.log(
			'✅ All packages have properly configured dependency trees!'
		);
	}
}

if ( require.main === module ) {
	main();
}

module.exports = { validatePackage };

/* eslint-enable no-console */
