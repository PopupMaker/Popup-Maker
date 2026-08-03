const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const {
	relativeArtifactPath,
	verifyArchiveEntryTypes,
	verifyArchiveStructure,
	verifyReleaseManifest,
	verifyZip,
} = require( '../../../bin/verify-release-artifact' );

describe( 'release artifact verifier', () => {
	test( 'accepts a minimal Popup Maker release ZIP', () => {
		const temporaryRoot = fs.mkdtempSync(
			path.join( os.tmpdir(), 'popup-maker-release-verifier-' )
		);
		const pluginRoot = path.join( temporaryRoot, 'popup-maker' );
		const zipPath = path.join( temporaryRoot, 'popup-maker.zip' );

		try {
			for ( const relativePath of [
				'popup-maker.php',
				'readme.txt',
				'vendor-prefixed/autoload.php',
			] ) {
				const filePath = path.join( pluginRoot, relativePath );
				fs.mkdirSync( path.dirname( filePath ), { recursive: true } );
				fs.writeFileSync( filePath, '<?php // Release fixture.' );
			}

			execFileSync( 'zip', [ '-qr', zipPath, 'popup-maker' ], {
				cwd: temporaryRoot,
			} );

			expect( verifyZip( zipPath ).failures ).toEqual( [] );
		} finally {
			fs.rmSync( temporaryRoot, { recursive: true, force: true } );
		}
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
					'popup-maker/popup-maker.php',
					'popup-maker/linked.php',
				],
				[ '-', 'd', 'l' ]
			)
		).toEqual(
			expect.arrayContaining( [
				'archive entry type/name mismatch in popup-maker/',
				'archive entry type/name mismatch in popup-maker/popup-maker.php',
				'forbidden archive entry type in popup-maker/linked.php',
			] )
		);
	} );

	test( 'rejects development files, platform junk, and missing runtime files', () => {
		expect(
			verifyReleaseManifest( [
				'popup-maker/popup-maker.php',
				'popup-maker/readme.txt',
				'popup-maker/bin/dev-tool.js',
				'popup-maker/packages/source/index.ts',
				'popup-maker/__MACOSX/._readme.txt',
				'popup-maker/.DS_Store',
				'popup-maker/assets/._icon.svg',
			] )
		).toEqual(
			expect.arrayContaining( [
				'missing release path: vendor-prefixed/autoload.php',
				'forbidden release path: bin/dev-tool.js',
				'forbidden release path: packages/source/index.ts',
				'forbidden release path: __MACOSX/._readme.txt',
				'forbidden release path: .DS_Store',
				'forbidden release path: assets/._icon.svg',
			] )
		);
	} );

	test( 'normalizes paths beneath the single plugin root', () => {
		expect(
			relativeArtifactPath( 'popup-maker/classes/Services/License.php' )
		).toBe( 'classes/Services/License.php' );
		expect( relativeArtifactPath( 'decoy/readme.txt' ) ).toBe(
			'decoy/readme.txt'
		);
	} );
} );
