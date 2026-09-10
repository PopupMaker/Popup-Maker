import { createRequire } from 'node:module';
/** @type { import('@storybook/react-webpack5').StorybookConfig } */
import { dirname, join } from 'path';

const require = createRequire( import.meta.url );

const config = {
	stories: [ '../packages/*/src/**/*.stories.@(js|jsx|ts|tsx)' ],

	addons: [
		getAbsolutePath( '@storybook/addon-webpack5-compiler-babel' ),
		{
			name: getAbsolutePath( '@storybook/addon-styling-webpack' ),
			options: {
				rules: [
					{
						test: /\.s[ac]ss$/i,
						use: [
							'style-loader',
							'css-loader',
							{
								loader: 'sass-loader',
								options: {
									implementation: import.meta.resolve(
										'sass'
									),
								},
							},
						],
					},
				],
			},
		},
		getAbsolutePath( '@storybook/addon-docs' ),
	],

	framework: {
		name: getAbsolutePath( '@storybook/react-webpack5' ),
		options: {},
	},
};

export default config;

function getAbsolutePath( value ) {
	return dirname( require.resolve( join( value, 'package.json' ) ) );
}
