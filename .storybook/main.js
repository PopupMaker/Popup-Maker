/** @type { import('@storybook/react-webpack5').StorybookConfig } */
import path from 'path';

const config = {
	stories: [ '../packages/*/src/**/*.stories.@(js|jsx|ts|tsx)' ],
	addons: [
		'@storybook/addon-webpack5-compiler-babel',
		'@storybook/addon-essentials',
		'@storybook/preset-scss',
		'@storybook/addon-styling-webpack',
	],
	framework: {
		name: '@storybook/react-webpack5',
		options: {},
	},
	docs: {
		autodocs: true,
	},
};

export default config;
