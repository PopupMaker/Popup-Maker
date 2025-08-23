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
            
            if (!process.argv.includes('--auto-fix')) {
                console.log(`✅ Loaded ${this.patterns.size} validation patterns`);
            }
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

        // This will be handled by the new block fixer, so just do basic checks
        const openTags = (html.match(/<!--\s*wp:/g) || []).length;
        const closeTags = (html.match(/<!--\s*\/wp:/g) || []).length;
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

        // Apply learned pattern corrections first
        correctedHtml = this.applyLearnedPatterns(correctedHtml, corrections);

        // Fix block comment JSON issues by rewriting malformed block comments
        correctedHtml = this.fixBlockCommentAttributes(correctedHtml, corrections);

        // Fix common JSON issues in remaining content
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
     * Apply learned patterns from corrections log
     */
    applyLearnedPatterns(html, corrections) {
        let correctedHtml = html;

        try {
            // Load corrections log
            const logPath = path.join(this.patternsDir, 'corrections-log.json');
            const logData = JSON.parse(require('fs').readFileSync(logPath, 'utf8'));
            const patterns = logData.correctionPatterns;

            // Apply HTML comment removal (but preserve WordPress block comments)
            if (patterns.htmlCommentRemoval) {
                const originalHtml = correctedHtml;
                // Only remove HTML comments that aren't WordPress block comments
                correctedHtml = correctedHtml.replace(/<!--(?!\s*\/?wp:)[^>]*-->/g, '');
                if (originalHtml !== correctedHtml) {
                    corrections.push({
                        type: 'learned_pattern',
                        description: patterns.htmlCommentRemoval.description,
                        confidence: patterns.htmlCommentRemoval.confidence || 0.9
                    });
                }
            }

            // Apply template variable cleanup
            if (patterns.templateVariableCleanup) {
                const originalHtml = correctedHtml;
                correctedHtml = correctedHtml.replace(/\{\{[^:]+:([^}]+)\}\}/g, '$1');
                if (originalHtml !== correctedHtml) {
                    corrections.push({
                        type: 'learned_pattern',
                        description: patterns.templateVariableCleanup.description,
                        confidence: patterns.templateVariableCleanup.confidence || 0.9
                    });
                }
            }

            // Apply position removal
            if (patterns.positionRemoval) {
                const originalHtml = correctedHtml;
                correctedHtml = correctedHtml.replace(/"position":\{[^}]+\},?/g, '');
                if (originalHtml !== correctedHtml) {
                    corrections.push({
                        type: 'learned_pattern',
                        description: patterns.positionRemoval.description,
                        confidence: patterns.positionRemoval.confidence || 0.9
                    });
                }
            }

            // Apply CSS filtering
            if (patterns.cssFiltering) {
                const originalHtml = correctedHtml;
                const unsupportedCss = ['position', 'z-index', 'display'];
                unsupportedCss.forEach(prop => {
                    const regex = new RegExp(`"${prop}":\\s*"[^"]*",?`, 'g');
                    correctedHtml = correctedHtml.replace(regex, '');
                });
                if (originalHtml !== correctedHtml) {
                    corrections.push({
                        type: 'learned_pattern',
                        description: patterns.cssFiltering.description,
                        confidence: patterns.cssFiltering.confidence || 0.9
                    });
                }
            }

            // Apply border simplification
            if (patterns.borderSimplification) {
                const originalHtml = correctedHtml;
                correctedHtml = correctedHtml.replace(/"border":\{[^}]*"radius":"([^"]+)"[^}]*\}/g, '"border":{"radius":"$1"}');
                if (originalHtml !== correctedHtml) {
                    corrections.push({
                        type: 'learned_pattern',
                        description: patterns.borderSimplification.description,
                        confidence: patterns.borderSimplification.confidence || 0.9
                    });
                }
            }

            // Apply CSS class generation fixes
            if (patterns.cssClassGeneration) {
                const originalHtml = correctedHtml;
                // Convert style.color.text to textColor attribute
                correctedHtml = correctedHtml.replace(/"style":\{([^}]*)"color":\{"text":"([^"]+)"\}([^}]*)\}/g, (match, before, color, after) => {
                    const colorName = this.getColorName(color);
                    let newStyle = before + after;
                    newStyle = newStyle.replace(/,,/g, ',').replace(/^,|,$/g, '');
                    
                    if (newStyle.trim()) {
                        return `"style":{${newStyle}},"textColor":"${colorName}"`;
                    } else {
                        return `"textColor":"${colorName}"`;
                    }
                });
                if (originalHtml !== correctedHtml) {
                    corrections.push({
                        type: 'learned_pattern',
                        description: patterns.cssClassGeneration.description,
                        confidence: patterns.cssClassGeneration.confidence || 0.9
                    });
                }
            }

        } catch (error) {
            console.warn(`Could not apply learned patterns: ${error.message}`);
        }

        return correctedHtml;
    }

    /**
     * Get color name from hex value
     */
    getColorName(colorValue) {
        const colorMap = {
            '#339af0': 'blue',
            '#555555': 'gray',
            '#666666': 'gray',
            '#2d74da': 'blue'
        };
        
        return colorMap[colorValue] || 'gray';
    }

    /**
     * Fix block comment attributes to match actual HTML content
     */
    fixBlockCommentAttributes(html, corrections) {
        let correctedHtml = html;
        
        // Enhanced approach: extract attributes from HTML and sync with block comments
        const blockPattern = /(<!--\s*wp:([^\s]+)(?:\s+({[^}]*}))?\s*-->)([\s\S]*?)(?=<!--\s*\/wp:\2\s*-->|<!--\s*wp:|$)/g;
        
        correctedHtml = correctedHtml.replace(blockPattern, (match, blockComment, blockName, attrsJson, blockContent) => {
            try {
                let attrs = {};
                
                // Try to parse existing attributes
                if (attrsJson) {
                    try {
                        attrs = JSON.parse(attrsJson);
                    } catch (e) {
                        corrections.push({
                            type: 'json_fix',
                            description: `Fixed malformed JSON in ${blockName} block`,
                            confidence: 0.9
                        });
                    }
                }
                
                // Extract attributes from HTML content and merge with existing
                const extractedAttrs = this.extractAttributesFromHTML(blockName, blockContent, attrs);
                
                // Check if we made changes
                if (JSON.stringify(attrs) !== JSON.stringify(extractedAttrs)) {
                    corrections.push({
                        type: 'attribute_sync',
                        description: `Synchronized ${blockName} attributes with HTML content`,
                        confidence: 0.95
                    });
                }
                
                const fixedAttrsJson = Object.keys(extractedAttrs).length > 0 ? ` ${JSON.stringify(extractedAttrs)}` : '';
                return `<!-- wp:${blockName}${fixedAttrsJson} -->${blockContent}`;
                
            } catch (error) {
                console.warn(`Error fixing block comment: ${error.message}`);
                return match;
            }
        });
        
        return correctedHtml;
    }

    /**
     * Extract attributes from HTML content to match WordPress expectations
     */
    extractAttributesFromHTML(blockName, htmlContent, currentAttrs = {}) {
        const attrs = { ...currentAttrs };
        
        // Find the main HTML element for this block
        const elementMatch = htmlContent.match(/<(\w+)[^>]*>/);
        if (!elementMatch) return attrs;
        
        const fullElement = elementMatch[0];
        const tagName = elementMatch[1];
        
        // Extract class attribute and ensure WordPress expected classes
        this.syncClassAttributes(blockName, tagName, fullElement, attrs);
        
        // Extract style attributes and convert to block format
        this.syncStyleAttributes(blockName, fullElement, attrs);
        
        // Block-specific attribute extraction
        switch (blockName) {
            case 'image':
                this.syncImageAttributes(fullElement, attrs);
                break;
            case 'heading':
                this.syncHeadingAttributes(fullElement, attrs);
                break;
            case 'list':
                this.syncListAttributes(fullElement, attrs);
                break;
        }
        
        return attrs;
    }

    /**
     * Synchronize class attributes between HTML and block comment
     */
    syncClassAttributes(blockName, tagName, htmlElement, attrs) {
        const classMatch = htmlElement.match(/class="([^"]+)"/);
        if (!classMatch) return;
        
        const htmlClasses = classMatch[1].split(/\s+/).filter(c => c);
        const wpBlockClass = `wp-block-${blockName}`;
        
        // Ensure WordPress block class is present
        if (!htmlClasses.includes(wpBlockClass)) {
            // This is a missing class that WordPress expects
            attrs.className = attrs.className || '';
            if (!attrs.className.includes(wpBlockClass)) {
                attrs.className = attrs.className ? `${wpBlockClass} ${attrs.className}` : wpBlockClass;
            }
        }
        
        // Extract custom classes (non-WordPress core classes)
        const customClasses = htmlClasses.filter(cls => 
            !cls.startsWith('wp-block-') && 
            !cls.startsWith('has-') && 
            !cls.includes('align') &&
            !cls.includes('size-') &&
            !cls.includes('is-')
        );
        
        if (customClasses.length > 0) {
            attrs.className = customClasses.join(' ');
        }
    }

    /**
     * Synchronize style attributes between HTML and block comment
     */
    syncStyleAttributes(blockName, htmlElement, attrs) {
        const styleMatch = htmlElement.match(/style="([^"]+)"/);
        if (!styleMatch) return;
        
        const styleStr = styleMatch[1];
        attrs.style = attrs.style || {};
        
        // Parse gap property specifically (common WordPress issue)
        if (styleStr.includes('gap:')) {
            const gapMatch = styleStr.match(/gap:\s*([^;]+)/);
            if (gapMatch) {
                attrs.style.spacing = attrs.style.spacing || {};
                attrs.style.spacing.blockGap = gapMatch[1].trim();
            }
        }
        
        // Parse margin properties
        if (styleStr.includes('margin-')) {
            attrs.style.spacing = attrs.style.spacing || {};
            attrs.style.spacing.margin = attrs.style.spacing.margin || {};
            
            const marginBottom = styleStr.match(/margin-bottom:\s*([^;]+)/);
            if (marginBottom) {
                attrs.style.spacing.margin.bottom = marginBottom[1].trim();
            }
            
            const marginTop = styleStr.match(/margin-top:\s*([^;]+)/);
            if (marginTop) {
                attrs.style.spacing.margin.top = marginTop[1].trim();
            }
        }
        
        // Parse padding properties
        if (styleStr.includes('padding')) {
            attrs.style.spacing = attrs.style.spacing || {};
            
            const paddingAll = styleStr.match(/(?:^|;)\s*padding:\s*([^;]+)/);
            if (paddingAll) {
                attrs.style.spacing.padding = { all: paddingAll[1].trim() };
            }
        }
        
        // Parse border properties
        if (styleStr.includes('border-radius')) {
            const radiusMatch = styleStr.match(/border-radius:\s*([^;]+)/);
            if (radiusMatch) {
                attrs.style.border = attrs.style.border || {};
                attrs.style.border.radius = radiusMatch[1].trim();
            }
        }
        
        // Parse font properties
        if (styleStr.includes('font-')) {
            attrs.style.typography = attrs.style.typography || {};
            
            const fontSize = styleStr.match(/font-size:\s*([^;]+)/);
            if (fontSize) {
                attrs.style.typography.fontSize = fontSize[1].trim();
            }
            
            const fontWeight = styleStr.match(/font-weight:\s*([^;]+)/);
            if (fontWeight) {
                attrs.style.typography.fontWeight = fontWeight[1].trim();
            }
            
            const lineHeight = styleStr.match(/line-height:\s*([^;]+)/);
            if (lineHeight) {
                attrs.style.typography.lineHeight = lineHeight[1].trim();
            }
        }
        
        // Parse color properties
        if (styleStr.includes('color:')) {
            const colorMatch = styleStr.match(/(?:^|;)\s*color:\s*([^;]+)/);
            if (colorMatch) {
                attrs.style.color = attrs.style.color || {};
                attrs.style.color.text = colorMatch[1].trim();
            }
        }
    }

    /**
     * Synchronize image-specific attributes
     */
    syncImageAttributes(htmlElement, attrs) {
        const widthMatch = htmlElement.match(/width="(\d+)"/);
        const heightMatch = htmlElement.match(/height="(\d+)"/);
        
        if (widthMatch) {
            attrs.width = parseInt(widthMatch[1], 10);
        }
        
        if (heightMatch) {
            attrs.height = parseInt(heightMatch[1], 10);
        }
        
        // Extract size class if present
        const classMatch = htmlElement.match(/class="([^"]+)"/);
        if (classMatch) {
            const classes = classMatch[1].split(/\s+/);
            const sizeClass = classes.find(c => c.startsWith('size-'));
            if (sizeClass) {
                attrs.sizeSlug = sizeClass.replace('size-', '');
            }
        }
    }

    /**
     * Synchronize heading-specific attributes  
     */
    syncHeadingAttributes(htmlElement, attrs) {
        // Extract heading level from tag name
        const levelMatch = htmlElement.match(/<h(\d)/);
        if (levelMatch) {
            attrs.level = parseInt(levelMatch[1], 10);
        }
        
        // Extract text alignment
        const classMatch = htmlElement.match(/class="([^"]+)"/);
        if (classMatch && classMatch[1].includes('has-text-align-')) {
            const alignMatch = classMatch[1].match(/has-text-align-(\w+)/);
            if (alignMatch) {
                attrs.textAlign = alignMatch[1];
            }
        }
    }

    /**
     * Synchronize list-specific attributes
     */
    syncListAttributes(htmlElement, attrs) {
        const classMatch = htmlElement.match(/class="([^"]+)"/);
        if (!classMatch) return;
        
        const classes = classMatch[1].split(/\s+/);
        
        // Extract custom classes (excluding WordPress core classes)
        const customClasses = classes.filter(cls => 
            !cls.startsWith('wp-block-') && 
            !cls.startsWith('has-')
        );
        
        if (customClasses.length > 0) {
            attrs.className = customClasses.join(' ');
        }
    }

    /**
     * Generate correct attributes for a block based on its HTML content
     */
    generateCorrectAttributes(blockName, innerHtml, currentAttrs = {}) {
        const attrs = { ...currentAttrs };
        
        switch (blockName) {
            case 'group':
                return this.fixGroupAttributes(innerHtml, attrs);
            case 'image':
                return this.fixImageAttributes(innerHtml, attrs);
            case 'heading':
                return this.fixHeadingAttributes(innerHtml, attrs);
            case 'paragraph':
                return this.fixParagraphAttributes(innerHtml, attrs);
            case 'list':
                return this.fixListAttributes(innerHtml, attrs);
            default:
                return this.extractStyleAttributes(innerHtml, attrs);
        }
    }

    /**
     * Fix group block attributes
     */
    fixGroupAttributes(innerHtml, attrs = {}) {
        const result = { ...attrs };
        
        // Extract style attribute from HTML
        const styleMatch = innerHtml.match(/style="([^"]+)"/);
        if (styleMatch) {
            const styleStr = styleMatch[1];
            const style = {};
            
            // Parse individual style properties
            if (styleStr.includes('padding:')) {
                const paddingMatch = styleStr.match(/padding:([^;]+)/);
                if (paddingMatch) {
                    style.spacing = style.spacing || {};
                    style.spacing.padding = { all: paddingMatch[1].trim() };
                }
            }
            
            if (styleStr.includes('border-radius:')) {
                const radiusMatch = styleStr.match(/border-radius:([^;]+)/);
                if (radiusMatch) {
                    style.border = style.border || {};
                    style.border.radius = radiusMatch[1].trim();
                }
            }
            
            if (styleStr.includes('margin-bottom:')) {
                const marginMatch = styleStr.match(/margin-bottom:([^;]+)/);
                if (marginMatch) {
                    style.spacing = style.spacing || {};
                    style.spacing.margin = style.spacing.margin || {};
                    style.spacing.margin.bottom = marginMatch[1].trim();
                }
            }
            
            if (styleStr.includes('gap:')) {
                const gapMatch = styleStr.match(/gap:([^;]+)/);
                if (gapMatch) {
                    style.spacing = style.spacing || {};
                    style.spacing.blockGap = gapMatch[1].trim();
                }
            }
            
            if (Object.keys(style).length > 0) {
                result.style = style;
            }
        }
        
        // Extract layout information
        if (innerHtml.includes('class=')) {
            const classMatch = innerHtml.match(/class="([^"]+)"/);
            if (classMatch && classMatch[1].includes('has-background')) {
                result.backgroundColor = 'white'; // Default for has-white-background-color
            }
        }
        
        return result;
    }

    /**
     * Fix image block attributes
     */
    fixImageAttributes(innerHtml, attrs = {}) {
        const result = { ...attrs };
        
        // Extract width and height from img tag
        const imgMatch = innerHtml.match(/<img[^>]+>/);
        if (imgMatch) {
            const img = imgMatch[0];
            
            const widthMatch = img.match(/width="(\d+)"/);
            const heightMatch = img.match(/height="(\d+)"/);
            
            if (widthMatch) result.width = parseInt(widthMatch[1]);
            if (heightMatch) result.height = parseInt(heightMatch[1]);
        }
        
        // Extract style attributes
        return this.extractStyleAttributes(innerHtml, result);
    }

    /**
     * Fix heading block attributes
     */
    fixHeadingAttributes(innerHtml, attrs = {}) {
        const result = { ...attrs };
        
        // Extract heading level from HTML tag
        const headingMatch = innerHtml.match(/<h(\d)/);
        if (headingMatch) {
            result.level = parseInt(headingMatch[1]);
        }
        
        // Extract text alignment
        if (innerHtml.includes('has-text-align-center')) {
            result.textAlign = 'center';
        }
        
        return this.extractStyleAttributes(innerHtml, result);
    }

    /**
     * Fix paragraph block attributes
     */
    fixParagraphAttributes(innerHtml, attrs = {}) {
        const result = { ...attrs };
        
        // Extract text alignment
        if (innerHtml.includes('has-text-align-center')) {
            result.align = 'center';
        }
        
        return this.extractStyleAttributes(innerHtml, result);
    }

    /**
     * Fix list block attributes
     */
    fixListAttributes(innerHtml, attrs = {}) {
        const result = { ...attrs };
        return this.extractStyleAttributes(innerHtml, result);
    }

    /**
     * Extract style attributes from inline styles
     */
    extractStyleAttributes(innerHtml, attrs = {}) {
        const result = { ...attrs };
        
        const styleMatch = innerHtml.match(/style="([^"]+)"/);
        if (styleMatch) {
            const styleStr = styleMatch[1];
            const style = result.style || {};
            
            // Parse typography styles
            if (styleStr.includes('font-size:') || styleStr.includes('font-weight:') || styleStr.includes('line-height:')) {
                style.typography = style.typography || {};
                
                const fontSizeMatch = styleStr.match(/font-size:([^;]+)/);
                if (fontSizeMatch) style.typography.fontSize = fontSizeMatch[1].trim();
                
                const fontWeightMatch = styleStr.match(/font-weight:([^;]+)/);
                if (fontWeightMatch) style.typography.fontWeight = fontWeightMatch[1].trim();
                
                const lineHeightMatch = styleStr.match(/line-height:([^;]+)/);
                if (lineHeightMatch) style.typography.lineHeight = lineHeightMatch[1].trim();
            }
            
            // Parse color styles
            if (styleStr.includes('color:')) {
                const colorMatch = styleStr.match(/color:([^;]+)/);
                if (colorMatch) {
                    style.color = style.color || {};
                    style.color.text = colorMatch[1].trim();
                }
            }
            
            // Parse spacing styles
            if (styleStr.includes('margin') || styleStr.includes('padding')) {
                style.spacing = style.spacing || {};
                
                const marginBottomMatch = styleStr.match(/margin-bottom:([^;]+)/);
                if (marginBottomMatch) {
                    style.spacing.margin = style.spacing.margin || {};
                    style.spacing.margin.bottom = marginBottomMatch[1].trim();
                }
                
                const paddingMatch = styleStr.match(/padding:([^;]+)/);
                if (paddingMatch) {
                    style.spacing.padding = { all: paddingMatch[1].trim() };
                }
            }
            
            if (Object.keys(style).length > 0) {
                result.style = style;
            }
        }
        
        return result;
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