#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const zipPath = path.resolve(
	process.argv[ 2 ] || path.join( process.cwd(), 'popup-maker-latest.zip' )
);

if ( ! fs.existsSync( zipPath ) ) {
	throw new Error( `Artifact not found: ${ zipPath }` );
}

const entries = execFileSync( 'unzip', [ '-Z1', zipPath ], {
	encoding: 'utf8',
} )
	.split( '\n' )
	.filter( Boolean );

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

const failures = [];

for ( const forbiddenPath of forbiddenPaths ) {
	if ( entries.some( ( entry ) => entry.endsWith( forbiddenPath ) ) ) {
		failures.push( `forbidden path: ${ forbiddenPath }` );
	}
}

for ( const requiredPath of requiredPaths ) {
	if ( ! entries.some( ( entry ) => entry.endsWith( requiredPath ) ) ) {
		failures.push( `missing compatibility path: ${ requiredPath }` );
	}
}

const textEntries = entries.filter( ( entry ) =>
	/\.(?:php|js|map|json|txt|html|css|scss|ts|tsx)$/.test( entry )
);

const forbiddenPatterns = [
	[ 'Connect service class', /Services\\{1,2}Connect/ ],
	[ 'silent installer class', /PluginSilentUpgrader/ ],
	[ 'remote install route', /popup-maker\/v[12]\/(?:connect|upgrade\/install)/ ],
	[ 'remote install handler', /(?:rest_install_pro|installProPlugin|pum_install_pro_plugin)/ ],
	[ 'installation metadata endpoint', /license\/(?:activate-pro|connect-info)/ ],
	[ 'installer-specific UI', /(?:pum-install-pro|pum-license-connect-trigger)/ ],
	[ 'Connect payload generator', /generate_connect_info/ ],
	[ 'private upgrade validator', /validate_for_upgrade/ ],
];

for ( const entry of textEntries ) {
	const contents = execFileSync( 'unzip', [ '-p', zipPath, entry ], {
		encoding: 'utf8',
		maxBuffer: 20 * 1024 * 1024,
	} );

	for ( const [ label, pattern ] of forbiddenPatterns ) {
		if ( pattern.test( contents ) ) {
			failures.push( `${ label } in ${ entry }` );
		}
	}
}

if ( failures.length ) {
	console.error( 'WordPress.org artifact verification failed:' );
	for ( const failure of failures ) {
		console.error( `- ${ failure }` );
	}
	process.exit( 1 );
}

console.log(
	`WordPress.org artifact verification passed (${ entries.length } files checked).`
);
