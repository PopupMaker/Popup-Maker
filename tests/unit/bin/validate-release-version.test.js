const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const {
	compareVersions,
	validateReleaseVersion,
} = require( '../../../bin/validate-release-version' );

describe( 'release version validation', () => {
	let projectRoot;

	beforeEach( () => {
		projectRoot = fs.mkdtempSync(
			path.join( os.tmpdir(), 'popup-maker-release-version-' )
		);
		fs.writeFileSync(
			path.join( projectRoot, 'package.json' ),
			JSON.stringify( { version: '1.25.0' } )
		);
		fs.writeFileSync(
			path.join( projectRoot, 'popup-maker.php' ),
			" * Version:           1.25.0\n'version' => '1.25.0',\n"
		);
		fs.writeFileSync(
			path.join( projectRoot, 'readme.txt' ),
			'Stable tag: 1.25.0\n\n= 1.25.0 - 2026-09-01 =\n'
		);
		fs.writeFileSync(
			path.join( projectRoot, 'CHANGELOG.md' ),
			'## v1.25.0 - 2026-09-01\n'
		);
	} );

	afterEach( () => {
		fs.rmSync( projectRoot, { recursive: true, force: true } );
	} );

	test( 'accepts one consistent version newer than the base release', () => {
		expect(
			validateReleaseVersion( {
				projectRoot,
				version: '1.25.0',
				previousVersion: '1.24.0',
			} )
		).toMatchObject( {
			'package.json': '1.25.0',
			'readme.txt stable tag': '1.25.0',
		} );
	} );

	test( 'rejects mismatched release files', () => {
		fs.writeFileSync(
			path.join( projectRoot, 'readme.txt' ),
			'Stable tag: 1.24.0\n\n= 1.25.0 - 2026-09-01 =\n'
		);

		expect( () =>
			validateReleaseVersion( { projectRoot, version: '1.25.0' } )
		).toThrow( 'readme.txt stable tag=1.24.0' );
	} );

	test( 'rejects a release that does not advance the base version', () => {
		expect( () =>
			validateReleaseVersion( {
				projectRoot,
				version: '1.25.0',
				previousVersion: '1.25.0',
			} )
		).toThrow( 'must be newer' );
	} );

	test( 'compares semantic version parts numerically', () => {
		expect( compareVersions( '1.25.0', '1.24.9' ) ).toBeGreaterThan( 0 );
		expect( compareVersions( '2.0.0', '1.99.99' ) ).toBeGreaterThan( 0 );
	} );
} );
