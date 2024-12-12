/** @type { import('@storybook/react-webpack5').StorybookConfig } */
const config = {
	framework: '@storybook/react-webpack5',
	stories: [ '../packages/*/src/**/*.stories.@(js|jsx|ts|tsx)' ],
	addons: [
		'@storybook/addon-essentials',
		'@storybook/addon-webpack5-compiler-babel',
	],
	typescript: {
		check: false,
		reactDocgen: 'react-docgen-typescript',
	},
};

export default config;
