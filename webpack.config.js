const path = require( 'path' );
const CustomTemplatedPathPlugin = require( '@popup-maker/custom-templated-path-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@popup-maker/dependency-extraction-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const RtlCssPlugin = require( 'rtlcss-webpack-plugin' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

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
	i18n: 'packages/i18n',
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
			// plugin.constructor.name !== 'CleanWebpackPlugin'
		),
		new MiniCssExtractPlugin( {
			filename: ( { chunk } ) => {
				if ( chunk.name.includes( 'style' ) ) {
					return `${ chunk.runtime }-style.css`;
				}

				return `${ chunk.runtime }.css`;
			},
		} ),
		new RtlCssPlugin( {
			filename: ( { chunk } ) => {
				if ( chunk.name.includes( 'style-' ) ) {
					return `${ chunk.runtime }-style-rtl.css`;
				}
				return `${ chunk.runtime }-rtl.css`;
			},
		} ),
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
