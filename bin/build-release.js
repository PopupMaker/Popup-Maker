#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );
const glob = require( 'glob' );
const { execSync } = require( 'child_process' );

/**
 * Unified Plugin Release Builder
 *
 * This script handles the complete release process for WordPress plugins:
 * 1. Cleans previous build artifacts
 * 2. Installs production dependencies (Composer)
 * 3. Builds production assets (npm)
 * 4. Copies distribution files based on package.json files array
 * 5. Creates versioned zip file
 * 6. Cleans up temporary files
 *
 * Usage:
 *   node bin/build-release.js [options]
 *
 * Options:
 *   --project-root <path>  Project root directory (default: current working directory)
 *   --plugin-name <name>   Plugin name override (default: from package.json name)
 *   --zip-name <name>      Zip file name override (default: {plugin-name}_{version}.zip)
 *   --output-dir <path>    Output directory for zip file (default: project root)
 *   --keep-build          Keep build directory after creating zip
 *   --skip-composer       Skip composer install step
 *   --skip-npm            Skip npm build step
 *   --quiet               Minimal output (progress only)
 *   --verbose             Show detailed output from build commands
 *   --help                Show this help message
 */

class PluginReleaseBuilder {
	constructor( options = {} ) {
		this.options = {
			projectRoot: null,
			pluginName: null,
			zipFileName: null,
			outputDir: null,
			keepBuild: false,
			skipComposer: false,
			skipNpm: false,
			quiet: false,
			verbose: false,
			...options,
		};

		this.setupPaths();
		this.loadPackageJSON();
	}

	setupPaths() {
		// Default project root to current working directory (where npm script was called from)
		this.projectRoot = this.options.projectRoot || process.cwd();

		this.buildPath = path.join( this.projectRoot, 'build' );
		this.outputDir = this.options.outputDir || this.projectRoot;

		if ( this.options.verbose ) {
			console.log( `Project root: ${ this.projectRoot }` );
		}
	}

	loadPackageJSON() {
		const packagePath = path.join( this.projectRoot, 'package.json' );

		if ( ! fs.existsSync( packagePath ) ) {
			throw new Error( `package.json not found at ${ packagePath }` );
		}

		this.packageJSON = require( packagePath );
		this.pluginName = this.options.pluginName || this.packageJSON.name;
		this.version = this.packageJSON.version;

		if ( this.options.verbose ) {
			console.log(
				`Building release for: ${ this.pluginName } v${ this.version }`
			);
		}
	}

	removeDirectory( directoryPath ) {
		if ( fs.existsSync( directoryPath ) ) {
			if ( this.options.verbose ) {
				console.log(
					`Removing directory: ${ path.relative(
						this.projectRoot,
						directoryPath
					) }`
				);
			}

			if ( fs.rmSync ) {
				// Use rmSync if available (Node 14.14+)
				fs.rmSync( directoryPath, { recursive: true, force: true } );
			} else {
				// Use rmdirSync for backward compatibility
				fs.rmdirSync( directoryPath, { recursive: true } );
			}
		}
	}

	executeCommand( command, description ) {
		if ( this.options.verbose ) {
			console.log( `${ description }...` );
			console.log( `Running: ${ command }` );
		} else if ( this.options.quiet ) {
			// Show minimal progress
			process.stdout.write( `${ description }... ` );
		} else {
			// Default mode: show description but not command
			console.log( `${ description }...` );
		}

		try {
			const result = execSync( command, {
				cwd: this.projectRoot,
				stdio: this.options.verbose ? 'inherit' : 'pipe',
				encoding: 'utf8',
				env: { ...process.env }, // Clean environment without our script's arguments
			} );

			if ( this.options.quiet ) {
				console.log( '✅' );
			} else if ( ! this.options.verbose ) {
				console.log( '✅ Done' );
			}

			return result;
		} catch ( error ) {
			if ( this.options.quiet || ! this.options.verbose ) {
				console.log( '❌' );
			}

			console.error( `\n❌ Failed to execute: ${ command }` );

			// Always show error output, even in quiet mode
			if ( error.stdout ) {
				console.error( 'STDOUT:' );
				console.error( error.stdout.toString() );
			}
			if ( error.stderr ) {
				console.error( 'STDERR:' );
				console.error( error.stderr.toString() );
			}

			console.error( `Exit code: ${ error.status }` );
			process.exit( 1 );
		}
	}

	cleanBuildArtifacts() {
		if ( ! this.options.quiet ) {
			console.log( '\n=== Cleaning build artifacts ===' );
		}

		// Clean build directory
		this.removeDirectory( this.buildPath );

		// Clean any existing plugin directory in root
		const pluginDir = path.join( this.projectRoot, this.pluginName );
		this.removeDirectory( pluginDir );

		// Clean any existing zip files for this version
		const existingZip = path.join(
			this.outputDir,
			`${ this.pluginName }_${ this.version }.zip`
		);

		if ( fs.existsSync( existingZip ) ) {
			if ( this.options.verbose ) {
				console.log(
					`Removing existing zip: ${ path.relative(
						this.projectRoot,
						existingZip
					) }`
				);
			}
			fs.unlinkSync( existingZip );
		}

		// Clean any existing zip files for -latest.zip
		const latestZip = path.join(
			this.outputDir,
			`${ this.pluginName }-latest.zip`
		);

		if ( fs.existsSync( latestZip ) ) {
			if ( this.options.verbose ) {
				console.log(
					`Removing existing zip: ${ path.relative(
						this.projectRoot,
						latestZip
					) }`
				);
			}
			fs.unlinkSync( latestZip );
		}
	}

	copyDistributionFiles() {
		if ( ! this.options.quiet ) {
			console.log( '\n=== Copying distribution files ===' );
		}

		// Create build directory
		if ( ! fs.existsSync( this.buildPath ) ) {
			fs.mkdirSync( this.buildPath, { recursive: true } );
		}

		// Get files array from package.json, or use default patterns
		const filePatterns = this.packageJSON.files || [
			'*.php',
			'assets/**/*',
			'classes/**/*',
			'includes/**/*',
			'languages/**/*',
			'templates/**/*',
			'vendor/**/*',
			'readme.txt',
			'LICENSE',
		];

		if ( this.options.verbose ) {
			console.log( `Using file patterns:`, filePatterns );
		}

		let fileCount = 0;

		// Process each pattern
		filePatterns.forEach( ( pattern ) => {
			const files = glob.sync( path.join( this.projectRoot, pattern ) );

			files.forEach( ( file ) => {
				const relativePath = path.relative( this.projectRoot, file );

				// Skip if it's the build directory itself or node_modules
				if (
					relativePath.startsWith( 'build' ) ||
					relativePath.startsWith( 'node_modules' ) ||
					relativePath.startsWith( '.git' )
				) {
					return;
				}

				const dest = path.join( this.buildPath, relativePath );
				// Ensure destination directory exists
				const destDir = path.dirname( dest );
				if ( ! fs.existsSync( destDir ) ) {
					fs.mkdirSync( destDir, { recursive: true } );
				}

				// Copy file or directory
				if ( fs.lstatSync( file ).isDirectory() ) {
					if ( ! fs.existsSync( dest ) ) {
						fs.mkdirSync( dest, { recursive: true } );
					}
				} else {
					fs.copyFileSync( file, dest );
					fileCount++;
				}
			} );
		} );

		if ( this.options.verbose ) {
			console.log(
				`Files copied to: ${ path.relative(
					this.projectRoot,
					this.buildPath
				) }`
			);
		} else if ( ! this.options.quiet ) {
			console.log( `✅ Copied ${ fileCount } files` );
		}
	}

	createZipFiles() {
		if ( ! this.options.quiet ) {
			console.log( '\n=== Creating release zip ===' );
		}

		const pluginDir = path.join( this.projectRoot, this.pluginName );
		const zipName =
			this.options.zipFileName ||
			`${ this.pluginName }_${ this.version }.zip`;
		const zipPath = path.join( this.outputDir, zipName );

		// Move build directory to plugin name
		if ( fs.existsSync( pluginDir ) ) {
			this.removeDirectory( pluginDir );
		}

		fs.renameSync( this.buildPath, pluginDir );

		// Create latest zip file
		this.executeCommand(
			`zip -r "${ this.pluginName }-latest.zip" "${ this.pluginName }"`,
			`Creating latest zip file`
		);

		// Copy (cp) to versioned zip file
		this.executeCommand(
			`cp "${ this.pluginName }-latest.zip" "${ zipName }"`,
			`Creating versioned zip file`
		);

		// Move zip to output directory if different from project root
		if ( this.outputDir !== this.projectRoot ) {
			const sourceZip = path.join( this.projectRoot, zipName );
			if ( fs.existsSync( sourceZip ) ) {
				fs.renameSync( sourceZip, zipPath );
			}
		}

		// Always show the final result
		console.log(
			`\n✅ Release created: \n- ${ path.relative(
				process.cwd(),
				`${ this.pluginName }-latest.zip`
			) } \n- ${ path.relative( process.cwd(), zipPath ) }`
		);

		return zipPath;
	}

	cleanup() {
		if ( this.options.keepBuild ) {
			if ( ! this.options.quiet ) {
				console.log( '\n=== Keeping build directory as requested ===' );
			}
			return;
		}

		if ( ! this.options.quiet ) {
			console.log( '\n=== Cleaning up ===' );
		}

		const pluginDir = path.join( this.projectRoot, this.pluginName );
		this.removeDirectory( pluginDir );
		this.removeDirectory( this.buildPath );
	}

	async build() {
		console.log(
			`🚀 Building ${ this.pluginName } v${ this.version }${
				! this.options.quiet ? '\n' : ''
			}`
		);

		try {
			this.cleanBuildArtifacts();

			// Run composer and npm builds in parallel for significant time savings
			await this.runParallelBuilds();

			this.copyDistributionFiles();
			// eslint-disable-next-line no-unused-vars
			const _zipPath = this.createZipFiles();
			this.cleanup();

			if ( ! this.options.quiet ) {
				console.log( `\n✅ Release build completed successfully!` );
			}
		} catch ( error ) {
			console.error( `\n❌ Release build failed:`, error.message );
			process.exit( 1 );
		}
	}

	async runParallelBuilds() {
		if ( ! this.options.quiet ) {
			console.log( '\n=== Running parallel builds ===' );
		}

		const startTime = Date.now();

		// Create promises for parallel execution
		const buildPromises = [];

		// Add composer install promise if not skipped
		if ( ! this.options.skipComposer ) {
			buildPromises.push(
				this.runComposerInstall().catch( ( error ) => {
					throw new Error(
						`Composer install failed: ${ error.message }`
					);
				} )
			);
		}

		// Add npm build promise if not skipped
		if ( ! this.options.skipNpm ) {
			buildPromises.push(
				this.runNpmBuild().catch( ( error ) => {
					throw new Error( `NPM build failed: ${ error.message }` );
				} )
			);
		}

		// Wait for all builds to complete
		// eslint-disable-next-line no-useless-catch
		try {
			await Promise.all( buildPromises );

			const duration = ( ( Date.now() - startTime ) / 1000 ).toFixed( 1 );
			if ( ! this.options.quiet ) {
				console.log( `✅ Parallel builds completed in ${ duration }s` );
			}
		} catch ( error ) {
			throw error;
		}
	}

	async runComposerInstall() {
		return new Promise( ( resolve, reject ) => {
			// Check if composer.json exists
			const composerPath = path.join( this.projectRoot, 'composer.json' );
			if ( ! fs.existsSync( composerPath ) ) {
				if ( ! this.options.quiet ) {
					console.log(
						'No composer.json found, skipping Composer install'
					);
				}
				resolve();
				return;
			}

			if ( this.options.verbose ) {
				console.log( 'Installing Composer dependencies...' );
			} else if ( this.options.quiet ) {
				process.stdout.write( 'Installing Composer dependencies... ' );
			} else {
				console.log( 'Installing Composer dependencies...' );
			}

			try {
				const result = execSync(
					'composer install -o --no-dev --classmap-authoritative',
					{
						cwd: this.projectRoot,
						stdio: this.options.verbose ? 'inherit' : 'pipe',
						encoding: 'utf8',
						env: { ...process.env },
					}
				);

				if ( this.options.quiet ) {
					console.log( '✅' );
				} else if ( ! this.options.verbose ) {
					console.log( '✅ Done' );
				}

				resolve( result );
			} catch ( error ) {
				if ( this.options.quiet || ! this.options.verbose ) {
					console.log( '❌' );
				}

				console.error( `\n❌ Composer install failed:` );
				if ( error.stdout ) {
					console.error( 'STDOUT:' );
					console.error( error.stdout.toString() );
				}
				if ( error.stderr ) {
					console.error( 'STDERR:' );
					console.error( error.stderr.toString() );
				}
				console.error( `Exit code: ${ error.status }` );

				reject( error );
			}
		} );
	}

	async runNpmBuild() {
		return new Promise( ( resolve, reject ) => {
			// Check if package.json has build scripts
			let buildCommand;
			if (
				this.packageJSON.scripts &&
				this.packageJSON.scripts[ 'build:production' ]
			) {
				buildCommand = 'npm run build:production';
			} else if (
				this.packageJSON.scripts &&
				this.packageJSON.scripts.build
			) {
				buildCommand = 'NODE_ENV=production npm run build';
			} else {
				if ( ! this.options.quiet ) {
					console.log(
						'No build scripts found in package.json, skipping npm build'
					);
				}
				resolve();
				return;
			}

			if ( this.options.verbose ) {
				console.log( 'Building production assets...' );
			} else if ( this.options.quiet ) {
				process.stdout.write( 'Building production assets... ' );
			} else {
				console.log( 'Building production assets...' );
			}

			try {
				const result = execSync( buildCommand, {
					cwd: this.projectRoot,
					stdio: this.options.verbose ? 'inherit' : 'pipe',
					encoding: 'utf8',
					env: { ...process.env },
				} );

				if ( this.options.quiet ) {
					console.log( '✅' );
				} else if ( ! this.options.verbose ) {
					console.log( '✅ Done' );
				}

				resolve( result );
			} catch ( error ) {
				if ( this.options.quiet || ! this.options.verbose ) {
					console.log( '❌' );
				}

				console.error( `\n❌ NPM build failed:` );
				if ( error.stdout ) {
					console.error( 'STDOUT:' );
					console.error( error.stdout.toString() );
				}
				if ( error.stderr ) {
					console.error( 'STDERR:' );
					console.error( error.stderr.toString() );
				}
				console.error( `Exit code: ${ error.status }` );

				reject( error );
			}
		} );
	}
}

// CLI argument parsing
function parseArgs() {
	const args = process.argv.slice( 2 );
	const options = {};

	for ( let i = 0; i < args.length; i++ ) {
		const arg = args[ i ];

		switch ( arg ) {
			case '--help':
				showHelp();
				process.exit( 0 );
				break;

			case '--project-root':
				options.projectRoot = args[ ++i ];
				break;

			case '--plugin-name':
				options.pluginName = args[ ++i ];
				break;

			case '--zip-name':
				options.zipFileName = args[ ++i ];
				break;

			case '--output-dir':
				options.outputDir = args[ ++i ];
				break;

			case '--keep-build':
				options.keepBuild = true;
				break;

			case '--skip-composer':
				options.skipComposer = true;
				break;

			case '--skip-npm':
				options.skipNpm = true;
				break;

			case '--quiet':
				options.quiet = true;
				break;

			case '--verbose':
				options.verbose = true;
				break;

			default:
				if ( arg.startsWith( '--' ) ) {
					console.error( `Unknown option: ${ arg }` );
					process.exit( 1 );
				}
		}
	}

	return options;
}

function showHelp() {
	console.log( `
Unified Plugin Release Builder

Usage: node bin/build-release.js [options]

Options:
  --project-root <path>   Project root directory (default: current working directory)
  --plugin-name <name>    Plugin name override (default: from package.json name)
  --zip-name <name>       Zip file name override (default: {plugin-name}_{version}.zip)
  --output-dir <path>     Output directory for zip file (default: project root)
  --keep-build           Keep build directory after creating zip
  --skip-composer        Skip composer install step
  --skip-npm             Skip npm build step
  --quiet                Minimal output (progress only)
  --verbose              Show detailed output from build commands
  --help                 Show this help message

Examples:
  node bin/build-release.js
  node bin/build-release.js --plugin-name my-custom-plugin
  node bin/build-release.js --zip-name my-plugin-v1.0.0.zip
  node bin/build-release.js --output-dir ./releases --keep-build
  node bin/build-release.js --skip-composer --skip-npm
` );
}

// Main execution
if ( require.main === module ) {
	const options = parseArgs();
	const builder = new PluginReleaseBuilder( options );
	builder.build().catch( ( error ) => {
		console.error( `\n❌ Release build failed:`, error.message );
		process.exit( 1 );
	} );
}

module.exports = PluginReleaseBuilder;
