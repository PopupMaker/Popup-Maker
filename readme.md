# Popup Maker

Everything you need to create unique popup user experiences. Insert forms & other content from your favorite plugins to create custom responsive popups.

Feel free to browse the code and make suggestions/requests. Thanks!

## Support

This is a developer's portal for Popup Maker and **should not** be used for support. Please [create a support ticket here](https://wordpress.org/support/plugin/popup-maker/).

## Getting Started

### Downloading And Using As A Plugin

To use this plugin, you must download from [the releases page](https://github.com/PopupMaker/Popup-Maker/releases). With each release, there is an attached zip, named something similar to `popup-maker_v1.10.2.zip
`. Download the appropriate zip file and then upload into your WordPress site as a normal plugin.

Once installed and activated, Go to wp-admin > Popup Maker > Add Popup to create your first popup.

### Getting Set Up For Development

In order to begin development on Popup Maker, please first refer to our [Contributing Guidelines](https://github.com/PopupMaker/Popup-Maker/blob/master/.github/CONTRIBUTING.md) for our coding style and commit style information.

Then, visit our [GitHub repo wiki](https://github.com/PopupMaker/Popup-Maker/wiki) for basic concepts of how Popup Maker works and is structured.

Then, move on to our [Setting up your local environment](https://github.com/PopupMaker/Popup-Maker/wiki/Setting-up-your-local-environment) wiki page.

## Built With

-   [SASS](https://sass-lang.com) - The CSS pre-processor we use. We use the SCSS syntax.
-   [jQuery](https://jquery.com) - A fast, small, and feature-rich JavaScript library
-   [JSON for JS](https://github.com/douglascrockford/JSON-js) - Creates a JSON property in the global object, if there isn't already one
-   [mobile-detect.js](https://github.com/hgoebl/mobile-detect.js) - Detect the device by comparing patterns against a given User-Agent string

## Developer Tools

### Release Preparation

The `bin/prepare-release.js` script prepares a reviewed release PR:

```bash
# Patch release (1.21.4 → 1.21.5)
pnpm run prepare-release start

# Minor release (1.21.4 → 1.22.0)
pnpm run prepare-release start --minor

# Specific version
pnpm run prepare-release start -- 2.1.0

# Test without changes
pnpm run prepare-release start --dry-run
```

**Features:**

-   🔄 Automatic version increments or specific versions
-   🌿 Creates a `release/X.Y.Z` branch for review
-   📝 Updates versions in all files and changelog
-   🔨 Builds release assets
-   🚀 Opens the release PR; maintainer authorization and merge perform publication

See `bin/README.md` for complete documentation.

## Deployment

This plugin is hosted on WordPress.org SVN. A merged `release/X.Y.Z` PR authorized
by a current maintainer approval or an authorized maintainer merge publishes the
canonical GitHub Actions artifact to GitHub Releases, EDD, Google Drive, and
WordPress.org. Authorized same-repository readme/assets-only PRs use a separate
narrow SVN sync. Direct pushes to `master` do not publish.

## Contributing

Community made feature requests, patches, localizations, bug reports, and contributions are always welcome and are crucial to ensure Popup Maker continues to grow.

When contributing please ensure you follow our [Contributor Guidelines](https://github.com/PopupMaker/Popup-Maker/blob/master/.github/CONTRIBUTING.md) so that we can keep on top of things.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see [the releases in this repository](https://github.com/PopupMaker/Popup-Maker/releases).

## Developers

-   Daniel Iser - Lead Developer

See also [the list of contributors](https://github.com/PopupMaker/Popup-Maker/graphs/contributors) who participated in this project.

## License

This project is licensed under the GPLv2 License.
