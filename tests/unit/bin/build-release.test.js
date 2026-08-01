const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const PluginReleaseBuilder = require( '../../../bin/build-release' );

describe( 'PluginReleaseBuilder verification safety', () => {
	const temporaryDirectories = [];

	function createBuilder() {
		const projectRoot = fs.mkdtempSync(
			path.join( os.tmpdir(), 'popup-maker-release-' )
		);
		temporaryDirectories.push( projectRoot );
		fs.writeFileSync(
			path.join( projectRoot, 'package.json' ),
			JSON.stringify( {
				name: 'popup-maker',
				version: '1.23.0',
				files: [],
			} )
		);

		return new PluginReleaseBuilder( {
			projectRoot,
			skipComposer: true,
			skipNpm: true,
			quiet: true,
		} );
	}

	function mockZipCreation( builder ) {
		const latestZip = path.join(
			builder.projectRoot,
			'popup-maker-latest.zip'
		);
		const versionedZip = path.join(
			builder.projectRoot,
			'popup-maker_1.23.0.zip'
		);

		builder.createZipFiles = jest.fn( () => {
			fs.writeFileSync( latestZip, 'invalid latest artifact' );
			fs.writeFileSync( versionedZip, 'invalid versioned artifact' );
			builder.releaseArtifactPaths = [ latestZip, versionedZip ];
			return versionedZip;
		} );

		return { latestZip, versionedZip };
	}

	afterEach( () => {
		for ( const temporaryDirectory of temporaryDirectories.splice( 0 ) ) {
			fs.rmSync( temporaryDirectory, { recursive: true, force: true } );
		}
	} );

	test( 'removes every ZIP and reports no success when verification fails', async () => {
		const builder = createBuilder();
		const artifacts = mockZipCreation( builder );
		builder.runParallelBuilds = jest.fn().mockResolvedValue();
		builder.copyDistributionFiles = jest.fn();
		builder.cleanup = jest.fn();
		builder.verifyArtifact = jest.fn( () => {
			throw new Error( 'fixture verification failed' );
		} );

		await expect( builder.build() ).rejects.toThrow(
			'fixture verification failed'
		);

		expect( fs.existsSync( artifacts.latestZip ) ).toBe( false );
		expect( fs.existsSync( artifacts.versionedZip ) ).toBe( false );
		expect( builder.cleanup ).toHaveBeenCalledTimes( 1 );

		const loggedOutput = console.log.mock.calls.flat().join( '\n' ); // eslint-disable-line no-console
		expect( loggedOutput ).not.toContain( 'Release created' );
		expect( loggedOutput ).not.toContain(
			'Release build completed successfully'
		);
		expect( console ).toHaveLoggedWith( '🚀 Building popup-maker v1.23.0' );
	} );

	test( 'announces a release only after the artifact passes verification', async () => {
		const builder = createBuilder();
		const { versionedZip } = mockZipCreation( builder );
		const callOrder = [];
		builder.runParallelBuilds = jest.fn( () => {
			callOrder.push( 'build' );
			return Promise.resolve();
		} );
		builder.copyDistributionFiles = jest.fn();
		builder.cleanup = jest.fn( () => {
			callOrder.push( 'cleanup' );
		} );
		builder.verifyArtifact = jest.fn( () => {
			callOrder.push( 'verify' );
		} );
		builder.announceRelease = jest.fn( () => {
			callOrder.push( 'announce' );
		} );

		await expect( builder.build() ).resolves.toBe( versionedZip );

		expect( callOrder ).toEqual( [
			'build',
			'verify',
			'cleanup',
			'announce',
		] );
		expect( fs.existsSync( versionedZip ) ).toBe( true );
		expect( console ).toHaveLoggedWith( '🚀 Building popup-maker v1.23.0' );
	} );

	test( 'cleans stale root and custom-output artifacts before building', () => {
		const builder = createBuilder();
		const outputDir = path.join( builder.projectRoot, 'release-output' );
		fs.mkdirSync( outputDir );
		builder.outputDir = outputDir;

		const artifactPaths = builder.getReleaseArtifactCandidates();
		for ( const artifactPath of artifactPaths ) {
			fs.writeFileSync( artifactPath, 'stale artifact' );
		}

		builder.cleanBuildArtifacts();

		for ( const artifactPath of artifactPaths ) {
			expect( fs.existsSync( artifactPath ) ).toBe( false );
		}
	} );
} );
