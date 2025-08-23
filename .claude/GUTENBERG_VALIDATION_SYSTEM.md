# Gutenberg Block Validation System Documentation

## Overview

A comprehensive validation and learning system for Gutenberg blocks used in Popup Maker layouts. Automatically fixes common block validation issues and learns from corrections to improve future block generation.

## Architecture

### Multi-Tier Validation Approach

1. **Pattern Library** (Instant) - Pre-validated block patterns stored in git
2. **Node Validator** (Fast) - WordPress block parser validation with JSON fixes
3. **Playwright Validator** (Comprehensive) - Browser-based validation with automated recovery
4. **Learning System** - Captures corrections and builds pattern library

## File Structure

```
popup-maker--gutenberg-validation/
├── .claude/
│   ├── commands/
│   │   └── pm-validate-blocks.md          # Command definition
│   └── block-patterns/
│       ├── newsletter.validated.json      # Newsletter layout patterns
│       ├── discount.validated.json        # Discount/exit intent patterns
│       ├── cookie.validated.json          # Cookie notice patterns
│       ├── product.validated.json         # Product announcement patterns
│       └── corrections-log.json           # Learning history and statistics
├── bin/
│   └── validate-gutenberg-blocks.js       # Node.js validator
├── tests/
│   └── e2e/
│       └── gutenberg-validator.spec.ts    # Playwright validator
├── broken-content.html                    # Test file with validation issues
└── final-corrected-content.html          # Successfully corrected output
```

## Commands

### `/pm-validate-blocks` - Main Command

**Purpose**: Validates and corrects Gutenberg block HTML using learned patterns and automated testing.

#### Basic Validation
```bash
/pm-validate-blocks [html]                           # Validate block HTML content
/pm-validate-blocks --file path/to/blocks.html       # Validate from file
/pm-validate-blocks --type newsletter                # Specify layout type for pattern matching
```

#### Learning & Management
```bash
/pm-validate-blocks --learn                          # Run Playwright learning mode
/pm-validate-blocks --stats                          # Show validation statistics
/pm-validate-blocks --patterns                       # List available validated patterns
/pm-validate-blocks --reset-patterns                 # Reset pattern library (caution!)
```

#### Integration Modes
```bash
/pm-validate-blocks --auto-fix                       # Automatically apply known corrections
/pm-validate-blocks --dry-run                        # Validate without applying changes
/pm-validate-blocks --export-patterns                # Export patterns for sharing
```

## Validation Process

### Step 1: Pattern Matching (Instant)

Checks incoming HTML against known validated patterns stored in `.claude/block-patterns/`:

```javascript
// Example pattern structure
{
  "version": "1.0.0",
  "layoutType": "newsletter",
  "patterns": {
    "group-wrapper": {
      "original": "<!-- wp:group {...invalid} -->",
      "validated": "<!-- wp:group {...corrected} -->",
      "corrections": ["escaped quotes", "valid JSON"],
      "confidence": 0.98
    }
  }
}
```

### Step 2: Node Validation (Fast)

Uses WordPress `@wordpress/blocks` package for syntax validation:

```javascript
const { parse } = require('@wordpress/blocks');
const blocks = parse(html);
// Check for parse errors or invalid blocks
```

**Key Features:**
- JSON parsing validation in block comments
- Automatic JSON structure correction
- Block attribute validation
- CSS property filtering

### Step 3: Playwright Validation (Comprehensive)

Browser-based validation with automated recovery:

1. Create test post in wp-env
2. Insert block HTML
3. Detect validation errors
4. Apply automatic recovery
5. Extract corrected blocks
6. Update pattern library

### Step 4: Learning System

Captures corrections and builds pattern library:
- Pattern Recognition: Identifies common correction patterns
- Success Tracking: Monitors validation success rates
- Auto-Improvement: Updates patterns based on new learnings
- Confidence Scoring: Rates reliability of each pattern

## Common Block Issues Fixed

### 1. Invalid JSON in Block Comments
**Problem**: Unescaped quotes, trailing commas, malformed JSON
```html
<!-- wp:group {"invalid":"json} -->
```
**Solution**: Automatic JSON parsing and reconstruction
```html
<!-- wp:group {"valid":"json"} -->
```

### 2. Missing Block Attributes
**Problem**: Block comments missing required attributes
```html
<!-- wp:image -->
<img width="100" height="100"/>
```
**Solution**: Extract attributes from HTML content
```html
<!-- wp:image {"width":100,"height":100} -->
<img width="100" height="100"/>
```

### 3. Block Hierarchy Violations
**Problem**: Improper block nesting
**Solution**: Structural validation and correction

### 4. CSS Property Issues
**Problem**: Invalid CSS properties in block attributes
**Solution**: Automatic filtering of unsupported properties

## Usage Examples

### Basic Validation
```bash
# Validate specific HTML content
/pm-validate-blocks '<!-- wp:group {"invalid":"json} -->'

# Validate from file
/pm-validate-blocks --file newsletter-layout.html --type newsletter

# Get corrected output
/pm-validate-blocks --file broken-blocks.html --auto-fix
```

### Learning Mode
```bash
# Run Playwright learning session
/pm-validate-blocks --learn

# Check what the system has learned
/pm-validate-blocks --stats
/pm-validate-blocks --patterns
```

### Integration with Layout Generation
```bash
# Automatic validation flow
/pm-gutenberg-layout newsletter hero
   ↓
1. Generate initial HTML
2. Check pattern library
3. Apply known corrections
4. Validate with Node parser
5. Return validated HTML
```

## API Reference

### Node.js Validator Class

```javascript
const GutenbergBlockValidator = require('./bin/validate-gutenberg-blocks.js');

const validator = new GutenbergBlockValidator();

// Validate blocks
const result = await validator.validateBlocks(html, 'newsletter');
console.log(result.isValid);        // boolean
console.log(result.errors);         // array of error messages
console.log(result.corrections);    // array of applied corrections
console.log(result.correctedHtml);  // fixed HTML output
console.log(result.confidence);     // confidence score (0-1)
```

### Validation Result Object
```javascript
{
  isValid: boolean,           // Overall validation status
  errors: string[],           // List of validation errors found
  corrections: object[],      // List of corrections applied
  correctedHtml: string,      // HTML with corrections applied
  confidence: number,         // Confidence score 0-1
  usedPatterns: string[],     // Pattern types used for corrections
  processingTime: number      // Time taken in milliseconds
}
```

### Pattern Library Management

```javascript
// Get validation statistics
const stats = await validator.getStatistics();

// Example stats structure
{
  global: {
    totalValidations: 145,
    successfulValidations: 142,
    failedValidations: 3,
    averageConfidence: 0.94
  },
  patterns: {
    newsletter: {
      timesUsed: 87,
      successRate: 0.98,
      lastValidation: "2024-01-15T10:00:00Z"
    }
  }
}
```

## Error Handling & Recovery

### Fallback Strategies
- **Node Validation Fails** → Fallback to Playwright
- **Playwright Unavailable** → Return best-effort corrections
- **Pattern Library Corrupt** → Rebuild from backups
- **wp-env Not Running** → Auto-start or graceful degradation

### Error Types
1. **JSON Parse Errors**: Fixed automatically with proper escaping
2. **Block Structure Errors**: Corrected using pattern matching
3. **Attribute Mismatch**: Resolved by HTML content analysis
4. **CSS Property Errors**: Invalid properties filtered out

## Performance & Caching

- **Pattern Cache**: In-memory caching of validated patterns
- **Quick Validation**: Node.js parsing for 90% of cases
- **Fallback Strategy**: Playwright only for complex/unknown patterns
- **Batch Processing**: Multiple blocks validated together

## Git Integration

All patterns are version controlled:
```bash
git add .claude/block-patterns/*.json
git commit -m "Update block validation patterns"
git push origin gutenberg-validation
```

## Configuration

### Validation Settings
```javascript
// In validate-gutenberg-blocks.js
const config = {
  confidenceThreshold: 0.8,      // Minimum confidence for auto-fix
  maxRetries: 3,                 // Max validation attempts
  enableLearning: true,          // Enable pattern learning
  cachePatterns: true            // Cache patterns in memory
};
```

### Pattern Library Configuration
```json
{
  "version": "1.0.0",
  "lastUpdated": "2024-01-15T10:00:00Z",
  "layoutType": "newsletter",
  "description": "Newsletter signup form patterns",
  "patterns": {
    // Pattern definitions
  },
  "statistics": {
    "timesUsed": 145,
    "successRate": 0.98,
    "lastValidation": "2024-01-15T10:00:00Z"
  }
}
```

## Troubleshooting

### Common Issues

**Issue**: Blocks still show "attempt recovery" messages
**Solution**: 
1. Check console errors for specific validation failures
2. Run with `--dry-run` to see what corrections are applied
3. Use browser dev tools to inspect actual block structure

**Issue**: Pattern learning not working
**Solution**:
1. Ensure `.claude/block-patterns/` directory exists
2. Check file permissions for pattern files
3. Verify corrections-log.json is writable

**Issue**: Playwright validation failing
**Solution**:
1. Ensure wp-env is running
2. Check browser installation: `npx playwright install`
3. Verify WordPress admin access

### Debug Mode
```bash
# Enable verbose logging
NODE_DEBUG=validator /pm-validate-blocks --file test.html

# Check validation details
/pm-validate-blocks --file test.html --dry-run
```

## Future Enhancements

1. **AI Pattern Learning**: Use machine learning for pattern detection
2. **Community Patterns**: Share patterns across installations  
3. **Real-time Validation**: Validate as user types in layout generator
4. **Visual Diff Tool**: Show before/after validation changes
5. **Performance Analytics**: Track validation performance metrics

## Contributing

When adding new pattern types:

1. Create pattern file in `.claude/block-patterns/[type].validated.json`
2. Add validation logic in `generateCorrectAttributes()` method
3. Update pattern matching in `applyPatternCorrections()`
4. Add tests in `tests/e2e/gutenberg-validator.spec.ts`
5. Update documentation

## License

Part of the Popup Maker plugin ecosystem. Use in accordance with plugin licensing terms.