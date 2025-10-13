#!/usr/bin/env node

/**
 * Bump and Publish Packages Script
 *
 * This script bumps all @popup-maker packages to the next minor version,
 * commits the changes, and publishes them to npm.
 *
 * Usage:
 *   node bin/bump-and-publish-packages.js [--dry-run] [--version-type=minor|patch|major]
 *
 * Options:
 *   --dry-run        Show what would be done without making changes
 *   --version-type   Type of version bump (default: minor)
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const minimist = require('minimist');

const argv = minimist(process.argv.slice(2));
const dryRun = argv['dry-run'];
const versionType = argv['version-type'] || 'minor';

// Colors for console output
const colors = {
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
    blue: '\x1b[34m',
    reset: '\x1b[0m',
    bold: '\x1b[1m'
};

function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

function error(message) {
    log(message, 'red');
}

function success(message) {
    log(message, 'green');
}

function warning(message) {
    log(message, 'yellow');
}

function info(message) {
    log(message, 'blue');
}

/**
 * Get all package directories
 */
function getPackageDirectories() {
    const packagesDir = path.join(__dirname, '..', 'packages');

    if (!fs.existsSync(packagesDir)) {
        error('Packages directory not found!');
        process.exit(1);
    }

    return fs.readdirSync(packagesDir)
        .map(dir => path.join(packagesDir, dir))
        .filter(dir => {
            const packageJsonPath = path.join(dir, 'package.json');
            return fs.statSync(dir).isDirectory() && fs.existsSync(packageJsonPath);
        });
}

/**
 * Get package info from package.json
 */
function getPackageInfo(packageDir) {
    const packageJsonPath = path.join(packageDir, 'package.json');
    const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));

    return {
        path: packageDir,
        packageJsonPath,
        packageJson,
        name: packageJson.name,
        version: packageJson.version,
        isPrivate: packageJson.private
    };
}

/**
 * Bump version based on type
 */
function bumpVersion(currentVersion, type = 'minor') {
    const [major, minor, patch] = currentVersion.split('.').map(Number);

    switch (type) {
        case 'major':
            return `${major + 1}.0.0`;
        case 'minor':
            return `${major}.${minor + 1}.0`;
        case 'patch':
            return `${major}.${minor}.${patch + 1}`;
        default:
            throw new Error(`Invalid version type: ${type}`);
    }
}

/**
 * Update package.json with new version
 */
function updatePackageJson(packageInfo, newVersion) {
    const updatedPackageJson = {
        ...packageInfo.packageJson,
        version: newVersion
    };

    if (!dryRun) {
        fs.writeFileSync(
            packageInfo.packageJsonPath,
            JSON.stringify(updatedPackageJson, null, '\t') + '\n'
        );
    }

    return updatedPackageJson;
}

/**
 * Execute shell command
 */
function execCommand(command, options = {}) {
    if (dryRun) {
        info(`[DRY RUN] Would execute: ${command}`);
        return '';
    }

    try {
        return execSync(command, {
            encoding: 'utf8',
            stdio: options.silent ? 'pipe' : 'inherit',
            ...options
        });
    } catch (error) {
        throw new Error(`Command failed: ${command}\n${error.message}`);
    }
}

/**
 * Check if we're in a git repository and it's clean
 */
function checkGitStatus() {
    try {
        // Check if we're in a git repo
        execCommand('git rev-parse --is-inside-work-tree', { silent: true });

        // Check if working directory is clean
        const status = execCommand('git status --porcelain', { silent: true });
        if (status.trim() && !dryRun) {
            error('Working directory is not clean. Please commit or stash changes first.');
            process.exit(1);
        }
    } catch (error) {
        error('Not in a git repository or git not available.');
        process.exit(1);
    }
}

/**
 * Check if npm is logged in
 */
function checkNpmAuth() {
    try {
        const whoami = execCommand('npm whoami', { silent: true });
        info(`Logged in to npm as: ${whoami.trim()}`);
    } catch (error) {
        error('Not logged in to npm. Please run "npm login" first.');
        process.exit(1);
    }
}

/**
 * Check if there are unreleased changes in CHANGELOG.md
 * For patch releases, we only bump if there are actual changes to release
 */
function hasUnreleasedChanges() {
    const changelogPath = path.join(__dirname, '..', 'CHANGELOG.md');

    if (!fs.existsSync(changelogPath)) {
        warning('CHANGELOG.md not found - proceeding with patch release');
        return true;
    }

    try {
        const changelog = fs.readFileSync(changelogPath, 'utf8');

        // Look for "## Unreleased" section with actual content
        const unreleasedMatch = changelog.match(/## Unreleased\s*(.*?)(?=##|$)/s);

        if (!unreleasedMatch) {
            return false;
        }

        const unreleasedSection = unreleasedMatch[1].trim();

        // Check if there's actual content (not just empty lines or section headers)
        const hasContent = unreleasedSection
            .split('\n')
            .some(line => {
                const trimmed = line.trim();
                return trimmed &&
                       !trimmed.startsWith('**') && // Skip section headers like **Fixes**
                       !trimmed.startsWith('##') && // Skip any nested headers
                       trimmed !== '';
            });

        return hasContent;
    } catch (error) {
        warning(`Error reading CHANGELOG.md: ${error.message} - proceeding with release`);
        return true;
    }
}

/**
 * Main execution function
 */
function main() {
    log(`${colors.bold}🚀 Popup Maker Package Bump & Publish${colors.reset}`);
    log(`Version type: ${versionType}`);

    if (dryRun) {
        warning('Running in DRY RUN mode - no changes will be made');
    }

    // Pre-flight checks
    checkGitStatus();
    if (!dryRun) {
        checkNpmAuth();
    }

    // Get all packages
    const packageDirs = getPackageDirectories();
    const packages = packageDirs.map(getPackageInfo);

    log(`\nFound ${packages.length} packages:`);
    packages.forEach(pkg => {
        log(`  • ${pkg.name} v${pkg.version}${pkg.isPrivate ? ' (private)' : ''}`);
    });

    // Filter out private packages for publishing
    const publishablePackages = packages.filter(pkg => !pkg.isPrivate);

    if (publishablePackages.length === 0) {
        warning('No publishable packages found!');
        return;
    }

    // For patch releases, check if there are unreleased changes
    if (versionType === 'patch') {
        const hasChanges = hasUnreleasedChanges();

        if (!hasChanges) {
            warning('📄 No unreleased changes found in CHANGELOG.md');
            warning('🚫 Patch releases require unreleased changes to justify the release');
            log('\n💡 Patch releases are for quick fixes that need to go out immediately.');
            log('   For planned releases, use minor or major version bumps instead.');
            log('\n   To proceed anyway, use: node bin/bump-and-publish-packages.js --version-type=minor');
            return;
        }

        info('✅ Found unreleased changes in CHANGELOG.md - proceeding with patch release');
    }

    log(`\n📦 Will bump and publish ${publishablePackages.length} packages:`);

    // Bump versions
    const updates = [];
    publishablePackages.forEach(pkg => {
        const newVersion = bumpVersion(pkg.version, versionType);
        updates.push({
            ...pkg,
            newVersion,
            updatedPackageJson: updatePackageJson(pkg, newVersion)
        });

        log(`  • ${pkg.name}: ${pkg.version} → ${newVersion}`);
    });

    if (dryRun) {
        log('\n✅ Dry run complete - no changes made');
        return;
    }

    // Commit changes
    log('\n📝 Committing changes...');
    const versionList = updates.map(u => `${u.name}@${u.newVersion}`).join(', ');
    const commitMessage = `chore: bump package versions to ${versionType}\n\n${versionList}`;

    execCommand('git add packages/*/package.json');
    execCommand(`git commit -m "${commitMessage}"`);

    success('✅ Changes committed');

    // Publish packages
    log('\n🚀 Publishing packages to npm...');

    let publishedCount = 0;
    let failedPackages = [];

    for (const pkg of updates) {
        try {
            info(`Publishing ${pkg.name}@${pkg.newVersion}...`);

            // Change to package directory for publishing
            process.chdir(pkg.path);
            execCommand('npm publish --access public');

            success(`✅ Published ${pkg.name}@${pkg.newVersion}`);
            publishedCount++;

        } catch (error) {
            error(`❌ Failed to publish ${pkg.name}: ${error.message}`);
            failedPackages.push(pkg.name);
        }
    }

    // Return to root directory
    process.chdir(path.join(__dirname, '..'));

    // Summary
    log(`\n${colors.bold}📋 Summary:${colors.reset}`);
    success(`✅ ${publishedCount} packages published successfully`);

    if (failedPackages.length > 0) {
        error(`❌ ${failedPackages.length} packages failed:`);
        failedPackages.forEach(name => error(`  • ${name}`));

        // Create a follow-up script for failed packages
        const retryScript = `#!/bin/bash
# Retry publishing failed packages
${failedPackages.map(name => {
            const pkg = updates.find(u => u.name === name);
            return `echo "Publishing ${name}..."
cd packages/${path.basename(pkg.path)}
npm publish --access public
cd ../..`;
        }).join('\n')}
`;

        fs.writeFileSync('./retry-publish.sh', retryScript);
        execCommand('chmod +x ./retry-publish.sh');
        info('💡 Created retry-publish.sh for failed packages');
    }

    if (publishedCount > 0) {
        success(`\n🎉 Package release complete! ${publishedCount} packages published.`);

        // Tag the release
        const tagName = `packages-${versionType}-${new Date().toISOString().split('T')[0]}`;
        execCommand(`git tag -a ${tagName} -m "Package ${versionType} version bump"`);
        info(`📌 Created git tag: ${tagName}`);

        log(`\n💡 Next steps:`);
        log(`  • Push changes: git push origin main --tags`);
        log(`  • Update Pro plugins to use new versions`);
    }
}

// Validate version type
if (!['major', 'minor', 'patch'].includes(versionType)) {
    error(`Invalid version type: ${versionType}. Must be major, minor, or patch.`);
    process.exit(1);
}

// Run the script
try {
    main();
} catch (error) {
    error(`Script failed: ${error.message}`);
    process.exit(1);
}