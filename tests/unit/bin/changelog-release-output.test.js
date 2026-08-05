const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const pluginRoot = path.resolve( __dirname, '../../..' );
const extractScript = path.join( pluginRoot, 'bin/extract-changelog.js' );
const updateScript = path.join( pluginRoot, 'bin/update-changelog.js' );

const changelog = `# Popup Maker Changelog

## Unreleased

**Improvements**

-   Improved PHP compatibility.

**Developers**

-   Added PHP runtime coverage.

**Fixes**

-   Fixed popup rendering.

## v1.0.0 - 2026-01-01

**Fixes**

-   Fixed an older issue.
`;

describe( 'user-facing changelog release output', () => {
	let temporaryRoot;

	beforeEach( () => {
		temporaryRoot = fs.mkdtempSync(
			path.join( os.tmpdir(), 'popup-maker-changelog-' )
		);
		fs.writeFileSync(
			path.join( temporaryRoot, 'CHANGELOG.md' ),
			changelog
		);
	} );

	afterEach( () => {
		fs.rmSync( temporaryRoot, { recursive: true, force: true } );
	} );

	test( 'excludes developer notes from extracted release notes', () => {
		const output = execFileSync(
			process.execPath,
			[ extractScript, '--unreleased' ],
			{ cwd: temporaryRoot, encoding: 'utf8' }
		);

		expect( output ).toContain( 'Improved PHP compatibility.' );
		expect( output ).toContain( 'Fixed popup rendering.' );
		expect( output ).not.toContain( 'Developers' );
		expect( output ).not.toContain( 'Added PHP runtime coverage.' );
	} );

	test( 'returns a user-facing placeholder when only developer notes exist', () => {
		fs.writeFileSync(
			path.join( temporaryRoot, 'CHANGELOG.md' ),
			`# Popup Maker Changelog

## Unreleased

**Developers**

-   Added PHP runtime coverage.

## v1.0.0 - 2026-01-01
`
		);

		const output = execFileSync(
			process.execPath,
			[ extractScript, '--unreleased' ],
			{ cwd: temporaryRoot, encoding: 'utf8' }
		);

		expect( output ).toContain( 'No user-facing changes.' );
		expect( output ).not.toContain( 'Added PHP runtime coverage.' );
	} );

	test( 'retains developer notes in CHANGELOG.md but omits them from readme.txt', () => {
		fs.writeFileSync(
			path.join( temporaryRoot, 'readme.txt' ),
			`=== Popup Maker ===

== Changelog ==

For the latest updates and release information: https://example.com/changelog

= v1.0.0 - 2026-01-01 =

**Fixes**

-   Fixed an older issue.`
		);

		execFileSync( process.execPath, [ updateScript, '1.1.0' ], {
			cwd: temporaryRoot,
		} );

		const updatedChangelog = fs.readFileSync(
			path.join( temporaryRoot, 'CHANGELOG.md' ),
			'utf8'
		);
		const updatedReadme = fs.readFileSync(
			path.join( temporaryRoot, 'readme.txt' ),
			'utf8'
		);

		expect( updatedChangelog ).toContain( 'Added PHP runtime coverage.' );
		expect( updatedReadme ).toContain( 'Improved PHP compatibility.' );
		expect( updatedReadme ).toContain( 'Fixed popup rendering.' );
		expect( updatedReadme ).not.toContain( 'Developers' );
		expect( updatedReadme ).not.toContain( 'Added PHP runtime coverage.' );
	} );

	test( 'adds a readme placeholder when only developer notes exist', () => {
		fs.writeFileSync(
			path.join( temporaryRoot, 'CHANGELOG.md' ),
			`# Popup Maker Changelog

## Unreleased

**Developers**

-   Added PHP runtime coverage.

## v1.0.0 - 2026-01-01
`
		);
		fs.writeFileSync(
			path.join( temporaryRoot, 'readme.txt' ),
			`=== Popup Maker ===

== Changelog ==

For the latest updates and release information: https://example.com/changelog

= v1.0.0 - 2026-01-01 =

**Fixes**

-   Fixed an older issue.`
		);

		execFileSync( process.execPath, [ updateScript, '1.1.0' ], {
			cwd: temporaryRoot,
		} );

		const updatedReadme = fs.readFileSync(
			path.join( temporaryRoot, 'readme.txt' ),
			'utf8'
		);

		expect( updatedReadme ).toContain( 'No user-facing changes.' );
		expect( updatedReadme ).not.toContain( 'Added PHP runtime coverage.' );
	} );
} );
