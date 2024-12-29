const path = require( 'path' );
const CustomTemplatedPathPlugin = require( '@popup-maker/custom-templated-path-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@popup-maker/dependency-extraction-webpack-plugin' );

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const NODE_ENV = process.env.NODE_ENV || 'development';

const packages = {
	'admin-bar': 'packages/admin-bar',
	'admin-marketing': 'packages/admin-marketing',
	'block-editor': 'packages/block-editor',
	'block-library': 'packages/block-library',
	components: 'packages/components',
	'core-data': 'packages/core-data',
	'cta-editor': 'packages/cta-editor',
	data: 'packages/data',
	fields: 'packages/fields',
	icons: 'packages/icons',
	utils: 'packages/utils',
};

const config = {
	...defaultConfig,
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
		filename: ( { chunk } ) => {
			return '[name].js';
		},
		// assetModuleFilename: '../images/[name][ext]',
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
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin' // &&
			// plugin.constructor.name !== 'CleanWebpackPlugin'
		),
		new CustomTemplatedPathPlugin( {
			modulename( outputPath, data ) {
				const entryName = data.chunk.name;
				if ( entryName ) {
					return entryName.replace( /-([a-z])/g, ( match, letter ) =>
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
	],
	optimization: {
		...defaultConfig.optimization,
		minimize: NODE_ENV !== 'development',
	},
	devServer: {
		...( defaultConfig.devServer || {} ),
		allowedHosts: 'all',
	},
};

module.exports = config;
