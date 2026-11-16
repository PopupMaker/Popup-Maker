const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const {
	findExcludedPaths,
	globPatternToRegExp,
	loadExcludePatterns,
	verifyWordPressOrgArtifact,
} = require( '../../../bin/verify-wordpress-org-artifact' );

describe( 'WordPress.org artifact verifier', () => {
	let temporaryRoot;
	let buildDir;
	let manifestPath;

	beforeEach( () => {
		temporaryRoot = fs.mkdtempSync(
			path.join( os.tmpdir(), 'popup-maker-wordpress-org-verifier-' )
		);
		buildDir = path.join( temporaryRoot, 'popup-maker' );
		manifestPath = path.join( temporaryRoot, '.wordpress-org-excludes' );

		fs.mkdirSync( path.join( buildDir, 'languages' ), { recursive: true } );
		for ( const relativePath of [
			'popup-maker.php',
			'readme.txt',
			'languages/index.php',
			'languages/popup-maker.pot',
		] ) {
			fs.writeFileSync( path.join( buildDir, relativePath ), 'fixture' );
		}
		fs.writeFileSync(
			manifestPath,
			'/languages/*.po\n/languages/*.mo\n/languages/*.json\n/languages/*.l10n.php\n'
		);
	} );

	afterEach( () => {
		fs.rmSync( temporaryRoot, { recursive: true, force: true } );
	} );

	test( 'accepts the WordPress.org payload without bundled language packs', () => {
		expect( verifyWordPressOrgArtifact( buildDir, manifestPath ) ).toEqual(
			[]
		);
	} );

	test( 'rejects PO, MO, and JavaScript translation catalogs', () => {
		for ( const fileName of [
			'popup-maker-de_DE.po',
			'popup-maker-de_DE.mo',
			'popup-maker-de_DE-a1b2c3.json',
			'popup-maker-de_DE.l10n.php',
		] ) {
			fs.writeFileSync(
				path.join( buildDir, 'languages', fileName ),
				'fixture'
			);
		}

		const patterns = loadExcludePatterns( manifestPath );
		expect( findExcludedPaths( buildDir, patterns ) ).toEqual( [
			'languages/popup-maker-de_DE-a1b2c3.json',
			'languages/popup-maker-de_DE.l10n.php',
			'languages/popup-maker-de_DE.mo',
			'languages/popup-maker-de_DE.po',
		] );
		expect(
			verifyWordPressOrgArtifact( buildDir, manifestPath )
		).toHaveLength( 4 );
	} );

	test( 'matches nested and single-directory exclusion globs', () => {
		expect(
			globPatternToRegExp( '/languages/*.json' ).test(
				'languages/popup-maker-de_DE-hash.json'
			)
		).toBe( true );
		expect(
			globPatternToRegExp( '/languages/*.json' ).test(
				'languages/nested/popup-maker-de_DE-hash.json'
			)
		).toBe( false );
		expect(
			globPatternToRegExp( '/languages/**/*.json' ).test(
				'languages/nested/popup-maker-de_DE-hash.json'
			)
		).toBe( true );
	} );

	test( 'requires the POT template and directory guard', () => {
		fs.rmSync( path.join( buildDir, 'languages', 'index.php' ) );
		fs.rmSync( path.join( buildDir, 'languages', 'popup-maker.pot' ) );

		expect( verifyWordPressOrgArtifact( buildDir, manifestPath ) ).toEqual(
			expect.arrayContaining( [
				'missing WordPress.org path: languages/index.php',
				'missing WordPress.org path: languages/popup-maker.pot',
			] )
		);
	} );

	test( 'rejects unsafe exclusion patterns', () => {
		fs.writeFileSync( manifestPath, '../languages/*.po\n' );

		expect( () => loadExcludePatterns( manifestPath ) ).toThrow(
			'Unsafe WordPress.org exclusion pattern'
		);
	} );
} );
