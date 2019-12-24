/*******************************************************************************
 * Copyright (c) 2019, WP Popup Maker
 ******************************************************************************/

const webpack = require('webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');
const config = require('./config');
const mode = require('./lib/mode');
const JS_DEV = path.resolve(config.root.dev, config.js.dev);
const JS_DIST = path.resolve(config.root.dist, config.js.dist);
const UglifyJS = require('uglify-es');
const UglifyJsPlugin = require( 'uglifyjs-webpack-plugin' );
const UnminifiedWebpackPlugin = require('unminified-webpack-plugin');

const adminPages = [
	// 'customers',
	// 'dashboard',
	// 'discounts',
	// 'downloads',
	// 'tools/export',
	// 'tools/import',
	// 'notes',
	// 'orders',
	// 'reports',
	// 'payments',
	// 'settings',
	// 'tools'
];

const minifyJs = (content) => {
	return mode.production ? Promise.resolve(Buffer.from(UglifyJS.minify(content.toString()).code)) : content;
};

const webpackConfig = {
	mode: mode.production ? 'production' : 'development',
	// context: JS_DEV,
	entry: Object.assign(
		// Dynamic entry points for individual admin pages.
		adminPages.reduce((memo, path) => {
			memo[`pum-admin-${path.replace('/', '-')}`] = `${JS_DEV}/admin/${path}`;
			return memo;
		}, {}),
		{
			'pum-admin-deprecated': `${JS_DEV}/admin/deprecated.js`,
			'popup-maker-easy-modal-importer-site': `${JS_DEV}/popup-maker-easy-modal-importer-site.js`,
			'mce-buttons': `${JS_DEV}/mce-buttons.js`
		}
	),
	output: {
		filename: '[name].min.js',
		path: JS_DIST
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: path.resolve(__dirname, 'node_modules/'),
				include: JS_DEV,
				loader: 'babel-loader',
				// options: {
				// 	presets: [
				// 		[
				// 			'env',
				// 			{modules: false},
				// 		],
				// 	],
				// },
			},
		],
	},
	externals: {
		jquery: 'jQuery',
		$: 'jQuery',
	},
	resolve: {
		modules: [
			JS_DEV,
			config.modules.node,
			config.modules.bower,
		],
		extensions: config.js.extensions,
	},
	plugins: [
		// Copy vendor files to ensure 3rd party plugins relying on a script
		// handle to exist continue to be enqueued.
		new CopyWebpackPlugin([
			{
				from: './node_modules/mobile-detect/mobile-detect.min.js',
				to: `${JS_DIST}/vendor/mobile-detect.min.js`,
				//transform: (content) => minifyJs(content),
			},
			{
				from: './node_modules/iframe-resizer/js/iframeResizer.min.js',
				to: `${JS_DIST}/vendor/iframeResizer.min.js`,
				transform: (content) => minifyJs(content),
			},
		]),
		new UglifyJsPlugin(),
		new UnminifiedWebpackPlugin()
	]
};

/**
 * Modify webpackConfig depends on mode
 */
if (mode.production) {
	//webpackConfig.optimization.minimize = true;
	webpackConfig.plugins.push(
		new webpack.NoEmitOnErrorsPlugin(),
	);
} else {
	webpackConfig.devtool = 'inline-source-map';
}

module.exports = webpackConfig;
