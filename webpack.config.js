const webpackMerge = require( 'webpack-merge' );
const defaultConfig = require( './node_modules/@wordpress/scripts/config/webpack.config.js' );
const path = require( 'path' );
const postcssPresetEnv = require( 'postcss-preset-env' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const { mapValues, transform, forIn } = require( 'lodash' );

const production = process.env.NODE_ENV === '';

const srcPath = path.resolve( process.cwd(), 'src' );

const buildList = {
	'block-editor': {
		entry: path.resolve( srcPath, 'block-editor' ),
		cssChunks: { 'style': 'block-styles', 'editor': 'block-editor-styles' },
	},
};

/**
 * Using webpackMerge.strategy to merge our config with the wp-scripts base config.
 *
 * strategy was used to ensure we overwrite the entry value entirely.
 */
const config = webpackMerge.strategy(
	{
		entry: 'replace',
	},
)( {}, defaultConfig, {
	// Maps our buildList into a new object of { key: build.entry }.
	entry: mapValues( buildList, ( build ) => build.entry ),
	// This version allows moving entry point to its own subfolder.
	// entry: transform( buildList, ( entryPoints = {}, build, buildName ) => {
	// 	entryPoints[ buildName + '/' + 'index' ] = build.entry;
	// }),
	optimization: {
		splitChunks: {
			cacheGroups: {
				// Creats a new cache group for each cssChunk such as import './style.scss'; or import './editor.scss';
				...transform( buildList, ( cacheGroups = {}, build, buildName ) => {
					if ( undefined !== build.cssChunks ) {
						forIn( build.cssChunks, ( filename, chunkName ) => {
							cacheGroups[ chunkName ] = {
								// name: buildName + '/' + filename,
								name: filename,
								test: new RegExp( `${ chunkName }\.(sc|sa|c)ss$` ),
								chunks: 'all',
								enforce: true,
							};
						} );
					}
				} ),
				default: false,
			},
		},
	},
	plugins: [
		new MiniCssExtractPlugin(),
		/**
		 * This ad-hoc plugin removes the empty chunks that MiniCssExtractPlugin leaves behind.
		 */
		{
			apply( compiler ) {
				compiler.hooks.shouldEmit.tap( 'Remove styles from output', ( compilation ) => {
					forIn( buildList, ( build, buildName ) => {
						if ( build.cssChunks ) {
							forIn( build.cssChunks, ( fileName, chunkName ) => {
								delete compilation.assets[ fileName + '.js' ];
							} );
						}
					} );

					return true;
				} );
			},
		},
	],
	module: {
		rules: [
			{
				test: /\.(sc|sa|c)ss$/,
				exclude: /node_modules/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							sourceMap: ! production,
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							ident: 'postcss',
							plugins: () => [
								postcssPresetEnv( {
									stage: 3,
									features: {
										'custom-media-queries': {
											preserve: false,
										},
										'custom-properties': {
											preserve: true,
										},
										'nesting-rules': true,
									},
								} ),
							],
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sourceMap: ! production,
						},
					},
				],
			},
		],
	},
} );

module.exports = config;