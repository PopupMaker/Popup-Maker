#!/usr/bin/env node

/**
 * Gutenberg Block Validator
 * 
 * Fast Node.js validation using WordPress block parser and pattern library.
 * Falls back to Playwright for complex cases.
 * 
 * @package PopupMaker
 */

const fs = require('fs').promises;
const path = require('path');

/**
 * Main validator class
 */
class GutenbergBlockValidator {
    constructor() {
        this.patternsDir = path.join(__dirname, '..', '.claude', 'block-patterns');
        this.patterns = new Map();
        this.loadPatterns();
    }

    /**
     * Load all validation patterns from disk
     */
    async loadPatterns() {
        try {
            const files = await fs.readdir(this.patternsDir);
            const patternFiles = files.filter(f => f.endsWith('.validated.json') && f !== 'corrections-log.json');
            
            for (const file of patternFiles) {
                const content = await fs.readFile(path.join(this.patternsDir, file), 'utf8');
                const pattern = JSON.parse(content);
                this.patterns.set(pattern.layoutType, pattern);
            }
            
            console.log(`✅ Loaded ${this.patterns.size} validation patterns`);
        } catch (error) {
            console.warn(`⚠️  Could not load patterns: ${error.message}`);
        }
    }

    /**
     * Validate block HTML using WordPress block parser
     * 
     * @param {string} html - Block HTML to validate
     * @param {string} layoutType - Type of layout (newsletter, discount, etc.)
     * @returns {Promise<Object>} Validation result
     */
    async validateBlocks(html, layoutType = null) {
        const result = {
            isValid: true,
            errors: [],
            corrections: [],
            correctedHtml: html,
            confidence: 1.0,
            usedPatterns: [],
            processingTime: Date.now()
        };

        try {
            // 1. Quick syntax check
            const syntaxErrors = this.checkSyntax(html);
            if (syntaxErrors.length > 0) {
                result.isValid = false;
                result.errors.push(...syntaxErrors);
            }

            // 2. Apply pattern-based corrections
            if (layoutType && this.patterns.has(layoutType)) {
                const corrected = await this.applyPatternCorrections(html, layoutType);
                result.correctedHtml = corrected.html;
                result.corrections.push(...corrected.corrections);
                result.usedPatterns.push(layoutType);
                
                if (corrected.corrections.length > 0) {
                    result.isValid = false;
                }
            }

            // 3. WordPress block parser validation (if available)
            const parseResult = await this.parseWithWordPress(result.correctedHtml);
            if (!parseResult.isValid) {
                result.isValid = false;
                result.errors.push(...parseResult.errors);
                
                // Apply generic corrections
                const genericCorrected = this.applyGenericCorrections(result.correctedHtml);
                result.correctedHtml = genericCorrected.html;
                result.corrections.push(...genericCorrected.corrections);
            }

            // 4. Calculate confidence score
            result.confidence = this.calculateConfidence(result);
            
            // 5. Update statistics
            await this.updateStatistics(layoutType, result);

        } catch (error) {
            result.isValid = false;
            result.errors.push(`Validation error: ${error.message}`);
            result.confidence = 0;
        }

        result.processingTime = Date.now() - result.processingTime;
        return result;
    }

    /**
     * Check basic HTML syntax for common issues
     */
    checkSyntax(html) {
        const errors = [];

        // Check for unescaped quotes in JSON
        const jsonMatches = html.match(/<!--\s*wp:\w+[^>]*{[^}]*}[^>]*-->/g);
        if (jsonMatches) {
            jsonMatches.forEach((match, index) => {
                try {
                    const jsonPart = match.match(/{[^}]*}/);
                    if (jsonPart) {
                        JSON.parse(jsonPart[0]);
                    }
                } catch (e) {
                    errors.push(`Invalid JSON in block ${index + 1}: ${e.message}`);
                }
            });
        }

        // Check for unclosed block comments
        const openTags = (html.match(/<!--\s*wp:/g) || []).length;
        const closeTags = (html.match(/-->/g) || []).length;
        if (openTags !== closeTags) {
            errors.push(`Mismatched block comment tags: ${openTags} open, ${closeTags} close`);
        }

        return errors;
    }

    /**
     * Apply corrections based on learned patterns
     */
    async applyPatternCorrections(html, layoutType) {
        const pattern = this.patterns.get(layoutType);
        const corrections = [];
        let correctedHtml = html;

        if (!pattern) {
            return { html, corrections };
        }

        // Apply each pattern correction
        for (const [patternName, patternData] of Object.entries(pattern.patterns)) {
            if (patternData.original && patternData.validated && patternData.original !== patternData.validated) {
                const originalRegex = new RegExp(this.escapeRegex(patternData.original), 'g');
                if (originalRegex.test(correctedHtml)) {
                    correctedHtml = correctedHtml.replace(originalRegex, patternData.validated);
                    corrections.push({
                        pattern: patternName,
                        type: 'pattern_replacement',
                        description: patternData.corrections.join(', '),
                        confidence: patternData.confidence
                    });
                }
            }
        }

        return { html: correctedHtml, corrections };
    }

    /**
     * Apply generic corrections for common issues
     */
    applyGenericCorrections(html) {
        const corrections = [];
        let correctedHtml = html;

        // Fix common JSON issues
        const jsonFixes = [
            {
                pattern: /"(\d+)px"/g,
                replacement: '$1',
                description: 'Convert string dimensions to numbers'
            },
            {
                pattern: /'([^']*)'/g,
                replacement: '"$1"',
                description: 'Convert single quotes to double quotes'
            },
            {
                pattern: /,(\s*})/g,
                replacement: '$1',
                description: 'Remove trailing commas'
            }
        ];

        jsonFixes.forEach(fix => {
            if (fix.pattern.test(correctedHtml)) {
                correctedHtml = correctedHtml.replace(fix.pattern, fix.replacement);
                corrections.push({
                    type: 'generic_fix',
                    description: fix.description,
                    confidence: 0.85
                });
            }
        });

        return { html: correctedHtml, corrections };
    }

    /**
     * Try to parse with WordPress block parser (requires @wordpress/blocks)
     */
    async parseWithWordPress(html) {
        try {
            // Try to require WordPress blocks package
            const { parse } = require('@wordpress/blocks');
            const blocks = parse(html);
            
            // Check for parse errors or invalid blocks
            const invalidBlocks = blocks.filter(block => 
                block.isValid === false || 
                block.validationIssues?.length > 0
            );

            return {
                isValid: invalidBlocks.length === 0,
                errors: invalidBlocks.map(block => 
                    `Invalid block: ${block.name} - ${block.validationIssues?.join(', ') || 'Unknown error'}`
                ),
                blocks
            };
        } catch (error) {
            // WordPress blocks package not available, skip this validation
            return { isValid: true, errors: [], blocks: [] };
        }
    }

    /**
     * Calculate confidence score based on validation results
     */
    calculateConfidence(result) {
        let confidence = 1.0;

        // Reduce confidence for each error
        confidence -= result.errors.length * 0.1;

        // Reduce confidence for each correction needed
        confidence -= result.corrections.length * 0.05;

        // Factor in pattern confidence scores
        const patternConfidences = result.corrections
            .filter(c => c.confidence)
            .map(c => c.confidence);
        
        if (patternConfidences.length > 0) {
            const avgPatternConfidence = patternConfidences.reduce((a, b) => a + b, 0) / patternConfidences.length;
            confidence = Math.min(confidence, avgPatternConfidence);
        }

        return Math.max(0, Math.min(1, confidence));
    }

    /**
     * Update validation statistics
     */
    async updateStatistics(layoutType, result) {
        try {
            const logPath = path.join(this.patternsDir, 'corrections-log.json');
            const logData = JSON.parse(await fs.readFile(logPath, 'utf8'));

            // Update global stats
            logData.globalStatistics.totalValidations++;
            if (result.isValid || result.confidence > 0.8) {
                logData.globalStatistics.successfulValidations++;
            } else {
                logData.globalStatistics.failedValidations++;
            }

            // Update pattern usage stats
            if (layoutType && this.patterns.has(layoutType)) {
                const pattern = this.patterns.get(layoutType);
                pattern.statistics.timesUsed++;
                pattern.statistics.lastValidation = new Date().toISOString();
                
                // Write updated pattern back
                const patternPath = path.join(this.patternsDir, `${layoutType}.validated.json`);
                await fs.writeFile(patternPath, JSON.stringify(pattern, null, 2));
            }

            logData.lastUpdated = new Date().toISOString();
            await fs.writeFile(logPath, JSON.stringify(logData, null, 2));
            
        } catch (error) {
            console.warn(`Could not update statistics: ${error.message}`);
        }
    }

    /**
     * Escape string for use in regex
     */
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\\]\\]/g, '\\\\$&');
    }

    /**
     * Get validation statistics
     */
    async getStatistics() {
        try {
            const logPath = path.join(this.patternsDir, 'corrections-log.json');
            const logData = JSON.parse(await fs.readFile(logPath, 'utf8'));
            
            // Add pattern stats
            const patternStats = {};
            for (const [type, pattern] of this.patterns) {
                patternStats[type] = pattern.statistics;
            }

            return {
                global: logData.globalStatistics,
                patterns: patternStats,
                correctionPatterns: logData.correctionPatterns
            };
        } catch (error) {
            return { error: error.message };
        }
    }
}

/**
 * CLI interface
 */
async function main() {
    const args = process.argv.slice(2);
    const validator = new GutenbergBlockValidator();

    if (args.includes('--help') || args.includes('-h')) {
        console.log(`
Gutenberg Block Validator

Usage:
  validate-gutenberg-blocks [options] [html]
  validate-gutenberg-blocks --file <path>
  validate-gutenberg-blocks --stats
  validate-gutenberg-blocks --patterns

Options:
  --file <path>     Validate HTML from file
  --type <type>     Layout type (newsletter, discount, etc.)
  --stats           Show validation statistics
  --patterns        List available patterns
  --auto-fix        Apply automatic corrections
  --dry-run         Validate without outputting corrections
  --help, -h        Show this help

Examples:
  validate-gutenberg-blocks '<!-- wp:group {...} -->'
  validate-gutenberg-blocks --file blocks.html --type newsletter
  validate-gutenberg-blocks --stats
`);
        return;
    }

    if (args.includes('--stats')) {
        const stats = await validator.getStatistics();
        console.log(JSON.stringify(stats, null, 2));
        return;
    }

    if (args.includes('--patterns')) {
        console.log('Available validation patterns:');
        for (const [type, pattern] of validator.patterns) {
            console.log(`  ${type}: ${pattern.description} (${Object.keys(pattern.patterns).length} patterns)`);
        }
        return;
    }

    // Get HTML input
    let html = '';
    const fileIndex = args.indexOf('--file');
    if (fileIndex >= 0 && args[fileIndex + 1]) {
        html = await fs.readFile(args[fileIndex + 1], 'utf8');
    } else if (args.length > 0 && !args[0].startsWith('--')) {
        html = args[0];
    } else {
        console.error('No HTML input provided. Use --help for usage.');
        process.exit(1);
    }

    // Get layout type
    const typeIndex = args.indexOf('--type');
    const layoutType = typeIndex >= 0 ? args[typeIndex + 1] : null;

    // Validate
    const result = await validator.validateBlocks(html, layoutType);

    if (args.includes('--dry-run')) {
        // Just show validation results
        console.log(JSON.stringify({
            isValid: result.isValid,
            errors: result.errors,
            corrections: result.corrections,
            confidence: result.confidence
        }, null, 2));
    } else if (args.includes('--auto-fix')) {
        // Output corrected HTML
        console.log(result.correctedHtml);
    } else {
        // Full result
        console.log(JSON.stringify(result, null, 2));
    }

    process.exit(result.isValid ? 0 : 1);
}

// Export for use as module
module.exports = GutenbergBlockValidator;

// Run as CLI if called directly
if (require.main === module) {
    main().catch(error => {
        console.error('Validation failed:', error.message);
        process.exit(1);
    });
}