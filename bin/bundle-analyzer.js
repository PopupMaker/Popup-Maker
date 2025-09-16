#!/usr/bin/env node

/**
 * Bundle Analyzer
 * 
 * Utility for analyzing webpack bundle sizes and optimization opportunities.
 * Provides detailed bundle analysis and size reporting.
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class BundleAnalyzer {
    constructor() {
        this.projectRoot = process.cwd();
        this.distPath = path.join(this.projectRoot, 'dist');
        this.modernDistPath = path.join(this.distPath, 'packages');
        this.legacyDistPath = path.join(this.distPath, 'assets');
    }

    /**
     * Get file size in KB
     */
    getFileSize(filePath) {
        if (!fs.existsSync(filePath)) {
            return 0;
        }
        
        const stats = fs.statSync(filePath);
        return Math.round(stats.size / 1024 * 100) / 100; // KB with 2 decimal places
    }

    /**
     * Get gzipped size
     */
    getGzippedSize(filePath) {
        if (!fs.existsSync(filePath)) {
            return 0;
        }

        try {
            const result = execSync(`gzip -c "${filePath}" | wc -c`, { encoding: 'utf8' });
            return Math.round(parseInt(result.trim()) / 1024 * 100) / 100;
        } catch (error) {
            return 0;
        }
    }

    /**
     * Analyze bundle sizes
     */
    analyzeBundles() {
        console.log('📊 Bundle Size Analysis\n');

        let modernTotal = 0;
        let modernGzippedTotal = 0;
        let legacyTotal = 0;
        let legacyGzippedTotal = 0;

        // Modern packages analysis
        console.log('🚀 Modern Packages (React/TypeScript):');
        if (fs.existsSync(this.modernDistPath)) {
            const modernFiles = this.getJSFiles(this.modernDistPath);

            modernFiles.forEach(file => {
                const size = this.getFileSize(file);
                const gzipped = this.getGzippedSize(file);
                const name = path.relative(this.modernDistPath, file);
                
                console.log(`  📦 ${name}: ${size} KB (${gzipped} KB gzipped)`);
                modernTotal += size;
                modernGzippedTotal += gzipped;
            });

            console.log(`  📊 Total: ${modernTotal} KB (${modernGzippedTotal} KB gzipped)\n`);
        } else {
            console.log('  ❌ No modern packages found\n');
        }

        // Legacy assets analysis
        console.log('🏗️ Legacy Assets (jQuery/Vanilla JS):');
        if (fs.existsSync(this.legacyDistPath)) {
            const legacyFiles = this.getJSFiles(this.legacyDistPath);

            legacyFiles.forEach(file => {
                const size = this.getFileSize(file);
                const gzipped = this.getGzippedSize(file);
                const name = path.relative(this.legacyDistPath, file);
                
                console.log(`  📦 ${name}: ${size} KB (${gzipped} KB gzipped)`);
                legacyTotal += size;
                legacyGzippedTotal += gzipped;
            });

            console.log(`  📊 Total: ${legacyTotal} KB (${legacyGzippedTotal} KB gzipped)\n`);
        } else {
            console.log('  ❌ No legacy assets found\n');
        }

        // Combined totals
        const combinedTotal = modernTotal + legacyTotal;
        const combinedGzippedTotal = modernGzippedTotal + legacyGzippedTotal;
        
        console.log(`🎯 Combined Bundle Size: ${combinedTotal} KB (${combinedGzippedTotal} KB gzipped)\n`);
    }

    /**
     * Get all JS files from directory
     */
    getJSFiles(dir) {
        const files = [];
        
        if (!fs.existsSync(dir)) {
            return files;
        }

        const entries = fs.readdirSync(dir);
        
        entries.forEach(entry => {
            const fullPath = path.join(dir, entry);
            const stat = fs.statSync(fullPath);
            
            if (stat.isDirectory()) {
                // Recursively get files from subdirectories
                files.push(...this.getJSFiles(fullPath));
            } else if (entry.endsWith('.js') && !entry.includes('.min.js') && !entry.includes('.hot-update.js')) {
                files.push(fullPath);
            }
        });

        return files;
    }

    /**
     * Analyze largest bundles for optimization opportunities
     */
    findOptimizationTargets() {
        console.log('🎯 Optimization Targets\n');

        const allFiles = [
            ...this.getJSFiles(this.modernDistPath),
            ...this.getJSFiles(this.legacyDistPath)
        ];

        // Sort by size descending
        const filesBySize = allFiles.map(file => ({
            path: file,
            size: this.getFileSize(file),
            gzipped: this.getGzippedSize(file),
            name: path.relative(this.distPath, file)
        })).sort((a, b) => b.size - a.size);

        console.log('📈 Largest bundles (optimization candidates):');
        filesBySize.slice(0, 10).forEach((file, index) => {
            const priority = file.size > 50 ? '🔥 HIGH' : file.size > 20 ? '⚠️ MEDIUM' : '💡 LOW';
            console.log(`  ${index + 1}. ${file.name}: ${file.size} KB (${file.gzipped} KB gzipped) - ${priority}`);
        });

        console.log('\n💡 Optimization Recommendations:');
        
        const highPriorityFiles = filesBySize.filter(f => f.size > 50);
        if (highPriorityFiles.length > 0) {
            console.log('  🔥 HIGH PRIORITY: Files >50KB should be code-split');
            highPriorityFiles.forEach(file => {
                console.log(`     - ${file.name} (${file.size} KB)`);
            });
        }

        const mediumPriorityFiles = filesBySize.filter(f => f.size > 20 && f.size <= 50);
        if (mediumPriorityFiles.length > 0) {
            console.log('  ⚠️ MEDIUM PRIORITY: Files 20-50KB could benefit from optimization');
            mediumPriorityFiles.forEach(file => {
                console.log(`     - ${file.name} (${file.size} KB)`);
            });
        }
    }

    /**
     * Generate webpack bundle analyzer report
     */
    generateDetailedReport() {
        console.log('📈 Generating detailed bundle analysis...\n');

        try {
            // Run webpack with bundle analyzer
            console.log('🚀 Building with bundle analyzer...');
            execSync('ANALYZE=true npm run build', { stdio: 'inherit' });
        } catch (error) {
            console.error('❌ Failed to generate detailed report:', error.message);
            console.log('\n💡 Fallback: Analyzing existing bundles...');
            try {
                const command = 'npx webpack-bundle-analyzer dist/packages/*.js --mode server --port 8888 --no-open';
                console.log('🚀 Starting bundle analyzer server...');
                console.log('📊 Open http://localhost:8888 to view detailed analysis');
                console.log('⚠️ Press Ctrl+C to stop the server\n');
                
                execSync(command, { stdio: 'inherit' });
            } catch (fallbackError) {
                console.error('❌ Fallback also failed:', fallbackError.message);
            }
        }
    }

    /**
     * Show help information
     */
    showHelp() {
        console.log(`
Bundle Analyzer

Usage: node bin/bundle-analyzer.js [command]

Commands:
  analyze    Show bundle size analysis and optimization targets
  detailed   Generate detailed interactive bundle analysis
  help       Show this help message

Examples:
  node bin/bundle-analyzer.js analyze
  node bin/bundle-analyzer.js detailed
        `);
    }
}

// CLI handling
if (require.main === module) {
    const analyzer = new BundleAnalyzer();
    const command = process.argv[2];

    switch (command) {
        case 'analyze':
            analyzer.analyzeBundles();
            analyzer.findOptimizationTargets();
            break;
        case 'detailed':
            analyzer.generateDetailedReport();
            break;
        case 'help':
        case '--help':
            analyzer.showHelp();
            break;
        default:
            if (command) {
                console.error(`Unknown command: ${command}\n`);
            }
            analyzer.analyzeBundles();
            analyzer.findOptimizationTargets();
    }
}

module.exports = BundleAnalyzer;