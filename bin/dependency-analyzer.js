#!/usr/bin/env node

const fs = require( 'fs' );
const path = require( 'path' );
const glob = require( 'glob' );

/**
 * Scans TypeScript/JavaScript files for all types of imports
 *
 * @param {string} filePath - The path to the file to scan
 * @return {string[]} An array of all imports
 */
function findJSImports( filePath ) {
	const content = fs.readFileSync( filePath, 'utf8' );
	const imports = new Set();

	// ES6 imports: import ... from 'package'
	const es6ImportRegex = /import\s+[^;]+\s+from\s+['"]([^'"]+)['"]/g;
	let match;
	while ( ( match = es6ImportRegex.exec( content ) ) !== null ) {
		imports.add( match[ 1 ] );
	}

	// Dynamic imports: import('package')
	const dynamicImportRegex = /import\s*\(\s*['"]([^'"]+)['"]\s*\)/g;
	while ( ( match = dynamicImportRegex.exec( content ) ) !== null ) {
		imports.add( match[ 1 ] );
	}

	// Require statements: require('package')
	const requireRegex = /require\s*\(\s*['"]([^'"]+)['"]\s*\)/g;
	while ( ( match = requireRegex.exec( content ) ) !== null ) {
		imports.add( match[ 1 ] );
	}

	return Array.from( imports );
}

/**
 * Scans SCSS files for @import statements
 *
 * @param {string} filePath - The path to the SCSS file to scan
 * @return {string[]} An array of normalized package imports
 */
function findScssImports( filePath ) {
	const content = fs.readFileSync( filePath, 'utf8' );
	// Match @import statements with ~ prefix (node_modules imports)
	const scssImportRegex = /@import\s+['"]~([^'"]+)['"]\s*;?/g;

	const imports = new Set();
	let match;

	while ( ( match = scssImportRegex.exec( content ) ) !== null ) {
		const importPath = match[ 1 ]; // Remove the ~ prefix
		
		// Normalize to package name (e.g., @wordpress/base-styles/variables -> @wordpress/base-styles)
		if ( importPath.startsWith( '@' ) ) {
			const parts = importPath.split( '/' );
			if ( parts.length >= 2 ) {
				imports.add( `${ parts[ 0 ] }/${ parts[ 1 ] }` );
			}
		} else {
			const parts = importPath.split( '/' );
			if ( parts.length > 0 ) {
				imports.add( parts[ 0 ] );
			}
		}
	}

	return Array.from( imports );
}

/**
 * Normalizes @popup-maker import paths to base package names
 *
 * @param {string} importPath - The import path to normalize
 * @return {string} The base package name
 */
function normalizePopupMakerImport( importPath ) {
	// @popup-maker/use-query-params/adapters/react-router-6 -> @popup-maker/use-query-params
	const parts = importPath.split( '/' );
	return parts.length >= 2 ? `${ parts[ 0 ] }/${ parts[ 1 ] }` : importPath;
}

/**
 * Normalizes import paths to package names
 *
 * @param {string} importPath - The import path to normalize
 * @return {string|null} The package name or null if not a package
 */
function normalizeToPackageName( importPath ) {
	// Skip relative imports
	if ( importPath.startsWith( '.' ) || importPath.startsWith( '/' ) ) {
		return null;
	}

	// Handle scoped packages: @scope/package/sub -> @scope/package
	if ( importPath.startsWith( '@' ) ) {
		const parts = importPath.split( '/' );
		return parts.length >= 2
			? `${ parts[ 0 ] }/${ parts[ 1 ] }`
			: importPath;
	}

	// Handle regular packages: package/sub -> package
	const parts = importPath.split( '/' );
	return parts[ 0 ];
}

/**
 * Scans a package directory for all imports (JS/TS and SCSS)
 *
 * @param {string} packageDir - The path to the package to scan
 * @return {Object} An object containing the scan results
 */
function scanPackageImports( packageDir ) {
	const packageJsonPath = path.join( packageDir, 'package.json' );

	if ( ! fs.existsSync( packageJsonPath ) ) {
		return { error: 'No package.json found' };
	}

	const packageJson = JSON.parse(
		fs.readFileSync( packageJsonPath, 'utf8' )
	);
	const packageName = packageJson.name;

	// Find all source files
	const jsFiles = glob.sync( '**/*.{js,jsx,ts,tsx,mjs,cjs}', {
		cwd: packageDir,
		ignore: [
			'node_modules/**',
			'build/**',
			'dist/**',
			'build-types/**',
			'vendor/**',
		],
	} );

	const scssFiles = glob.sync( '**/*.{scss,sass}', {
		cwd: packageDir,
		ignore: [
			'node_modules/**',
			'build/**',
			'dist/**',
			'build-types/**',
			'vendor/**',
		],
	} );

	// Collect all imports
	const allImports = new Set();
	const popupMakerImports = new Set();
	const importsByFile = {};

	// Scan JS/TS files
	jsFiles.forEach( ( file ) => {
		const fullPath = path.join( packageDir, file );
		const imports = findJSImports( fullPath );
		if ( imports.length > 0 ) {
			const normalizedImports = imports
				.map( normalizeToPackageName )
				.filter( Boolean );

			if ( normalizedImports.length > 0 ) {
				importsByFile[ file ] = normalizedImports;
				normalizedImports.forEach( ( imp ) => {
					allImports.add( imp );
					if ( imp.startsWith( '@popup-maker/' ) ) {
						popupMakerImports.add( normalizePopupMakerImport( imp ) );
					}
				} );
			}
		}
	} );

	// Scan SCSS files
	scssFiles.forEach( ( file ) => {
		const fullPath = path.join( packageDir, file );
		const imports = findScssImports( fullPath );
		if ( imports.length > 0 ) {
			if ( importsByFile[ file ] ) {
				importsByFile[ file ] = [ ...importsByFile[ file ], ...imports ];
			} else {
				importsByFile[ file ] = imports;
			}
			imports.forEach( ( imp ) => allImports.add( imp ) );
		}
	} );

	// Remove self-references
	allImports.delete( packageName );
	popupMakerImports.delete( packageName );

	return {
		packageName,
		packageJsonPath,
		packageJson,
		allImports: Array.from( allImports ),
		popupMakerImports: Array.from( popupMakerImports ),
		importsByFile,
		jsFiles,
		scssFiles,
	};
}

/**
 * Checks if a @types/* package should be considered used
 *
 * @param {string} typesPackage - The @types/* package name
 * @param {Set}    usedImports  - Set of actually used imports
 * @return {boolean} True if the @types package should be kept
 */
function isTypesPackageUsed( typesPackage, usedImports ) {
	if ( ! typesPackage.startsWith( '@types/' ) ) {
		return false;
	}

	const basePackage = typesPackage.replace( '@types/', '' );
	// Handle scoped packages: @types/wordpress__data -> @wordpress/data
	const normalizedBasePackage = basePackage.replace( '__', '/' );
	const scopedBasePackage = normalizedBasePackage.startsWith( 'wordpress' )
		? `@wordpress/${ normalizedBasePackage.replace( 'wordpress/', '' ) }`
		: normalizedBasePackage;

	// If the base package is used, keep the @types package
	return (
		usedImports.has( basePackage ) ||
		usedImports.has( scopedBasePackage )
	);
}

/**
 * Sorts an object's keys alphabetically
 *
 * @param {Object} obj - The object to sort
 * @return {Object} A new object with sorted keys
 */
function sortObjectKeys( obj ) {
	if ( ! obj || Object.keys( obj ).length === 0 ) {
		return obj;
	}

	const sortedKeys = Object.keys( obj ).sort();
	const sortedObj = {};
	sortedKeys.forEach( ( key ) => {
		sortedObj[ key ] = obj[ key ];
	} );
	return sortedObj;
}

module.exports = {
	findJSImports,
	findScssImports,
	normalizePopupMakerImport,
	normalizeToPackageName,
	scanPackageImports,
	isTypesPackageUsed,
	sortObjectKeys,
};