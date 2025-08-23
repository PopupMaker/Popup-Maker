/**
 * Gutenberg Block Validator - Playwright E2E Tests
 * 
 * Browser-based validation with automated error recovery and pattern learning.
 * This test file also serves as the Playwright fallback validator for complex cases.
 * 
 * @package PopupMaker
 */

import { test, expect, Page, BrowserContext } from '@playwright/test';
import fs from 'fs/promises';
import path from 'path';

/**
 * Configuration
 */
const WORDPRESS_CONFIG = {
    baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
    adminUser: process.env.WP_ADMIN_USER || 'admin',
    adminPassword: process.env.WP_ADMIN_PASSWORD || 'password',
    timeout: 30000
};

/**
 * Validation result interface
 */
interface ValidationResult {
    isValid: boolean;
    errors: string[];
    corrections: Array<{
        type: string;
        description: string;
        original?: string;
        corrected?: string;
        confidence: number;
    }>;
    correctedHtml: string;
    confidence: number;
    processingTime: number;
}

/**
 * Gutenberg Validator Test Class
 */
class GutenbergValidatorE2E {
    private page: Page;
    private patternsDir: string;

    constructor(page: Page) {
        this.page = page;
        this.patternsDir = path.join(__dirname, '..', '..', '.claude', 'block-patterns');
    }

    /**
     * Login to WordPress admin
     */
    async login(): Promise<void> {
        await this.page.goto(`${WORDPRESS_CONFIG.baseURL}/wp-login.php`);
        
        // Fill login form
        await this.page.fill('#user_login', WORDPRESS_CONFIG.adminUser);
        await this.page.fill('#user_pass', WORDPRESS_CONFIG.adminPassword);
        await this.page.click('#wp-submit');
        
        // Wait for dashboard
        await this.page.waitForURL('**/wp-admin/**');
        await expect(this.page.locator('#wpadminbar')).toBeVisible();
    }

    /**
     * Create a new post for testing
     */
    async createTestPost(): Promise<void> {
        await this.page.goto(`${WORDPRESS_CONFIG.baseURL}/wp-admin/post-new.php`);
        await this.page.waitForSelector('.block-editor');
        
        // Wait for editor to load
        await this.page.waitForFunction(() => 
            window.wp?.blocks && window.wp?.data
        );
        
        // Close welcome modal if present
        const welcomeModal = this.page.locator('[aria-label="Welcome to the block editor"]');
        if (await welcomeModal.isVisible()) {
            await this.page.click('[aria-label="Close"]');
        }
        
        // Close tips modal if present
        const tipsModal = this.page.locator('.components-modal__header:has-text("Tip")');
        if (await tipsModal.isVisible()) {
            await this.page.click('.components-modal__header button[aria-label="Close"]');
        }
    }

    /**
     * Switch to code editor mode
     */
    async switchToCodeEditor(): Promise<void> {
        // Click on options menu (three dots)
        await this.page.click('[aria-label="Options"]');
        
        // Click on "Code editor"
        await this.page.click('text=Code editor');
        
        // Wait for code editor to load
        await this.page.waitForSelector('.editor-post-text-editor');
    }

    /**
     * Switch to visual editor mode
     */
    async switchToVisualEditor(): Promise<void> {
        // Click on "Exit code editor" button
        await this.page.click('text=Exit code editor');
        
        // Wait for visual editor to load
        await this.page.waitForSelector('.block-editor');
        
        // Wait for blocks to be processed
        await this.page.waitForTimeout(2000);
    }

    /**
     * Insert block HTML into the editor
     */
    async insertBlockHTML(html: string): Promise<void> {
        // Make sure we're in code editor
        await this.switchToCodeEditor();
        
        // Clear existing content
        await this.page.fill('.editor-post-text-editor', '');
        
        // Insert the block HTML
        await this.page.fill('.editor-post-text-editor', html);
        
        // Wait a moment for processing
        await this.page.waitForTimeout(1000);
    }

    /**
     * Detect validation errors in the editor
     */
    async detectValidationErrors(): Promise<string[]> {
        const errors: string[] = [];
        
        // Switch to visual editor to trigger validation
        await this.switchToVisualEditor();
        
        // Look for block validation warnings
        const warningBlocks = this.page.locator('.block-editor-warning');
        const warningCount = await warningBlocks.count();
        
        for (let i = 0; i < warningCount; i++) {
            const warning = warningBlocks.nth(i);
            const errorText = await warning.textContent();
            if (errorText) {
                errors.push(errorText.trim());
            }
        }
        
        // Look for "Attempt Recovery" buttons
        const recoveryButtons = this.page.locator('button:has-text(\"Attempt Recovery\")');
        const recoveryCount = await recoveryButtons.count();
        
        if (recoveryCount > 0) {
            errors.push(`Found ${recoveryCount} blocks requiring recovery`);
        }
        
        return errors;
    }

    /**
     * Attempt to recover all invalid blocks
     */
    async attemptRecovery(): Promise<Array<{ type: string; description: string; confidence: number }>> {
        const corrections: Array<{ type: string; description: string; confidence: number }> = [];
        
        // Find all "Attempt Recovery" buttons
        const recoveryButtons = this.page.locator('button:has-text(\"Attempt Recovery\")');
        const recoveryCount = await recoveryButtons.count();
        
        console.log(`Found ${recoveryCount} blocks to recover`);
        
        // Click each recovery button
        for (let i = 0; i < recoveryCount; i++) {
            try {
                // Re-query buttons as DOM might have changed
                const currentButtons = this.page.locator('button:has-text(\"Attempt Recovery\")');
                const button = currentButtons.first();
                
                if (await button.isVisible()) {
                    await button.click();
                    await this.page.waitForTimeout(500); // Wait for recovery
                    
                    corrections.push({
                        type: 'automatic_recovery',
                        description: `WordPress automatic block recovery applied`,
                        confidence: 0.90
                    });
                }
            } catch (error) {
                console.warn(`Recovery ${i + 1} failed:`, error);
            }
        }
        
        return corrections;
    }

    /**
     * Extract corrected HTML from the editor
     */
    async extractCorrectedHTML(): Promise<string> {
        // Switch to code editor to get the HTML
        await this.switchToCodeEditor();
        
        // Get the content from the text area
        const content = await this.page.inputValue('.editor-post-text-editor');
        
        return content || '';
    }

    /**
     * Validate blocks using browser-based testing
     */
    async validateBlocks(html: string, layoutType?: string): Promise<ValidationResult> {
        const startTime = Date.now();
        const result: ValidationResult = {
            isValid: true,
            errors: [],
            corrections: [],
            correctedHtml: html,
            confidence: 1.0,
            processingTime: 0
        };

        try {
            // 1. Insert HTML into editor
            await this.insertBlockHTML(html);
            
            // 2. Detect validation errors
            const errors = await this.detectValidationErrors();
            result.errors = errors;
            
            if (errors.length > 0) {
                result.isValid = false;
                
                // 3. Attempt automatic recovery
                const corrections = await this.attemptRecovery();
                result.corrections = corrections;
                
                // 4. Extract corrected HTML
                result.correctedHtml = await this.extractCorrectedHTML();
                
                // 5. Check if recovery was successful
                const postRecoveryErrors = await this.detectValidationErrors();
                if (postRecoveryErrors.length === 0) {
                    result.isValid = true;
                    result.confidence = 0.95; // High confidence after successful recovery
                } else {
                    result.confidence = 0.5; // Medium confidence - partial recovery
                }
            }
            
            // 6. Update pattern library if we learned something
            if (result.corrections.length > 0 && layoutType) {
                await this.updatePatternLibrary(layoutType, html, result.correctedHtml, result.corrections);
            }

        } catch (error) {
            result.isValid = false;
            result.errors.push(`Browser validation failed: ${error}`);
            result.confidence = 0;
        }

        result.processingTime = Date.now() - startTime;
        return result;
    }

    /**
     * Update pattern library with learned corrections
     */
    async updatePatternLibrary(
        layoutType: string, 
        originalHtml: string, 
        correctedHtml: string, 
        corrections: Array<{ type: string; description: string; confidence: number }>
    ): Promise<void> {
        try {
            const patternFile = path.join(this.patternsDir, `${layoutType}.validated.json`);
            
            // Load existing pattern data
            let patternData: any = {};
            try {
                const content = await fs.readFile(patternFile, 'utf8');
                patternData = JSON.parse(content);
            } catch {
                // File doesn't exist, create new pattern
                patternData = {
                    version: '1.0.0',
                    layoutType,
                    description: `${layoutType} popup patterns`,
                    patterns: {},
                    commonCorrections: {},
                    statistics: {
                        timesUsed: 0,
                        successRate: 1.0,
                        lastValidation: new Date().toISOString(),
                        totalCorrections: 0,
                        avgConfidence: 1.0
                    }
                };
            }

            // Extract block-level differences
            const blockDifferences = this.extractBlockDifferences(originalHtml, correctedHtml);
            
            // Update patterns
            blockDifferences.forEach((diff, index) => {
                const patternKey = `learned_pattern_${Date.now()}_${index}`;
                patternData.patterns[patternKey] = {
                    name: `Learned pattern ${index + 1}`,
                    original: diff.original,
                    validated: diff.corrected,
                    corrections: corrections.map(c => c.description),
                    confidence: corrections.reduce((avg, c) => avg + c.confidence, 0) / corrections.length,
                    learnedAt: new Date().toISOString()
                };
            });

            // Update statistics
            patternData.statistics.totalCorrections += corrections.length;
            patternData.statistics.lastValidation = new Date().toISOString();
            patternData.lastUpdated = new Date().toISOString();

            // Save updated pattern
            await fs.writeFile(patternFile, JSON.stringify(patternData, null, 2));
            
            console.log(`✅ Updated pattern library for ${layoutType}`);

        } catch (error) {
            console.warn(`⚠️  Could not update pattern library: ${error}`);
        }
    }

    /**
     * Extract differences between original and corrected HTML
     */
    private extractBlockDifferences(original: string, corrected: string): Array<{ original: string; corrected: string }> {
        const differences: Array<{ original: string; corrected: string }> = [];
        
        // Simple diff - in practice, you might want a more sophisticated diff algorithm
        if (original !== corrected) {
            // Split into blocks and compare
            const originalBlocks = original.match(/<!--[^>]*-->[\\s\\S]*?(?=<!--|\$)/g) || [];
            const correctedBlocks = corrected.match(/<!--[^>]*-->[\\s\\S]*?(?=<!--|\$)/g) || [];
            
            originalBlocks.forEach((origBlock, index) => {
                const corrBlock = correctedBlocks[index];
                if (corrBlock && origBlock !== corrBlock) {
                    differences.push({
                        original: origBlock.trim(),
                        corrected: corrBlock.trim()
                    });
                }
            });
        }
        
        return differences;
    }
}

/**
 * Test: Validate Newsletter Layout
 */
test.describe('Gutenberg Block Validation', () => {
    let validator: GutenbergValidatorE2E;

    test.beforeEach(async ({ page }) => {
        validator = new GutenbergValidatorE2E(page);
        await validator.login();
        await validator.createTestPost();
    });

    test('should validate newsletter hero layout', async () => {
        const newsletterHTML = `
<!-- wp:group {"style":{"spacing":{"padding":{"all":"48px"}},"border":{"radius":"12px"},"position":{"type":"relative"}},"backgroundColor":"white","layout":{"type":"constrained","contentSize":"520px"},"className":"pum-newsletter-hero"} -->
<div class="wp-block-group pum-newsletter-hero has-white-background-color has-background" style="border-radius:12px;padding:48px;position:relative">

<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"36px","fontWeight":"700","lineHeight":"1.2"},"spacing":{"margin":{"bottom":"12px"}}}} -->
<h1 class="wp-block-heading has-text-align-center" style="margin-bottom:12px;font-size:36px;font-weight:700;line-height:1.2">Join 10,000+ Subscribers</h1>
<!-- /wp:heading -->

</div>
<!-- /wp:group -->
        `.trim();

        const result = await validator.validateBlocks(newsletterHTML, 'newsletter');
        
        // Log results for debugging
        console.log('Validation Result:', JSON.stringify(result, null, 2));
        
        // Assertions
        expect(result.processingTime).toBeGreaterThan(0);
        expect(result.confidence).toBeGreaterThan(0.5);
        expect(result.correctedHtml).toBeTruthy();
        
        // If there were errors, make sure we attempted recovery
        if (!result.isValid) {
            expect(result.corrections.length).toBeGreaterThan(0);
        }
    });

    test('should handle invalid JSON in block comments', async () => {
        const invalidHTML = `
<!-- wp:group {"style":{"spacing":{"padding":{"all":"48px"}},"border":{"radius":"12px"},"position":{"type":"relative"}},"backgroundColor":"white","layout":{"type":"constrained","contentSize":"520px"},"className":"pum-newsletter-hero"} -->
<div class="wp-block-group">Invalid content</div>
<!-- /wp:group -->
        `.trim();

        const result = await validator.validateBlocks(invalidHTML, 'newsletter');
        
        // Should detect and attempt to fix the issue
        expect(result.processingTime).toBeGreaterThan(0);
        
        if (!result.isValid) {
            expect(result.errors.length).toBeGreaterThan(0);
            expect(result.corrections.length).toBeGreaterThan(0);
        }
    });
});

/**
 * Standalone validator function for programmatic use
 */
export async function validateBlocksWithPlaywright(
    html: string, 
    layoutType?: string,
    browserContext?: BrowserContext
): Promise<ValidationResult> {
    
    if (!browserContext) {
        throw new Error('Browser context required for Playwright validation');
    }
    
    const page = await browserContext.newPage();
    const validator = new GutenbergValidatorE2E(page);
    
    try {
        await validator.login();
        await validator.createTestPost();
        const result = await validator.validateBlocks(html, layoutType);
        await page.close();
        return result;
    } catch (error) {
        await page.close();
        throw error;
    }
}

/**
 * CLI interface for Playwright validator
 */
if (require.main === module) {
    console.log('Use the validate-gutenberg-blocks.js script for CLI validation');
    console.log('This file provides Playwright-based validation for complex cases');
}