const webpackMerge = require( 'webpack-merge' );
const defaultConfig = require( '../node_modules/@wordpress/scripts/config/webpack.config.js' );
const path = require( 'path' );
const postcssPresetEnv = require( 'postcss-preset-env' );
// const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const IgnoreEmitPlugin = require( 'ignore-emit-webpack-plugin' );

const production = process.env.NODE_ENV === '';

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
	entry: {
		'block-editor/block-editor': path.resolve( process.cwd(), 'src', 'block-editor/index.js' ),
		'block-editor/block-styles': path.resolve( process.cwd(), 'src', 'block-editor/blocks/styles.scss' ),
		'block-editor/block-editor-styles': path.resolve( process.cwd(), 'src', 'block-editor/editor.scss' ),
	},
	output: {
		path: path.resolve( process.cwd(), 'dist' ),
	},
	// optimization: {
	// 	splitChunks: {
	// 		cacheGroups: {
	// 			// editor: {
	// 			// 	name: 'block-editor/block-editor-styles',
	// 			// 	test: /editor\.(sc|sa|c)ss$/,
	// 			// 	chunks: 'all',
	// 			// 	enforce: true,
	// 			// },
	// 			// style: {
	// 			// 	name: 'block-editor/block-styles',
	// 			// 	test: /style\.(sc|sa|c)ss$/,
	// 			// 	chunks: 'all',
	// 			// 	enforce: true,
	// 			// },
	// 			default: false,
	// 		},
	// 	},
	// },
	module: {
		rules: [
			{
				test: /\.(sc|sa|c)ss$/,
				exclude: /node_modules/,
				use: [
					// MiniCssExtractPlugin.loader,
					// {
					// 	loader: 'css-loader',
					// 	options: {
					// 		sourceMap: ! production,
					// 	},
					// },
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
	plugins: [
		// new MiniCssExtractPlugin( {
		// 	filename: '[name].css',
		// } ),
		new IgnoreEmitPlugin( [ /-styles.js$/, /-styles.min.js$/, /-styles.js.map$/ ] ),
	],
} );

module.exports = config;
