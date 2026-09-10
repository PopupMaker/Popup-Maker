const prettier = require( 'prettier' );

const readableAssets = {
	'site.css': 'site-readable.css',
	'site-rtl.css': 'site-rtl-readable.css',
};

/**
 * Emit readable copies of the production core stylesheets.
 *
 * Formatting at build time keeps CSS parsing out of WordPress requests while
 * ensuring the readable and minified files come from the same compiled asset.
 */
class ReadableCssAssetsWebpackPlugin {
	apply( compiler ) {
		compiler.hooks.thisCompilation.tap(
			'ReadableCssAssetsWebpackPlugin',
			( compilation ) => {
				compilation.hooks.processAssets.tapPromise(
					{
						name: 'ReadableCssAssetsWebpackPlugin',
						stage: compiler.webpack.Compilation
							.PROCESS_ASSETS_STAGE_SUMMARIZE,
					},
					async () => {
						for ( const [
							sourceName,
							readableName,
						] of Object.entries( readableAssets ) ) {
							const asset = compilation.getAsset( sourceName );

							if ( ! asset ) {
								continue;
							}

							const css = asset.source
								.source()
								.toString()
								.replace(
									/\n?\/\*# sourceMappingURL=.*?\*\/\s*$/,
									''
								);
							const readableCss = await prettier.format( css, {
								parser: 'css',
								tabWidth: 4,
								useTabs: true,
							} );
							const source =
								new compiler.webpack.sources.RawSource(
									readableCss
								);

							if ( compilation.getAsset( readableName ) ) {
								compilation.updateAsset( readableName, source );
							} else {
								compilation.emitAsset( readableName, source );
							}
						}
					}
				);
			}
		);
	}
}

module.exports = ReadableCssAssetsWebpackPlugin;
