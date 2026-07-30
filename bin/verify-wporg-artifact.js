#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const forbiddenPaths = [
	'classes/Services/Connect.php',
	'classes/Services/Upgrader.php',
	'classes/RestAPI/Connect.php',
	'classes/Installers/Install_Skin.php',
	'classes/Installers/PluginSilentUpgrader.php',
	'classes/Installers/PluginSilentUpgraderSkin.php',
	'assets/js/src/admin/settings-page/pro-upgrade-flow.js',
	'assets/js/src/admin/settings-page/license-status-polling.js',
	'bin/self-hosted-bootstrap.php',
	'bin/self-hosted-injectable.php',
];

const requiredPaths = [
	'classes/Services/License.php',
	'classes/Extension/License.php',
	'classes/Extension/Updater.php',
];

const forbiddenReleasePathPrefixes = [
	'.github/',
	'bin/',
	'docs/',
	'node_modules/',
	'packages/',
	'tests/',
	'vendor/',
];

const sourceRoots = [
	'assets',
	'bin',
	'classes',
	'dist',
	'includes',
	'languages',
	'packages',
	'templates',
	'vendor-prefixed',
];

const textEntryPattern =
	/\.(?:php\d*|phtml|phtm|pht|inc|js|jsx|mjs|cjs|map|json|txt|html|css|scss|ts|tsx|svg|xml|po|pot)$/i;

const forbiddenPatterns = [
	[ 'Connect service class', /Services\\{1,2}Connect/i ],
	[ 'silent installer class', /PluginSilentUpgrader/i ],
	[ 'private Connect host', /upgrade\.wppopupmaker\.com/i ],
	[
		'remote install route',
		/popup-maker\/v[12]\/(?:connect|upgrade\/install)/i,
	],
	[
		'remote install handler',
		/(?:rest_install_pro|installProPlugin|pum_install_pro_plugin|wp_ajax_install_plugin)/i,
	],
	[
		'installation metadata endpoint',
		/license\/(?:activate-pro|connect-info)/i,
	],
	[
		'installer-specific UI',
		/(?:pum-install-pro|pum-license-connect-trigger)/i,
	],
	[ 'private installer identifier', /['"]install_pro['"]/i ],
	[ 'Connect payload generator', /generate_connect_info/i ],
	[ 'private upgrade validator', /validate_for_upgrade/i ],
	[
		'Direct distribution bootstrap',
		/\b(?:PUM_SELF_HOSTED|PUM_SelfHosted_Updater|POPUP_MAKER_FREE_LICENSE)\b/i,
	],
	[
		'Direct Core updater endpoint',
		/https?:\/\/wppopupmaker\.com\/edd-sl\//i,
	],
	[ 'Direct Core EDD product', /['"]item_id['"]\s*=>\s*482276\b/i ],
];

const compatibilityAllowlists = {
	complianceTooling: new Set( [ 'bin/verify-wporg-artifact.js' ] ),
	legacyUpdaterMetadata: new Set( [ 'classes/Extension/Updater.php' ] ),
	pluginLifecycle: new Set( [
		'popup-maker.php',
		'classes/Activator.php',
		'classes/Deactivator.php',
		'classes/Extensions.php',
		'classes/Install.php',
		'includes/pum-sdk/class-pum-extension-activation.php',
	] ),
};

const capabilityRules = [
	{
		label: 'plugin package upgrader capability',
		pattern:
			/\b(?:Plugin_Upgrader|WP_Upgrader)\b|\b(?:download_url|unzip_file)\s*\(/i,
		allowedPaths: new Set(),
	},
	{
		label: 'plugin activation capability',
		pattern: /\b(?:activate_plugin|deactivate_plugins)\s*\(/i,
		allowedPaths: compatibilityAllowlists.pluginLifecycle,
	},
	{
		label: 'plugin delivery handler',
		pattern:
			/(?:function\s+|(?:->|::))(?:(?:[a-z0-9_]+_)?(?:install|download|deploy|sideload|activate)_(?:plugin|package|archive)(?:_[a-z0-9_]+)?|(?:[a-z0-9_]+_)?(?:plugin|package|archive)_(?:install|download|deploy|sideload|activate)(?:_[a-z0-9_]+)?|[a-z0-9]*(?:install|download|deploy|sideload|activate)(?:plugin|package|archive)[a-z0-9]*|[a-z0-9]*(?:plugin|package|archive)(?:install|download|deploy|sideload|activate)[a-z0-9]*)\s*\(/i,
		allowedPaths: compatibilityAllowlists.pluginLifecycle,
	},
	{
		label: 'plugin package execution',
		patterns: [
			/(?:->|::)(?:install|upgrade|download|unpack|run)\s*\(/i,
			/(?:\$(?:package|plugin|archive|zip)\b|->package\b|['"]package['"]\s*=>|\b(?:Plugin_Upgrader|WP_Upgrader)\b|class-plugin-upgrader\.php)/i,
		],
		allowedPaths: new Set(),
	},
	{
		label: 'plugin delivery REST route',
		pattern:
			/register_rest_route\s*\([\s\S]{0,600}['"`][^'"`]*(?:connect|install|download|upgrade|deploy|sideload)[^'"`]*['"`]/i,
		allowedPaths: new Set(),
	},
	{
		label: 'remote plugin package response',
		patterns: [
			/\bwp_(?:safe_)?remote_(?:get|post|request)\s*\(/i,
			/(?:\bWP_PLUGIN_DIR\b|wp-content\/plugins|class-plugin-upgrader\.php|['"]package['"]\s*=>|->package\b)/i,
		],
		allowedPaths: compatibilityAllowlists.legacyUpdaterMetadata,
	},
	{
		label: 'remote plugin delivery request',
		pattern:
			/\b(?:apiFetch|fetch|ajax\.post)\s*\([\s\S]{0,500}(?:activate-pro|activate-plugin|connect|install|download|upgrade|deploy|sideload)/i,
		allowedPaths: new Set(),
	},
];

function relativeArtifactPath( entry ) {
	const normalized = entry
		.replace( /\\/g, '/' )
		.replace( /^(?:\.\/|\/)+/, '' );

	if (
		/^(?:assets|classes|dist|includes|languages|packages|templates|vendor-prefixed)\//.test(
			normalized
		) ||
		/^[^/]+\.php$/.test( normalized )
	) {
		return normalized;
	}

	const artifactRoot = 'popup-maker/';

	return normalized.startsWith( artifactRoot )
		? normalized.slice( artifactRoot.length )
		: normalized;
}

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
			'popup-maker' !== pathWithoutTrailingSlash &&
			! normalized.startsWith( 'popup-maker/' )
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

function verifyReleaseManifest( entries ) {
	const failures = [];

	for ( const entry of entries ) {
		const relativePath = relativeArtifactPath( entry );
		const isForbiddenPrefix = forbiddenReleasePathPrefixes.some(
			( prefix ) => relativePath.startsWith( prefix )
		);
		const isRootMarkdown = /^[^/]+\.md$/i.test( relativePath );

		if ( isForbiddenPrefix || isRootMarkdown ) {
			failures.push( `forbidden release path: ${ relativePath }` );
		}
	}

	return [ ...new Set( failures ) ];
}

function scanCapabilities( entry, contents ) {
	const relativePath = relativeArtifactPath( entry );
	const failures = [];

	if (
		compatibilityAllowlists.legacyUpdaterMetadata.has( relativePath ) &&
		/(?:->|::)(?:install|upgrade|download|unpack|run)\s*\(/i.test(
			contents
		)
	) {
		failures.push( `plugin package execution in ${ entry }` );
	}

	for ( const rule of capabilityRules ) {
		if ( rule.allowedPaths.has( relativePath ) ) {
			continue;
		}

		const matched = rule.patterns
			? rule.patterns.every( ( pattern ) => pattern.test( contents ) )
			: rule.pattern.test( contents );

		if ( matched ) {
			failures.push( `${ rule.label } in ${ entry }` );
		}
	}

	return failures;
}

function verifyArtifactEntries( entries, readEntry ) {
	const failures = [];
	const relativePaths = new Set( entries.map( relativeArtifactPath ) );

	for ( const forbiddenPath of forbiddenPaths ) {
		if ( relativePaths.has( forbiddenPath ) ) {
			failures.push( `forbidden path: ${ forbiddenPath }` );
		}
	}

	for ( const relativePath of relativePaths ) {
		if ( /\.phar$/i.test( relativePath ) ) {
			failures.push( `forbidden executable archive: ${ relativePath }` );
		}
	}

	for ( const requiredPath of requiredPaths ) {
		if ( ! relativePaths.has( requiredPath ) ) {
			failures.push( `missing compatibility path: ${ requiredPath }` );
		}
	}

	for ( const entry of entries.filter(
		( archiveEntry ) => ! archiveEntry.endsWith( '/' )
	) ) {
		if (
			compatibilityAllowlists.complianceTooling.has(
				relativeArtifactPath( entry )
			)
		) {
			continue;
		}

		const entryContents = readEntry( entry );
		const isKnownText = textEntryPattern.test( entry );
		const containsPhp = /<\?(?:php|=)/i.test(
			entryContents.toString( 'latin1' )
		);

		if ( ! isKnownText && ! containsPhp ) {
			continue;
		}

		const contents = entryContents.toString();

		for ( const [ label, pattern ] of forbiddenPatterns ) {
			if ( pattern.test( contents ) ) {
				failures.push( `${ label } in ${ entry }` );
			}
		}

		failures.push( ...scanCapabilities( entry, contents ) );
	}

	return [ ...new Set( failures ) ];
}

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

function verifyZipEntryTypes( zipPath, entries ) {
	const listing = execFileSync( 'unzip', [ '-Z', '-l', zipPath ], {
		encoding: 'utf8',
	} );
	const entryTypes = listing
		.split( '\n' )
		.filter( ( line ) => /^[bcdlps-].{9}\s/.test( line ) )
		.map( ( line ) => line.charAt( 0 ) );

	return verifyArchiveEntryTypes( entries, entryTypes );
}

function verifyZip( zipPath ) {
	if ( ! fs.existsSync( zipPath ) ) {
		throw new Error( `Artifact not found: ${ zipPath }` );
	}

	const entries = execFileSync( 'unzip', [ '-Z1', zipPath ], {
		encoding: 'utf8',
	} )
		.split( '\n' )
		.filter( Boolean );

	const failures = [
		...verifyArchiveStructure( entries ),
		...verifyZipEntryTypes( zipPath, entries ),
		...verifyReleaseManifest( entries ),
		...verifyArtifactEntries( entries, ( entry ) =>
			execFileSync( 'unzip', [ '-p', zipPath, entry ], {
				encoding: 'utf8',
				maxBuffer: 20 * 1024 * 1024,
			} )
		),
	];

	return { entries, failures };
}

function collectSourceEntries( projectRoot ) {
	const entries = [];
	const skippedDirectories = new Set( [
		'.git',
		'coverage',
		'node_modules',
		'vendor',
	] );

	function walkDirectory( directory ) {
		for ( const directoryEntry of fs.readdirSync( directory, {
			withFileTypes: true,
		} ) ) {
			const entryPath = path.join( directory, directoryEntry.name );

			if (
				skippedDirectories.has( directoryEntry.name ) &&
				( directoryEntry.isDirectory() ||
					directoryEntry.isSymbolicLink() )
			) {
				continue;
			}

			if ( directoryEntry.isDirectory() ) {
				walkDirectory( entryPath );
				continue;
			}

			if ( directoryEntry.isFile() || directoryEntry.isSymbolicLink() ) {
				entries.push(
					path
						.relative( projectRoot, entryPath )
						.replace( /\\/g, '/' )
				);
			}
		}
	}

	for ( const sourceRoot of sourceRoots ) {
		const sourcePath = path.join( projectRoot, sourceRoot );

		if ( fs.existsSync( sourcePath ) ) {
			walkDirectory( sourcePath );
		}
	}

	for ( const rootEntry of fs.readdirSync( projectRoot, {
		withFileTypes: true,
	} ) ) {
		if (
			( rootEntry.isFile() || rootEntry.isSymbolicLink() ) &&
			! rootEntry.name.startsWith( '.' ) &&
			/^[^/]+\.php\d*$/i.test( rootEntry.name )
		) {
			entries.push( rootEntry.name );
		}
	}

	return [ ...new Set( entries ) ].sort();
}

function verifySourceTree( projectRoot ) {
	const entries = collectSourceEntries( projectRoot );
	const symlinkEntries = entries.filter( ( entry ) =>
		fs.lstatSync( path.join( projectRoot, entry ) ).isSymbolicLink()
	);
	const regularEntries = entries.filter(
		( entry ) => ! symlinkEntries.includes( entry )
	);
	const failures = [
		...symlinkEntries.map( ( entry ) => `source symlink: ${ entry }` ),
		...verifyArtifactEntries( regularEntries, ( entry ) =>
			fs.readFileSync( path.join( projectRoot, entry ) )
		),
	];

	return { entries, failures: [ ...new Set( failures ) ] };
}

function main() {
	const sourceMode = '--source' === process.argv[ 2 ];
	const targetPath = sourceMode
		? path.resolve( process.argv[ 3 ] || process.cwd() )
		: path.resolve(
				process.argv[ 2 ] ||
					path.join( process.cwd(), 'popup-maker-latest.zip' )
		  );
	const { entries, failures } = sourceMode
		? verifySourceTree( targetPath )
		: verifyZip( targetPath );
	const targetLabel = sourceMode ? 'source' : 'artifact';

	if ( failures.length ) {
		console.error( `WordPress.org ${ targetLabel } verification failed:` );
		for ( const failure of failures ) {
			console.error( `- ${ failure }` );
		}
		process.exitCode = 1;
		return;
	}

	console.log(
		`WordPress.org ${ targetLabel } verification passed (${ entries.length } files checked).`
	);
}

if ( require.main === module ) {
	main();
}

module.exports = {
	collectSourceEntries,
	relativeArtifactPath,
	scanCapabilities,
	verifyArchiveEntryTypes,
	verifyArchiveStructure,
	verifyArtifactEntries,
	verifyReleaseManifest,
	verifySourceTree,
	verifyZip,
	verifyZipEntryTypes,
};

/* eslint-enable no-console */
