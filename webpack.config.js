const path = require( 'path' );
const CustomTemplatedPathPlugin = require( '@popup-maker/custom-templated-path-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@popup-maker/dependency-extraction-webpack-plugin' );
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );
const RtlCssPlugin = require( 'rtlcss-webpack-plugin' );

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const NODE_ENV = process.env.NODE_ENV || 'development';

// Remove when block-editor is merged.
const oldPackages = {
	// 'block-editor': path.resolve( process.cwd(), 'src/block-editor' ),
};

const packages = {
	'admin-bar': 'packages/admin-bar',
	'admin-marketing': 'packages/admin-marketing',
	'block-editor': 'packages/block-editor',
	'block-library': 'packages/block-library',
	components: 'packages/components',
	'core-data': 'packages/core-data',
	data: 'packages/data',
	// fields: 'packages/fields',
	icons: 'packages/icons',
	utils: 'packages/utils',
};

const config = {
	...defaultConfig,
	entry: {
		...oldPackages,
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
			// Write the old block-editor output to block-editor/[name].js for now.
			return chunk.name === 'block-editor'
				? '../block-editor/[name].js'
				: '[name].js';
		},
		assetModuleFilename: '../images/[name][ext]',
		publicPath: '/wp-content/plugins/popup-maker/dist/packages/',
		devtoolNamespace: 'popup-maker',
		devtoolModuleFilenameTemplate: '../[resource-path]?[loaders]',
		// library: {
		// 	name: [ 'popupMaker', '[modulename]' ],
		// 	type: 'window',
		// },
		// uniqueName: '__popupMakerAdmin_webpackJsonp',
		clean: false, // Disable cleaning
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
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
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules.map( ( rule ) => {
				if (
					'asset/resource' === rule.type &&
					rule.test.test( '.png' )
				) {
					return {
						...rule,
						generator: {
							filename: '../images/[name].[hash:8][ext]',
						},
					};
				}

				return rule;
			} ),
		],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				// Remove when block-editor is merged.
				plugin.constructor.name !== 'MiniCSSExtractPlugin' &&
				// Remove when block-editor is merged.
				plugin.constructor.name !== 'RtlCssPlugin' &&
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
		// MiniCSSExtractPlugin to extract the CSS thats gets imported into JavaScript.
		// Remove when block-editor is merged.
		new MiniCSSExtractPlugin( {
			filename: ( { chunk } ) => {
				const isBlockEditor = chunk.name === 'block-editor';
				return isBlockEditor
					? '../block-editor/[name].css'
					: '[name].css';
			},
		} ),
		// RtlCssPlugin to generate RTL CSS files.
		// Remove when block-editor is merged.
		new RtlCssPlugin( {
			filename: ( { chunk } ) => {
				const isBlockEditor = chunk.name === 'block-editor';
				return isBlockEditor
					? '../block-editor/[name]-rtl.css'
					: '[name]-rtl.css';
			},
		} ),
		new DependencyExtractionWebpackPlugin( {
			// combineAssets: true,
			// combinedOutputFile: '../package-assets.php',
		} ),
	],
	optimization: {
		...defaultConfig.optimization,
		minimize: NODE_ENV !== 'development',
	},
};

module.exports = config;
