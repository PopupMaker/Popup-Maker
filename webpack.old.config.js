const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const { entry } = require( './webpack.config' );

const NODE_ENV = process.env.NODE_ENV || 'development';

const isProduction = NODE_ENV === 'production';
const srcPath = path.join( process.cwd(), './assets/js/src' );
const distPath = path.join( process.cwd(), './dist/assets' );

const jsBuilds = {
	// Admin
	'admin-batch': path.join( srcPath, 'admin/batch' ),
	'admin-general': path.join( srcPath, 'admin/general' ),
	'admin-marketing': path.join( srcPath, 'admin/marketing' ),
	'admin-pointer': path.join( srcPath, 'admin/pointer' ),
	'admin-popup-editor': path.join( srcPath, 'admin/popup-editor' ),
	'admin-settings-page': path.join( srcPath, 'admin/settings-page' ),
	'admin-shortcode-ui': path.join( srcPath, 'admin/shortcode-ui' ),
	'admin-theme-editor': path.join( srcPath, 'admin/theme-editor' ),
	'mce-buttons': path.join( srcPath, 'mce-buttons' ),
	'popup-maker-easy-modal-importer-site': path.join(
		srcPath,
		'popup-maker-easy-modal-importer-site'
	),
	'pum-admin-deprecated': path.join( srcPath, 'admin/deprecated' ),

	// Integrations
	// 'pum-integration-calderaforms': path.join(
	// 	srcPath,
	// 	'integration/calderaforms'
	// ),
	// 'pum-integration-contactform7': path.join(
	// 	srcPath,
	// 	'integration/contactform7'
	// ),
	// 'pum-integration-fluentforms': path.join(
	// 	srcPath,
	// 	'integration/fluentforms'
	// ),
	// 'pum-integration-formidableforms': path.join(
	// 	srcPath,
	// 	'integration/formidableforms'
	// ),
	// 'pum-integration-gravityforms': path.join(
	// 	srcPath,
	// 	'integration/gravityforms'
	// ),
	// 'pum-integration-mc4wp': path.join( srcPath, 'integration/mc4wp' ),
	// 'pum-integration-ninjaforms': path.join(
	// 	srcPath,
	// 	'integration/ninjaforms'
	// ),
	// 'pum-integration-wpforms': path.join( srcPath, 'integration/wpforms' ),
	// 'pum-integration-wsforms': path.join( srcPath, 'integration/wsforms' ),
	// Site
	site: path.join( srcPath, 'site' ),
};

const config = {
	...defaultConfig,
	entry: jsBuilds,
	output: {
		path: distPath,
		filename: '[name].js',
		// Don't clean the output directory since we're testing in place
		clean: false, // !IMPORTANT!
	},
	optimization: {
		...defaultConfig.optimization,
		minimize: isProduction,
		splitChunks: false,
		runtimeChunk: false,
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
			// Disable the default cleaner, it will empty `/dist/` which we don't output to in this config.
			// plugin.constructor.name !== 'CleanWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			requestToExternal( request ) {
				if ( request === 'jquery' ) {
					return 'jQuery';
				}
			},
			requestToHandle( request ) {
				if ( request === 'jquery' ) {
					return 'jquery';
				}
			},
		} ),
	],
};

module.exports = config;
