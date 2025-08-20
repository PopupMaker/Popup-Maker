const path = require( 'path' );
const CustomTemplatedPathPlugin = require( './packages/custom-templated-path-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( './packages/dependency-extraction-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const RtlCssPlugin = require( 'rtlcss-webpack-plugin' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const NODE_ENV = process.env.NODE_ENV || 'development';
const isProduction = NODE_ENV === 'production';

const packages = {
	'admin-bar': 'packages/admin-bar',
	'admin-marketing': 'packages/admin-marketing',
	'block-editor': 'packages/block-editor',
	'block-library': 'packages/block-library',
	components: 'packages/components',
	'core-data': 'packages/core-data',
	'cta-admin': 'packages/cta-admin',
	'cta-editor': 'packages/cta-editor',
	dashboard: 'packages/dashboard',
	data: 'packages/data',
	fields: 'packages/fields',
	i18n: 'packages/i18n',
	icons: 'packages/icons',
	layout: 'packages/layout',
	'popup-admin': 'packages/popup-admin',
	registry: 'packages/registry',
	'use-query-params': 'packages/use-query-params',
	utils: 'packages/utils',
};

const config = {
	...defaultConfig,
	// Maps our buildList into a new object of { key: build.entry }.
	entry: {
		...Object.entries( packages ).reduce(
			( entry, [ packageName, packagePath ] ) => {
				entry[ packageName ] = path.resolve(
					process.cwd(),
					packagePath,
					'src'
				);
				return entry;
			},
			{}
		),
	},
	externals: {
		jquery: 'jQuery',
		...defaultConfig.externals,
	},
	output: {
		path: path.resolve( process.cwd(), 'dist/packages' ),
		publicPath: '/wp-content/plugins/popup-maker/dist/packages/',
		devtoolNamespace: 'popup-maker/core',
		devtoolModuleFilenameTemplate:
			'webpack://[namespace]/[resource-path]?[loaders]',
		library: {
			// Expose the exports of entry points so we can consume the libraries in window.popupMaker.[modulename] with DependencyExtractionWebpackPlugin.
			name: [ 'popupMaker', '[modulename]' ],
			type: 'window',
		},
		uniqueName: '__popupMakerAdmin_webpackJsonp',
		clean: false, // Disable cleaning
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx', '.scss', '.css' ],
		alias: {
			...defaultConfig.resolve.alias,
			...Object.entries( packages ).reduce(
				( alias, [ packageName, packagePath ] ) => {
					alias[ `@popup-maker/${ packageName }` ] = path.resolve(
						__dirname,
						packagePath
					);
					return alias;
				},
				{}
			),
		},
		modules: [ 'node_modules', path.resolve( __dirname ) ],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !==
					'DependencyExtractionWebpackPlugin' &&
				plugin.constructor.name !== 'MiniCssExtractPlugin' &&
				plugin.constructor.name !== 'RtlCssPlugin'
		),
		new MiniCssExtractPlugin( {
			filename: ( { chunk } ) => {
				if ( chunk.name && chunk.name.includes( 'style' ) ) {
					return `${ chunk.runtime }-style.css`;
				}

				return `${ chunk.runtime }.css`;
			},
		} ),
		new RtlCssPlugin( {
			filename: ( { chunk } ) => {
				if ( chunk.name && chunk.name.includes( 'style-' ) ) {
					return `${ chunk.runtime }-style-rtl.css`;
				}
				return `${ chunk.runtime }-rtl.css`;
			},
		} ),
		new CustomTemplatedPathPlugin( {
			modulename( outputPath, data ) {
				const entryName = data.chunk.name;
				if ( entryName ) {
					// Convert the dash-case name to a camel case module name.
					// For example, 'csv-export' -> 'csvExport'
					return entryName.replace( /-([a-z])/g, ( _, letter ) =>
						letter.toUpperCase()
					);
				}
				return outputPath;
			},
		} ),
		new DependencyExtractionWebpackPlugin( {
			combineAssets: true,
			combinedOutputFile: '../package-assets.php',
		} ),
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: 'packages/block-library/src/lib/**/block.json',
					to( { absoluteFilename } ) {
						const pathParts = absoluteFilename.split( '/' );
						const blockDirName = pathParts[ pathParts.length - 2 ];
						return `../blocks/${ blockDirName }.block.json`;
					},
					context: '.',
					noErrorOnMissing: true,
					transform( content ) {
						return content;
					},
				},
			],
		} ),
		// Bundle analyzer - only in analyze mode
		...( process.env.ANALYZE
			? [
					new ( require( 'webpack-bundle-analyzer' ).BundleAnalyzerPlugin )(
						{
							analyzerMode: 'server',
							analyzerPort: 8888,
							openAnalyzer: false,
						}
					),
			  ]
			: [] ),
	],
	cache: {
		type: 'filesystem',
		cacheDirectory: path.resolve( process.cwd(), '.webpack-cache' ),
		buildDependencies: {
			// Invalidate cache when webpack config changes
			config: [ __filename ],
			// Invalidate cache when package.json changes (dependencies)
			packages: [ path.resolve( process.cwd(), 'package.json' ) ],
		},
		// Cache for 7 days in production, 1 day in development
		maxAge: isProduction ? 1000 * 60 * 60 * 24 * 7 : 1000 * 60 * 60 * 24,
		compression: 'gzip',
		name: `popup-maker-packages-${ NODE_ENV }`,
		version: require( path.resolve( process.cwd(), 'package.json' ) )
			.version,
	},
	optimization: {
		...defaultConfig.optimization,
		minimize: NODE_ENV !== 'development',
		// Tree shaking optimizations
		usedExports: true,
		sideEffects: false, // Enable aggressive tree shaking
		// Module concatenation (scope hoisting)
		concatenateModules: isProduction,
	},
	devServer: {
		...( defaultConfig.devServer || {} ),
		allowedHosts: 'all',
		// port: 8887,
		// Fix for webpack-dev-server proxy configuration issue
		proxy: undefined, // Remove any inherited proxy configuration that might be causing the array format issue
	},
};

module.exports = config;
