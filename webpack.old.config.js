const path = require( 'path' );
const CustomTemplatedPathPlugin = require( '@popup-maker/custom-templated-path-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@popup-maker/dependency-extraction-webpack-plugin' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
// const UnminifiedWebpackPlugin = require( 'unminified-webpack-plugin' );

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const NODE_ENV = process.env.NODE_ENV || 'development';

const srcPath = path.join( process.cwd(), 'assets/js/src' );
const distPath = path.join( process.cwd(), 'dist/assets' );

const jsBuilds = {
	// Site
	site: `${ srcPath }/site/index.js`,
	// Admin
	'admin-batch': `${ srcPath }/admin/batch/index.js`,
	'admin-general': `${ srcPath }/admin/general/index.js`,
	'admin-marketing': `${ srcPath }/admin/marketing/index.js`,
	'admin-pointer': `${ srcPath }/admin/pointer/index.js`,
	'admin-popup-editor': `${ srcPath }/admin/popup-editor/index.js`,
	'admin-settings-page': `${ srcPath }/admin/settings-page/index.js`,
	'admin-shortcode-ui': `${ srcPath }/admin/shortcode-ui/index.js`,
	'admin-theme-editor': `${ srcPath }/admin/theme-editor/index.js`,
	'mce-buttons': `${ srcPath }/mce-buttons.js`,
	'popup-maker-easy-modal-importer-site': `${ srcPath }/popup-maker-easy-modal-importer-site.js`,
	'admin-deprecated': `${ srcPath }/admin/deprecated.js`,

	// TODO These are currently outputting empty .js files.
	'admin-editor-styles': `${ srcPath }/admin/editor-styles.scss`,
	'admin-extensions-page': `${ srcPath }/admin/extensions-page.scss`,
	'admin-support-page': `${ srcPath }/admin/support-page.scss`,

	// Integrations
	// 'pum-integration-calderaforms': `${ srcPath }/integration/calderaforms/index.js`,
	// 'pum-integration-contactform7': `${ srcPath }/integration/contactform7/index.js`,
	// 'pum-integration-fluentforms': `${ srcPath }/integration/fluentforms/index.js`,
	// 'pum-integration-formidableforms': `${ srcPath }/integration/formidableforms/index.js`,
	// 'pum-integration-gravityforms': `${ srcPath }/integration/gravityforms/index.js`,
	// 'pum-integration-mc4wp': `${ srcPath }/integration/mc4wp/index.js`,
	// 'pum-integration-ninjaforms': `${ srcPath }/integration/ninjaforms/index.js`,
	// 'pum-integration-wpforms': `${ srcPath }/integration/wpforms/index.js`,
	// 'pum-integration-wsforms': `${ srcPath }/integration/wsforms/index.js`,
};

const config = {
	...defaultConfig,
	entry: Object.entries( jsBuilds ).reduce(
		( entry, [ packageName, packagePath ] ) => {
			entry[ packageName ] = packagePath;
			return entry;
		},
		{}
	),
	output: {
		path: distPath,
		filename: '[name].js',
		assetModuleFilename: '../images/[name][ext]',
		publicPath: '/wp-content/plugins/popup-maker/dist/assets/',
		// Don't clean the output directory since we're testing in place
		clean: false, // !IMPORTANT!
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
		alias: {
			...defaultConfig.resolve.alias,
			assets: path.resolve( __dirname, 'assets' ),
		},
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
						test: /\.(bmp|png|jpe?g|gif|webp|svg)$/i,
						generator: {
							filename: '../images/[name].[hash:8][ext]',
						},
					};
				}
				return rule;
			} ),
		],
	},

	optimization: {
		...defaultConfig.optimization,
		minimize: NODE_ENV !== 'development',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
			// Disable the default cleaner, it will empty `/dist/` which we don't output to in this config.
			// plugin.constructor.name !== 'CleanWebpackPlugin'
		),
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: './node_modules/mobile-detect/mobile-detect.min.js',
					to: path.join( distPath, 'vendor', 'mobile-detect.min.js' ),
				},
				{
					from: './node_modules/iframe-resizer/js/iframeResizer.min.js',
					to: path.join( distPath, 'vendor', 'iframeResizer.min.js' ),
				},
			],
		} ),
		// new UnminifiedWebpackPlugin(),
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
			// combineAssets: true,
			// combinedOutputFile: '../plugin-assets.php',
		} ),
	],
	optimization: {
		...defaultConfig.optimization,
		minimize: NODE_ENV !== 'development',
	},
};

module.exports = config;
