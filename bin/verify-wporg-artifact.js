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
];

const requiredPaths = [
	'classes/Services/License.php',
	'classes/Extension/License.php',
	'classes/Extension/Updater.php',
	'includes/namespaced/licensing.php',
];

const textEntryPattern = /\.(?:php|js|map|json|txt|html|css|scss|ts|tsx)$/;

const forbiddenPatterns = [
	[ 'Connect service class', /Services\\{1,2}Connect/ ],
	[ 'silent installer class', /PluginSilentUpgrader/ ],
	[
		'remote install route',
		/popup-maker\/v[12]\/(?:connect|upgrade\/install)/,
	],
	[
		'remote install handler',
		/(?:rest_install_pro|installProPlugin|pum_install_pro_plugin)/,
	],
	[
		'installation metadata endpoint',
		/license\/(?:activate-pro|connect-info)/,
	],
	[
		'installer-specific UI',
		/(?:pum-install-pro|pum-license-connect-trigger)/,
	],
	[ 'Connect payload generator', /generate_connect_info/ ],
	[ 'private upgrade validator', /validate_for_upgrade/ ],
];

const compatibilityAllowlists = {
	legacyUpdater: new Set( [ 'classes/Extension/Updater.php' ] ),
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
			/\b(?:Plugin_Upgrader|WP_Upgrader)\b|\b(?:download_url|unzip_file)\s*\(/,
		allowedPaths: compatibilityAllowlists.legacyUpdater,
	},
	{
		label: 'plugin activation capability',
		pattern: /\b(?:activate_plugin|deactivate_plugins)\s*\(/,
		allowedPaths: compatibilityAllowlists.pluginLifecycle,
	},
	{
		label: 'plugin delivery handler',
		pattern:
			/(?:function\s+|(?:->|::))(?:(?:[a-z0-9_]+_)?(?:install|download|deploy|sideload|activate)_(?:plugin|package|archive)(?:_[a-z0-9_]+)?|(?:[a-z0-9_]+_)?(?:plugin|package|archive)_(?:install|download|deploy|sideload|activate)(?:_[a-z0-9_]+)?|[A-Za-z0-9]*(?:Install|Download|Deploy|Sideload|Activate)(?:Plugin|Package|Archive)[A-Za-z0-9]*|[A-Za-z0-9]*(?:Plugin|Package|Archive)(?:Install|Download|Deploy|Sideload|Activate)[A-Za-z0-9]*)\s*\(/,
		allowedPaths: new Set( [
			...compatibilityAllowlists.legacyUpdater,
			...compatibilityAllowlists.pluginLifecycle,
		] ),
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
			/\bwp_(?:safe_)?remote_(?:get|post|request)\s*\(/,
			/(?:\bWP_PLUGIN_DIR\b|wp-content\/plugins|class-plugin-upgrader\.php|['"]package['"]\s*=>|->package\b)/,
		],
		allowedPaths: compatibilityAllowlists.legacyUpdater,
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
		/^(?:assets|classes|dist|includes|languages|templates|vendor-prefixed)\//.test(
			normalized
		) ||
		/^[^/]+\.php$/.test( normalized )
	) {
		return normalized;
	}

	const pathParts = normalized.split( '/' );

	return pathParts.length > 1 ? pathParts.slice( 1 ).join( '/' ) : normalized;
}

function scanCapabilities( entry, contents ) {
	const relativePath = relativeArtifactPath( entry );
	const failures = [];

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

	for ( const requiredPath of requiredPaths ) {
		if ( ! relativePaths.has( requiredPath ) ) {
			failures.push( `missing compatibility path: ${ requiredPath }` );
		}
	}

	for ( const entry of entries.filter( ( archiveEntry ) =>
		textEntryPattern.test( archiveEntry )
	) ) {
		const contents = readEntry( entry ).toString();

		for ( const [ label, pattern ] of forbiddenPatterns ) {
			if ( pattern.test( contents ) ) {
				failures.push( `${ label } in ${ entry }` );
			}
		}

		failures.push( ...scanCapabilities( entry, contents ) );
	}

	return [ ...new Set( failures ) ];
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

	const failures = verifyArtifactEntries( entries, ( entry ) =>
		execFileSync( 'unzip', [ '-p', zipPath, entry ], {
			encoding: 'utf8',
			maxBuffer: 20 * 1024 * 1024,
		} )
	);

	return { entries, failures };
}

function main() {
	const zipPath = path.resolve(
		process.argv[ 2 ] ||
			path.join( process.cwd(), 'popup-maker-latest.zip' )
	);
	const { entries, failures } = verifyZip( zipPath );

	if ( failures.length ) {
		console.error( 'WordPress.org artifact verification failed:' );
		for ( const failure of failures ) {
			console.error( `- ${ failure }` );
		}
		process.exitCode = 1;
		return;
	}

	console.log(
		`WordPress.org artifact verification passed (${ entries.length } files checked).`
	);
}

if ( require.main === module ) {
	main();
}

module.exports = {
	relativeArtifactPath,
	scanCapabilities,
	verifyArtifactEntries,
	verifyZip,
};

/* eslint-enable no-console */
