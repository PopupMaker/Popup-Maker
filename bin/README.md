# Plugin Release Tools

This directory contains tools for managing WordPress plugin releases:

- **prepare-release.js** - Automates the complete release workflow with git flow
- **build-release.js** - Unified build script for creating release packages

## Features

-   **Unified Process**: Single script handles the entire release workflow
-   **Configurable**: Supports multiple configuration options and flags
-   **Cross-Plugin**: Can be copied and used across all your plugins
-   **Smart Detection**: Automatically reads plugin name and version from `package.json`
-   **Flexible File Copying**: Uses the `files` array from `package.json` for distribution
-   **Error Handling**: Comprehensive error handling and logging
-   **Production Ready**: Handles Composer and npm production builds

## Release Preparation Script

### Quick Start

The **prepare-release.js** script automates the complete release workflow including version management, changelog updates, and git flow integration.

```bash
# Patch release (1.21.4 → 1.21.5)
node bin/prepare-release.js

# Minor release (1.21.4 → 1.22.0)
node bin/prepare-release.js --minor

# Major release (1.21.4 → 2.0.0)
node bin/prepare-release.js --major

# Specific version
node bin/prepare-release.js 2.1.0

# Test without changes
node bin/prepare-release.js --dry-run

# See all options
node bin/prepare-release.js --help
```

### What It Does

1. ✅ Validates git status and git flow availability
2. 🌿 Creates git flow release branch
3. 📝 Updates versions in all files (via `update-versions.js`)
4. 📋 Updates changelog (via `update-changelog.js`)
5. 📦 Updates `package-lock.json`
6. 🔨 Builds release assets (`npm run release`)
7. 💾 Commits changes with standardized message
8. 🏁 Finishes git flow release with tag
9. 🚀 Offers to push changes

### Options

- `[version]` - Specific version number (e.g., `1.21.5`)
- `--major` - Increment major version (X+1.0.0)
- `--minor` - Increment minor version (X.Y+1.0)
- `--patch` - Increment patch version (X.Y.Z+1) [default]
- `--dry-run` - Show what would be done without making changes
- `--no-build` - Skip the release build step
- `--auto` - Skip all confirmations (dangerous!)
- `--help` - Show detailed help

## Build Release Script

### Basic Usage

```bash
# From your plugin directory
npm run release

# Or run directly
node bin/build-release.js
```

### Advanced Usage

```bash
# Build with custom plugin name
node bin/build-release.js --plugin-name my-custom-plugin

# Custom zip file name
node bin/build-release.js --zip-name my-plugin-v1.0.0.zip

# Output to specific directory
node bin/build-release.js --output-dir ./releases

# Skip certain steps for testing
node bin/build-release.js --skip-composer --skip-npm

# Keep build directory for debugging
node bin/build-release.js --keep-build

# Get help
node bin/build-release.js --help
```

## Installation Across Plugins

### Option 1: Copy the Script

Copy `bin/build-release.js` to each plugin's `bin/` directory:

```bash
# From another plugin directory
cp ../popup-maker/bin/build-release.js ./bin/
chmod +x ./bin/build-release.js
```

### Option 2: Shared Script Location

Place the script in a shared location and reference it:

```bash
# Create a shared tools directory
mkdir -p ~/dev-tools/wordpress
cp bin/build-release.js ~/dev-tools/wordpress/

# From any plugin directory
node ~/dev-tools/wordpress/build-release.js --project-root $(pwd)
```

### Option 3: npm Package (Recommended)

Create a shared npm package for your organization:

```json
// In a shared package
{
  "name": "@your-org/wp-plugin-builder",
  "bin": {
    "wp-plugin-release": "./bin/build-release.js"
  }
}

// Then in each plugin's package.json
{
  "devDependencies": {
    "@your-org/wp-plugin-builder": "^1.0.0"
  },
  "scripts": {
    "release": "wp-plugin-release"
  }
}
```

## Package.json Scripts Update

Update your plugin's `package.json` scripts to use the unified builder:

```json
{
	"scripts": {
		"release": "node bin/build-release.js",
		"release:build": "node bin/build-release.js --skip-composer --skip-npm",
		"release:clean": "rm -rf popup-maker/ && rm -rf build/"
	}
}
```

## Configuration

### Files Array

The script uses the `files` array in your `package.json` to determine what gets included in the release. Make sure this is properly configured:

```json
{
	"files": [
		"assets/**/index.php",
		"assets/css/*.css",
		"assets/js/*.js",
		"classes/**/*",
		"includes/**/*",
		"languages/**/*",
		"templates/**/*",
		"vendor-prefixed/**/*",
		"readme.txt",
		"*.php"
	]
}
```

### Build Scripts

The script looks for these npm scripts in order:

1. `build:production` (preferred)
2. `build` (with NODE_ENV=production)

Make sure at least one of these exists in your `package.json`.

## What the Script Does

1. **Clean**: Removes any existing build artifacts and old zip files
2. **Dependencies**: Runs `composer install` with production flags
3. **Build**: Runs your npm build script for production
4. **Copy**: Copies files based on the `files` array in package.json
5. **Zip**: Creates a versioned zip file named `{plugin-name}_{version}.zip` (customizable)
6. **Cleanup**: Removes temporary build directories

## Zip File Naming

By default, the script creates zip files with the format: `{plugin-name}_{version}.zip`

You can customize this for special cases:

**Use Cases for Custom Zip Names:**
- **Beta/RC releases**: `--zip-name my-plugin-v1.2.0-beta1.zip`
- **Client-specific builds**: `--zip-name my-plugin-client-custom.zip`
- **Distribution channels**: `--zip-name my-plugin-wordpress-org.zip`
- **Build variants**: `--zip-name my-plugin-lite-v1.0.0.zip`

## Example Workflows

### Standard Release

```bash
# Full release process
npm run release
```

### Development Testing

```bash
# Build without dependencies (faster for testing)
node bin/build-release.js --skip-composer --skip-npm --keep-build
```

### CI/CD Pipeline

```bash
# In your CI/CD script
node bin/build-release.js --output-dir ./dist --plugin-name ${CI_PROJECT_NAME}
```

### Custom Plugin Names and Zip Files

```bash
# Override the plugin name from package.json
node bin/build-release.js --plugin-name popup-maker-pro

# Custom zip file name for special releases
node bin/build-release.js --zip-name popup-maker-holiday-special.zip

# Combine custom plugin name with custom zip name
node bin/build-release.js --plugin-name popup-maker-pro --zip-name pmp-v2.1.0-release.zip
```

## Troubleshooting

### Common Issues

1. **"package.json not found"**: Make sure you're running from the plugin root directory
2. **"composer install failed"**: Ensure Composer is installed and accessible
3. **"npm build failed"**: Check that your build scripts are working locally
4. **"zip command not found"**: Install zip utility (`apt-get install zip` or `brew install zip`)

### Debug Mode

Use `--keep-build` to inspect what files are being copied:

```bash
node bin/build-release.js --keep-build
ls -la build/  # Check the build contents
```

## Cross-Plugin Standardization

To standardize across all your plugins:

1. Ensure all plugins have consistent `package.json` structure
2. Use the same `files` array patterns
3. Standardize npm script names (`build:production`)
4. Use the same Composer configuration
5. Copy this script to all plugin repositories

## Integration with Existing Workflows

### Replacing Current Scripts

If you have existing release scripts, you can gradually migrate:

```json
{
	"scripts": {
		"release": "node bin/build-release.js",
		"release:old": "npm run release:clean && npm run release:build && npm run release:zip && npm run release:clean",
		"release:build": "node bin/build-release.js --skip-composer --skip-npm"
	}
}
```

### Git Hooks

Add to your `package.json` for automatic tagging:

```json
{
	"scripts": {
		"release": "node bin/build-release.js && git tag v$npm_package_version && git push --tags"
	}
}
```
