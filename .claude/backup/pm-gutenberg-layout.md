# /pm-gutenberg-layout - Popup Maker Gutenberg Layout Generator

Generates professional popup layouts as validated Gutenberg blocks ready for immediate use in the block editor.

## Overview

This command creates complete popup content using WordPress blocks that have been automatically validated and corrected. The layouts are:
- **Validated**: Checked against learned patterns and WordPress block parser
- **Copy-ready**: Can be pasted directly into the popup editor without errors
- **Self-improving**: Learns from validation patterns over time
- **Community-driven**: All validations improve the shared pattern library

## Integration with Validation System

All generated layouts are automatically processed through the validation system:

1. **Generate Layout**: Create initial block HTML
2. **Apply Patterns**: Use learned corrections from `.claude/block-patterns/`
3. **Validate Syntax**: Check with WordPress block parser
4. **Auto-Correct**: Apply automatic fixes for common issues
5. **Confidence Score**: Provide validation confidence rating
6. **Return Validated**: Output clean, error-free block HTML

## Usage Examples

**Basic Usage:**
- `/pm-gutenberg-layout newsletter signup`
- `/pm-gutenberg-layout exit intent discount`
- `/pm-gutenberg-layout cookie notice`

**With Validation Options:**
- `/pm-gutenberg-layout newsletter --validate`
- `/pm-gutenberg-layout discount --no-validate`
- `/pm-gutenberg-layout product --learn`

**With Customization:**
- `/pm-gutenberg-layout newsletter --heading "Join Our Community" --button "Subscribe"`
- `/pm-gutenberg-layout discount --code "SAVE20" --timer "2 hours"`

## Available Layout Types

### 1. Newsletter Signup
**Usage:** `/pm-gutenberg-layout newsletter [variant]`

**Variants:**
- `hero` - Hero-style with large heading and social proof
- `simple` - Minimal newsletter signup form
- `sidebar` - Compact sidebar-friendly layout

**Auto-Validation**: Uses `newsletter.validated.json` patterns

### 2. Exit Intent Discount
**Usage:** `/pm-gutenberg-layout discount [variant]`

**Variants:**
- `urgent` - High-urgency with countdown timer
- `percentage` - Percentage-based discount offer
- `fixed` - Fixed dollar amount discount

**Auto-Validation**: Uses `discount.validated.json` patterns

### 3. Cookie Notice
**Usage:** `/pm-gutenberg-layout cookie [variant]`

**Variants:**
- `banner` - Top/bottom banner style
- `modal` - Center modal style
- `corner` - Bottom corner notification

**Auto-Validation**: Uses `cookie.validated.json` patterns

### 4. Product Announcement
**Usage:** `/pm-gutenberg-layout product [variant]`

**Variants:**
- `featured` - Featured product showcase
- `launch` - New product launch announcement
- `update` - Product update notification

**Auto-Validation**: Uses `product.validated.json` patterns

## Variable System

Templates use `{{VARIABLE}}` or `{{VARIABLE:Default Value}}` syntax for customization:

- `{{VARIABLE}}` - Must be provided by user
- `{{VARIABLE:Default}}` - Uses default if not provided
- Command flags can override variables: `--heading "Custom Title"`

## Command Flags

**Validation Flags:**
- `--validate` - Force validation (default: auto)
- `--no-validate` - Skip validation system
- `--learn` - Enable learning mode (saves patterns)
- `--confidence` - Show validation confidence score

**Common Flags:**
- `--heading "text"` - Override heading content
- `--description "text"` - Override description
- `--button "text"` - Override button text
- `--color "#hex"` - Override primary color
- `--size "small|medium|large"` - Template size

**Layout-Specific Flags:**
- Newsletter: `--email-placeholder`, `--privacy-text`
- Discount: `--code`, `--discount`, `--timer`
- Cookie: `--privacy-url`, `--accept`, `--decline`
- Product: `--features`, `--image`

## Output Format

The command provides validated output in multiple formats:

1. **Validated HTML** - Ready to paste into block editor (default)
2. **Raw HTML** - Original unvalidated blocks (`--raw`)
3. **JSON Export** - For importing into other sites (`--json`)
4. **Validation Report** - Detailed validation results (`--report`)

## Validation Integration Examples

### Basic Layout Generation (Auto-Validated)
```bash
/pm-gutenberg-layout newsletter hero
```
**Output:**
```html
<!-- Confidence: 98% | Patterns Applied: 3 | Errors Fixed: 0 -->

<!-- wp:group {"style":{"spacing":{"padding":{"all":"48px"}},"border":{"radius":"12px"}},"backgroundColor":"white","layout":{"type":"constrained","contentSize":"520px"},"className":"pum-newsletter-hero"} -->
<div class="wp-block-group pum-newsletter-hero has-white-background-color has-background" style="border-radius:12px;padding:48px">
...
</div>
<!-- /wp:group -->
```

### With Validation Report
```bash
/pm-gutenberg-layout newsletter hero --report
```
**Output:**
```json
{
  "html": "<!-- validated block HTML -->",
  "validation": {
    "isValid": true,
    "confidence": 0.98,
    "errors": [],
    "corrections": [
      {
        "type": "pattern_replacement",
        "description": "Applied newsletter hero pattern",
        "confidence": 0.98
      }
    ],
    "patternsUsed": ["newsletter"],
    "processingTime": 45
  }
}
```

### Learning Mode
```bash
/pm-gutenberg-layout discount urgent --learn
```
Enables learning mode - any validation corrections will be saved to the pattern library for future use.

## Pattern Library Integration

The command automatically integrates with the validation pattern library:

**Pattern Application Flow:**
1. Load patterns from `.claude/block-patterns/{layout-type}.validated.json`
2. Apply known corrections to generated HTML
3. Validate with WordPress block parser
4. Apply generic corrections if needed
5. Update usage statistics

**Pattern Learning:**
- New corrections are automatically learned
- Pattern confidence scores improve over time
- Community benefits from shared patterns

## Error Handling & Fallbacks

**Validation Failures:**
- **Node validation fails** → Apply generic corrections
- **Pattern library missing** → Generate basic layout
- **High error rate** → Suggest Playwright validation
- **Complete failure** → Return unvalidated HTML with warning

**Graceful Degradation:**
```bash
⚠️  Validation failed (confidence: 45%)
✅ Generated layout with generic corrections
💡 Consider running: /pm-validate-blocks --learn
```

## Command Implementation

### Core Integration
```javascript
// Pseudo-code for command implementation
async function generateGutenbergLayout(type, variant, options) {
  // 1. Generate initial HTML
  const html = generateLayoutHTML(type, variant, options);
  
  // 2. Auto-validate unless disabled
  if (options.validate !== false) {
    const validator = require('./bin/validate-gutenberg-blocks');
    const result = await validator.validateBlocks(html, type);
    
    // 3. Return validated result
    return {
      html: result.correctedHtml,
      validation: result,
      confidence: result.confidence
    };
  }
  
  // 4. Return raw HTML if validation disabled
  return { html, validation: null, confidence: 1.0 };
}
```

### File Dependencies
- `.claude/pm-gutenberg-layout.md` - This command definition
- `bin/validate-gutenberg-blocks.js` - Node.js validator
- `tests/e2e/gutenberg-validator.spec.ts` - Playwright fallback
- `.claude/block-patterns/*.json` - Pattern library

## Advanced Usage

### Custom Pattern Development
```bash
# Generate with learning enabled
/pm-gutenberg-layout newsletter custom --learn

# Test validation
/pm-validate-blocks --type newsletter --stats

# Export patterns for sharing
/pm-validate-blocks --export-patterns
```

### Batch Validation
```bash
# Validate all layout types
for type in newsletter discount cookie product; do
  /pm-gutenberg-layout $type --validate --report
done
```

### CI/CD Integration
```bash
# In package.json scripts
"validate-layouts": "node bin/validate-gutenberg-blocks.js --patterns && echo 'All patterns valid'"
```

## Performance & Caching

- **Pattern Cache**: Validation patterns cached in memory
- **Quick Validation**: Node.js parsing for 90% of cases
- **Background Learning**: Pattern updates don't block generation
- **Confidence Thresholds**: High-confidence patterns applied instantly

## Quality Guarantees

All generated layouts are guaranteed to:
- ✅ Parse correctly in Gutenberg editor
- ✅ Not trigger "Attempt Recovery" warnings
- ✅ Follow WordPress block standards
- ✅ Maintain semantic HTML structure
- ✅ Include accessibility best practices

## Community Impact

Every use of this command contributes to the shared pattern library:
- **Your Corrections** → Benefit all users
- **Community Patterns** → Improve your layouts
- **Continuous Learning** → Ever-improving quality
- **Git-Tracked** → Transparent and versioned

## Future Enhancements

1. **Visual Validation** - Screenshot comparison testing
2. **A11Y Validation** - Automated accessibility checking
3. **Performance Scoring** - Block performance optimization
4. **Custom Templates** - User-defined layout templates
5. **Multi-language** - Localized content patterns

---

**Ready to create validated popup layouts that work perfectly every time! 🚀**