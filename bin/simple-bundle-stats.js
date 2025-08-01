#!/usr/bin/env node

/**
 * Simple Bundle Stats
 * 
 * Basic bundle size reporting for baseline measurements
 */

const fs = require('fs');
const path = require('path');

class SimpleBundleStats {
    constructor() {
        this.projectRoot = process.cwd();
        this.modernDistPath = path.join(this.projectRoot, 'dist/packages');
        this.legacyDistPath = path.join(this.projectRoot, 'dist/assets');
    }

    getFileSize(filePath) {
        if (!fs.existsSync(filePath)) return 0;
        const stats = fs.statSync(filePath);
        return Math.round(stats.size / 1024 * 100) / 100; // KB with 2 decimal places
    }

    getJSFiles(dir) {
        const files = [];
        if (!fs.existsSync(dir)) return files;

        const entries = fs.readdirSync(dir);
        entries.forEach(entry => {
            const fullPath = path.join(dir, entry);
            const stat = fs.statSync(fullPath);
            
            if (stat.isDirectory()) {
                files.push(...this.getJSFiles(fullPath));
            } else if (entry.endsWith('.js') && !entry.includes('.min.js') && !entry.includes('.hot-update.js')) {
                files.push(fullPath);
            }
        });

        return files;
    }

    getBasicStats() {
        let modernTotal = 0;
        let legacyTotal = 0;

        // Modern packages
        const modernFiles = this.getJSFiles(this.modernDistPath);
        modernFiles.forEach(file => {
            modernTotal += this.getFileSize(file);
        });

        // Legacy assets
        const legacyFiles = this.getJSFiles(this.legacyDistPath);
        legacyFiles.forEach(file => {
            legacyTotal += this.getFileSize(file);
        });

        return {
            modern: modernTotal,
            legacy: legacyTotal,
            total: modernTotal + legacyTotal,
            modernCount: modernFiles.length,
            legacyCount: legacyFiles.length
        };
    }
}

// CLI execution
if (require.main === module) {
    const stats = new SimpleBundleStats();
    const results = stats.getBasicStats();
    
    console.log(`Modern: ${results.modern} KB (${results.modernCount} files)`);
    console.log(`Legacy: ${results.legacy} KB (${results.legacyCount} files)`);
    console.log(`Total: ${results.total} KB`);
}

module.exports = SimpleBundleStats;