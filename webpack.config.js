const path = require( 'path' );
const CustomTemplatedPathPlugin = require( '@popup-maker/custom-templated-path-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( './packages/dependency-extraction-webpack-plugin' );

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const NODE_ENV = process.env.NODE_ENV || 'development';

const packages = {
	'admin-bar': 'packages/admin-bar',
	// 'block-library': 'packages/block-library',
};

const config = {
	...defaultConfig,
	entry: Object.entries( packages ).reduce(
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
	output: {
		path: path.resolve( process.cwd(), 'dist/packages' ),
		filename: '[name].js',
		assetModuleFilename: '../images/[name][ext]',
		publicPath: '/wp-content/plugins/popup-maker/dist/packages/',
		devtoolNamespace: 'popup-maker',
		devtoolModuleFilenameTemplate:
			'webpack://[namespace]/[resource-path]?[loaders]',
		library: {
			name: [ 'popupMaker', '[modulename]' ],
			type: 'window',
		},
		uniqueName: '__popupMakerAdmin_webpackJsonp',
		clean: false, // Disable cleaning
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx', '.scss' ],
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
			'@pum': path.resolve( process.cwd(), 'packages' ),
			'@images': path.resolve( __dirname, 'assets/images' ),
		},
		modules: [ 'node_modules', path.resolve( __dirname ) ],
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.(png|jpg|jpeg|gif|svg)$/,
				type: 'asset/resource',
			},
		],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !==
					'DependencyExtractionWebpackPlugin' &&
				plugin.constructor.name !== 'CleanWebpackPlugin'
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
			// injectPolyfill: true,
			// useDefaults: true,
		} ),
	],
	optimization: {
		...defaultConfig.optimization,
		minimize: NODE_ENV !== 'development',
	},
};

module.exports = config;
