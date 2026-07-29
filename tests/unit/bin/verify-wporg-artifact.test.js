const fs = require( 'fs' );
const path = require( 'path' );

const {
	verifyArtifactEntries,
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
} );
