# Dependency Extraction Webpack Plugin

Extends Wordpress [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/master/packages/dependency-extraction-webpack-plugin) to automatically include Popup Maker dependencies in addition to WordPress dependencies.

## Installation

Install the module

```bash
pnpm install @popup-maker/dependency-extraction-webpack-plugin --save-dev
```

## Usage

Use this as you would [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/master/packages/dependency-extraction-webpack-plugin). The API is exactly the same, except that Popup Maker packages are also handled automatically.

```js
// webpack.config.js
const PopupMakerDependencyExtractionWebpackPlugin = require( '@popup-maker/dependency-extraction-webpack-plugin' );

module.exports = {
 // …snip
 plugins: [ new PopupMakerDependencyExtractionWebpackPlugin() ],
};
```

Additional module requests on top of Wordpress [Dependency Extraction Webpack Plugin](https://github.com/WordPress/gutenberg/tree/master/packages/dependency-extraction-webpack-plugin) are:

| Request                        | Global                         | Script handle                 | Notes |
| ------------------------------ | ------------------------------ | ----------------------------- | ----- |
| `@popup-maker/*`           | `popupMaker['*']`          | `popup-maker-*`           |       |

### Options

An object can be passed to the constructor to customize the behavior, for example:

```js
module.exports = {
 plugins: [
  new PopupMakerDependencyExtractionWebpackPlugin( {
   bundledPackages: [ '@popup-maker/components' ],
  } ),
 ],
};
```

#### `bundledPackages`

- Type: array
- Default: []

A list of potential Popup Maker excluded packages, this will include the excluded package within the bundle (example above).

For more supported options see the original [dependency extraction plugin](https://github.com/WordPress/gutenberg/blob/trunk/packages/dependency-extraction-webpack-plugin/README.md#options).
