#!/usr/bin/env node

/**
 * Webpack Cache Manager
 * 
 * Utility for managing webpack persistent cache directories and performance.
 * Provides cache statistics, cleanup, and optimization commands.
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class WebpackCacheManager {
    constructor() {
        this.projectRoot = process.cwd();
        this.modernCacheDir = path.join(this.projectRoot, '.webpack-cache');
        this.legacyCacheDir = path.join(this.projectRoot, '.webpack-cache-legacy');
    }

    /**
     * Get cache directory size in MB
     */
    getCacheSize(dir) {
        if (!fs.existsSync(dir)) {
            return 0;
        }

        try {
            const result = execSync(`du -sm "${dir}"`, { encoding: 'utf8' });
            return parseInt(result.split('\t')[0]);
        } catch (error) {
            return 0;
        }
    }

    /**
     * Get cache directory file count
     */
    getCacheFileCount(dir) {
        if (!fs.existsSync(dir)) {
            return 0;
        }

        try {
            const result = execSync(`find "${dir}" -type f | wc -l`, { encoding: 'utf8' });
            return parseInt(result.trim());
        } catch (error) {
            return 0;
        }
    }

    /**
     * Show cache statistics
     */
    showStats() {
        console.log('📊 Webpack Cache Statistics\n');

        const modernSize = this.getCacheSize(this.modernCacheDir);
        const modernFiles = this.getCacheFileCount(this.modernCacheDir);
        const legacySize = this.getCacheSize(this.legacyCacheDir);
        const legacyFiles = this.getCacheFileCount(this.legacyCacheDir);
        const totalSize = modernSize + legacySize;

        console.log(`Modern Packages Cache:`);
        console.log(`  📁 Size: ${modernSize} MB`);
        console.log(`  📄 Files: ${modernFiles.toLocaleString()}`);
        console.log(`  📍 Path: ${path.relative(this.projectRoot, this.modernCacheDir)}`);

        console.log(`\nLegacy Assets Cache:`);
        console.log(`  📁 Size: ${legacySize} MB`);
        console.log(`  📄 Files: ${legacyFiles.toLocaleString()}`);
        console.log(`  📍 Path: ${path.relative(this.projectRoot, this.legacyCacheDir)}`);

        console.log(`\nTotal Cache Size: ${totalSize} MB`);

        if (totalSize > 0) {
            const savings = this.estimateTimeSavings(totalSize);
            console.log(`⚡ Estimated build time savings: ${savings}`);
        }
    }

    /**
     * Estimate time savings from cache
     */
    estimateTimeSavings(sizeMB) {
        if (sizeMB < 50) return '10-30%';
        if (sizeMB < 100) return '30-50%';
        if (sizeMB < 200) return '50-70%';
        return '70-80%';
    }

    /**
     * Clean cache directories
     */
    clean(type = 'all') {
        console.log('🧹 Cleaning webpack cache...\n');

        const cleanDir = (dir, name) => {
            if (fs.existsSync(dir)) {
                const sizeBefore = this.getCacheSize(dir);
                console.log(`Removing ${name} cache (${sizeBefore} MB)...`);
                
                try {
                    fs.rmSync(dir, { recursive: true, force: true });
                    console.log(`✅ ${name} cache cleaned`);
                } catch (error) {
                    console.error(`❌ Failed to clean ${name} cache:`, error.message);
                }
            } else {
                console.log(`ℹ️  ${name} cache doesn't exist`);
            }
        };

        if (type === 'all' || type === 'modern') {
            cleanDir(this.modernCacheDir, 'Modern packages');
        }

        if (type === 'all' || type === 'legacy') {
            cleanDir(this.legacyCacheDir, 'Legacy assets');
        }

        console.log('\n🎉 Cache cleanup completed!');
    }

    /**
     * Validate cache health
     */
    validate() {
        console.log('🔍 Validating webpack cache health...\n');

        const validateDir = (dir, name) => {
            if (!fs.existsSync(dir)) {
                console.log(`❌ ${name} cache directory missing`);
                return false;
            }

            try {
                // Check if directory is readable/writable
                fs.accessSync(dir, fs.constants.R_OK | fs.constants.W_OK);
                console.log(`✅ ${name} cache is accessible`);

                // Check for cache corruption indicators
                const files = this.getCacheFileCount(dir);
                if (files === 0) {
                    console.log(`⚠️  ${name} cache is empty`);
                } else {
                    console.log(`✅ ${name} cache contains ${files.toLocaleString()} files`);
                }

                return true;
            } catch (error) {
                console.log(`❌ ${name} cache has permission issues:`, error.message);
                return false;
            }
        };

        const modernValid = validateDir(this.modernCacheDir, 'Modern packages');
        const legacyValid = validateDir(this.legacyCacheDir, 'Legacy assets');

        if (modernValid && legacyValid) {
            console.log('\n🎉 Cache validation passed!');
        } else {
            console.log('\n⚠️  Cache validation found issues. Consider running --clean');
        }
    }

    /**
     * Show help information
     */
    showHelp() {
        console.log(`
Webpack Cache Manager

Usage: node bin/webpack-cache-manager.js [command]

Commands:
  stats      Show cache statistics and performance info
  clean      Clean all cache directories
  clean-modern   Clean only modern packages cache
  clean-legacy   Clean only legacy assets cache
  validate   Validate cache health and accessibility
  help       Show this help message

Examples:
  node bin/webpack-cache-manager.js stats
  node bin/webpack-cache-manager.js clean
  node bin/webpack-cache-manager.js validate
        `);
    }
}

// CLI handling
if (require.main === module) {
    const manager = new WebpackCacheManager();
    const command = process.argv[2];

    switch (command) {
        case 'stats':
            manager.showStats();
            break;
        case 'clean':
            manager.clean('all');
            break;
        case 'clean-modern':
            manager.clean('modern');
            break;
        case 'clean-legacy':
            manager.clean('legacy');
            break;
        case 'validate':
            manager.validate();
            break;
        case 'help':
        case '--help':
            manager.showHelp();
            break;
        default:
            if (command) {
                console.error(`Unknown command: ${command}\n`);
            }
            manager.showHelp();
            process.exit(1);
    }
}

module.exports = WebpackCacheManager;