const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const {
	relativeArtifactPath,
	verifyArchiveEntryTypes,
	verifyArchiveStructure,
	verifyArtifactEntries,
	verifyReleaseManifest,
	verifySourceTree,
	verifyZip,
} = require( '../../../bin/verify-wporg-artifact' );

const fixturesRoot = path.join( __dirname, 'fixtures', 'wporg-artifact' );

function loadFixtureTree( fixtureName ) {
	const fixtureRoot = path.join( fixturesRoot, fixtureName );
	const entries = new Map();

	function loadDirectory( directory ) {
		for ( const directoryEntry of fs.readdirSync( directory, {
			withFileTypes: true,
		} ) ) {
			const entryPath = path.join( directory, directoryEntry.name );

			if ( directoryEntry.isDirectory() ) {
				loadDirectory( entryPath );
				continue;
			}

			entries.set(
				path
					.relative( fixtureRoot, entryPath )
					.replace( /\\/g, '/' )
					.replace( /\.fixture$/, '' ),
				fs.readFileSync( entryPath, 'utf8' )
			);
		}
	}

	loadDirectory( fixtureRoot );
	return entries;
}

function verifyFixture( fixtureName, overlays = new Map() ) {
	const fixtureEntries = loadFixtureTree( fixtureName );

	for ( const [ entry, contents ] of overlays ) {
		fixtureEntries.set( entry, contents );
	}

	return verifyArtifactEntries( [ ...fixtureEntries.keys() ], ( entry ) =>
		fixtureEntries.get( entry )
	);
}

describe( 'WordPress.org artifact verifier', () => {
	test( 'checks the product source tree before packaging', () => {
		const sourceRoot = path.resolve( __dirname, '../../..' );

		expect( verifySourceTree( sourceRoot ).failures ).toEqual( [] );
	} );

	test( 'allows only the named legacy updater and plugin lifecycle paths', () => {
		expect( verifyFixture( 'allowed' ) ).toEqual( [] );
	} );

	test( 'detects a renamed remote plugin delivery implementation', () => {
		const renamedInstaller = loadFixtureTree( 'renamed-installer' );
		const failures = verifyFixture( 'allowed', renamedInstaller );

		expect( failures ).toEqual(
			expect.arrayContaining( [
				expect.stringContaining(
					'plugin package upgrader capability in popup-maker/classes/Services/PackageDelivery.php'
				),
				expect.stringContaining(
					'plugin activation capability in popup-maker/classes/Services/PackageDelivery.php'
				),
				expect.stringContaining(
					'plugin delivery handler in popup-maker/classes/Services/PackageDelivery.php'
				),
			] )
		);
	} );

	test( 'detects renamed package download routes', () => {
		const renamedRoute = loadFixtureTree( 'renamed-route' );
		const failures = verifyFixture( 'allowed', renamedRoute );

		expect( failures ).toEqual(
			expect.arrayContaining( [
				expect.stringContaining(
					'plugin package upgrader capability in popup-maker/classes/RestAPI/PackageDelivery.php'
				),
				expect.stringContaining(
					'plugin delivery REST route in popup-maker/classes/RestAPI/PackageDelivery.php'
				),
			] )
		);
	} );

	test( 'does not extend the compatibility allowlist to renamed files', () => {
		const allowed = loadFixtureTree( 'allowed' );
		const legacyUpdater = allowed.get(
			'popup-maker/classes/Extension/Updater.php'
		);
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Services/RenamedUpdater.php',
					legacyUpdater,
				],
			] )
		);

		expect( failures ).toContain(
			'remote plugin package response in popup-maker/classes/Services/RenamedUpdater.php'
		);
	} );

	test( 'does not allow an installer inside the legacy updater file', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Extension/Updater.php',
					`<?php
class PUM_Extension_Updater {
\tpublic function install_plugin_package( $package ) {
\t\t$upgrader = new Plugin_Upgrader();
\t\treturn $upgrader->install( $package );
\t}
}`,
				],
			] )
		);

		expect( failures ).toEqual(
			expect.arrayContaining( [
				expect.stringContaining(
					'plugin package upgrader capability in popup-maker/classes/Extension/Updater.php'
				),
				expect.stringContaining(
					'plugin delivery handler in popup-maker/classes/Extension/Updater.php'
				),
			] )
		);
	} );

	test( 'rejects package execution hidden inside legacy updater metadata', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Extension/Updater.php',
					`<?php
class PUM_Extension_Updater {
\tpublic function check_update() {
\t\t$manifest = json_decode( wp_remote_retrieve_body(
\t\t\twp_remote_get( 'https://updates.example.test/' )
\t\t) );
\t\treturn $this->installer->install( $manifest->package );
\t}
}`,
				],
			] )
		);

		expect( failures ).toContain(
			'plugin package execution in popup-maker/classes/Extension/Updater.php'
		);
	} );

	test( 'rejects WP Upgrader run hidden inside legacy updater metadata', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Extension/Updater.php',
					`<?php
class PUM_Extension_Updater {
\tpublic function check_update() {
\t\t$manifest = json_decode( wp_remote_retrieve_body(
\t\t\twp_remote_get( 'https://updates.example.test/' )
\t\t) );
\t\treturn $this->installer->run( [
\t\t\t'package' => $manifest->package,
\t\t] );
\t}
}`,
				],
			] )
		);

		expect( failures ).toContain(
			'plugin package execution in popup-maker/classes/Extension/Updater.php'
		);
	} );

	test( 'rejects a generic executor inside the legacy updater exception', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Extension/Updater.php',
					`<?php
class PUM_Extension_Updater {
\tpublic function check_update( $api ) {
\t\t$manifest = json_decode( wp_remote_retrieve_body(
\t\t\twp_remote_get( $api )
\t\t) );
\t\t$payload = $manifest->download_url;
\t\treturn $this->installer->run( [
\t\t\t'source' => $payload,
\t\t] );
\t}
}`,
				],
			] )
		);

		expect( failures ).toContain(
			'plugin package execution in popup-maker/classes/Extension/Updater.php'
		);
	} );

	test( 'matches PHP plugin capabilities case-insensitively', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Services/MixedCaseDelivery.php',
					`<?php
$upgrader = new \\plugin_upgrader();
$result = $upgrader->install( $package );
\\ACTIVATE_PLUGIN( $result );`,
				],
			] )
		);

		expect( failures ).toEqual(
			expect.arrayContaining( [
				'plugin package upgrader capability in popup-maker/classes/Services/MixedCaseDelivery.php',
				'plugin activation capability in popup-maker/classes/Services/MixedCaseDelivery.php',
				'plugin package execution in popup-maker/classes/Services/MixedCaseDelivery.php',
			] )
		);
	} );

	test( 'scans executable inc files', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/includes/private-delivery.inc',
					`<?php
register_rest_route( 'popup-maker/v3', '/package/download', [] );`,
				],
			] )
		);

		expect( failures ).toContain(
			'plugin delivery REST route in popup-maker/includes/private-delivery.inc'
		);
	} );

	test( 'scans alternate executable PHP suffixes', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Services/PrivateInstaller.php5',
					`<?php
$upgrader = new Plugin_Upgrader();
$upgrader->install( $package );`,
				],
			] )
		);

		expect( failures ).toEqual(
			expect.arrayContaining( [
				'plugin package upgrader capability in popup-maker/classes/Services/PrivateInstaller.php5',
				'plugin package execution in popup-maker/classes/Services/PrivateInstaller.php5',
			] )
		);
	} );

	test( 'scans PHP payloads regardless of file suffix', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Services/PrivateInstaller.dat',
					`<?php
$upgrader = new Plugin_Upgrader();
$upgrader->install( $package );`,
				],
			] )
		);

		expect( failures ).toEqual(
			expect.arrayContaining( [
				'plugin package upgrader capability in popup-maker/classes/Services/PrivateInstaller.dat',
				'plugin package execution in popup-maker/classes/Services/PrivateInstaller.dat',
			] )
		);
	} );

	test( 'rejects executable phar archives instead of trusting text scanning', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/includes/private-delivery.phar',
					'binary payload',
				],
			] )
		);

		expect( failures ).toContain(
			'forbidden executable archive: includes/private-delivery.phar'
		);
	} );

	test( 'source verification rejects executable archives and symlinks', () => {
		const sourceRoot = fs.mkdtempSync(
			path.join( os.tmpdir(), 'popup-maker-source-verifier-' )
		);

		try {
			const requiredFiles = [
				'classes/Services/License.php',
				'classes/Extension/License.php',
				'classes/Extension/Updater.php',
			];

			for ( const requiredFile of requiredFiles ) {
				const filePath = path.join( sourceRoot, requiredFile );
				fs.mkdirSync( path.dirname( filePath ), { recursive: true } );
				fs.writeFileSync( filePath, '<?php // Compatibility fixture.' );
			}

			const pharPath = path.join(
				sourceRoot,
				'classes/Services/PrivateInstaller.phar'
			);
			const symlinkTarget = path.join(
				sourceRoot,
				'classes/Services/License.php'
			);
			const symlinkPath = path.join(
				sourceRoot,
				'classes/Services/LinkedInstaller.php'
			);
			const privateBootstrapPath = path.join(
				sourceRoot,
				'bin/self-hosted-bootstrap.php'
			);
			const privateInjectablePath = path.join(
				sourceRoot,
				'bin/self-hosted-injectable.php'
			);

			fs.writeFileSync( pharPath, 'binary fixture' );
			fs.symlinkSync( symlinkTarget, symlinkPath );
			fs.mkdirSync( path.dirname( privateBootstrapPath ), {
				recursive: true,
			} );
			fs.writeFileSync(
				privateBootstrapPath,
				'<?php // Direct fixture.'
			);
			fs.writeFileSync(
				privateInjectablePath,
				'<?php // Direct fixture.'
			);

			expect( verifySourceTree( sourceRoot ).failures ).toEqual(
				expect.arrayContaining( [
					'forbidden executable archive: classes/Services/PrivateInstaller.phar',
					'source symlink: classes/Services/LinkedInstaller.php',
					'forbidden path: bin/self-hosted-bootstrap.php',
					'forbidden path: bin/self-hosted-injectable.php',
				] )
			);
		} finally {
			fs.rmSync( sourceRoot, { recursive: true, force: true } );
		}
	} );

	test( 'rejects renamed Direct distribution injections', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/bin/direct-free-updater.php',
					`<?php
define( 'POPUP_MAKER_FREE_LICENSE', 'XXLICENSEXX' );
new PUM_Extension_Updater(
\t'https://wppopupmaker.com/edd-sl/',
\t__FILE__,
\t[ 'item_id' => 482276 ]
);`,
				],
			] )
		);

		expect( failures ).toEqual(
			expect.arrayContaining( [
				'Direct distribution bootstrap in popup-maker/bin/direct-free-updater.php',
				'Direct Core updater endpoint in popup-maker/bin/direct-free-updater.php',
				'Direct Core EDD product in popup-maker/bin/direct-free-updater.php',
			] )
		);
	} );

	test( 'rejects symbolic links stored in a ZIP artifact', () => {
		const fixtureRoot = fs.mkdtempSync(
			path.join( os.tmpdir(), 'popup-maker-zip-verifier-' )
		);
		const pluginRoot = path.join( fixtureRoot, 'popup-maker' );
		const zipPath = path.join( fixtureRoot, 'artifact.zip' );
		const requiredFiles = [
			'classes/Services/License.php',
			'classes/Extension/License.php',
			'classes/Extension/Updater.php',
		];

		try {
			for ( const requiredFile of requiredFiles ) {
				const filePath = path.join( pluginRoot, requiredFile );
				fs.mkdirSync( path.dirname( filePath ), { recursive: true } );
				fs.writeFileSync( filePath, '<?php // Compatibility fixture.' );
			}

			fs.symlinkSync(
				'License.php',
				path.join( pluginRoot, 'classes/Services/LinkedLicense.php' )
			);
			execFileSync( 'zip', [ '-qry', zipPath, 'popup-maker' ], {
				cwd: fixtureRoot,
			} );

			expect( verifyZip( zipPath ).failures ).toContain(
				'forbidden archive entry type in popup-maker/classes/Services/LinkedLicense.php'
			);
		} finally {
			fs.rmSync( fixtureRoot, { recursive: true, force: true } );
		}
	} );

	test( 'rejects WordPress core plugin installer dispatch', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/classes/Services/CoreInstaller.php',
					`<?php
function deliver_pro() {
\treturn wp_ajax_install_plugin();
}`,
				],
			] )
		);

		expect( failures ).toContain(
			'remote install handler in popup-maker/classes/Services/CoreInstaller.php'
		);
	} );

	test( 'does not accept compatibility files from a decoy archive root', () => {
		const allowed = loadFixtureTree( 'allowed' );
		const decoyEntries = new Map(
			[ ...allowed.entries() ].map( ( [ entry, contents ] ) => [
				entry.replace( /^popup-maker\//, 'decoy/' ),
				contents,
			] )
		);
		const failures = verifyArtifactEntries(
			[ ...decoyEntries.keys() ],
			( entry ) => decoyEntries.get( entry )
		);

		expect( failures ).toEqual(
			expect.arrayContaining( [
				'missing compatibility path: classes/Services/License.php',
				'missing compatibility path: classes/Extension/License.php',
				'missing compatibility path: classes/Extension/Updater.php',
			] )
		);
		expect(
			relativeArtifactPath( 'decoy/classes/Extension/Updater.php' )
		).toBe( 'decoy/classes/Extension/Updater.php' );
	} );

	test( 'rejects unsafe, duplicate, and non-plugin archive roots', () => {
		expect(
			verifyArchiveStructure( [
				'popup-maker/',
				'popup-maker/classes/License.php',
				'popup-maker/classes/License.php',
				'decoy/classes/License.php',
				'popup-maker/../private.php',
			] )
		).toEqual(
			expect.arrayContaining( [
				'duplicate archive path: popup-maker/classes/License.php',
				'unexpected archive root: decoy/classes/License.php',
				'unsafe archive path: popup-maker/../private.php',
			] )
		);
	} );

	test( 'rejects mismatched and non-regular archive entry types', () => {
		expect(
			verifyArchiveEntryTypes(
				[
					'popup-maker/',
					'popup-maker/classes/Services/License.php',
					'popup-maker/classes/Services/LinkedLicense.php',
				],
				[ '-', 'd', 'l' ]
			)
		).toEqual(
			expect.arrayContaining( [
				'archive entry type/name mismatch in popup-maker/',
				'archive entry type/name mismatch in popup-maker/classes/Services/License.php',
				'forbidden archive entry type in popup-maker/classes/Services/LinkedLicense.php',
			] )
		);
	} );

	test( 'rejects private and development material from release artifacts', () => {
		expect(
			verifyReleaseManifest( [
				'popup-maker/docs/architecture.md',
				'popup-maker/bin/dev-tool.sh',
				'popup-maker/tests/compliance.test.js',
				'popup-maker/packages/source/index.ts',
				'popup-maker/architecture.md',
				'popup-maker/dist/packages/runtime.js',
			] )
		).toEqual( [
			'forbidden release path: docs/architecture.md',
			'forbidden release path: bin/dev-tool.sh',
			'forbidden release path: tests/compliance.test.js',
			'forbidden release path: packages/source/index.ts',
			'forbidden release path: architecture.md',
		] );
	} );

	test( 'scans authored JSX before packaging', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/packages/example/src/Installer.jsx',
					`export const Installer = () =>
\tapiFetch( { path: '/popup-maker/v2/connect/install' } );`,
				],
			] )
		);

		expect( failures ).toEqual(
			expect.arrayContaining( [
				expect.stringContaining(
					'remote install route in popup-maker/packages/example/src/Installer.jsx'
				),
			] )
		);
	} );

	test( 'rejects the removed private installer permission', () => {
		const failures = verifyFixture(
			'allowed',
			new Map( [
				[
					'popup-maker/includes/namespaced/core.php',
					`<?php
$permissions = [ 'install_pro' => 'install_plugins' ];`,
				],
			] )
		);

		expect( failures ).toContain(
			'private installer identifier in popup-maker/includes/namespaced/core.php'
		);
	} );
} );
