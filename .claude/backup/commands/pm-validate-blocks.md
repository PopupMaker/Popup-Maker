# /pm-validate-blocks - Gutenberg Block Validation System

Validates and corrects Gutenberg block HTML using learned patterns and automated testing.

## Overview

This command provides comprehensive validation for Gutenberg blocks used in popup layouts. It uses a multi-tier approach:
1. **Pattern Library**: Pre-validated block patterns stored in git
2. **Node Validator**: Fast WordPress block parser validation  
3. **Playwright Validator**: Browser-based validation with automated recovery
4. **Learning System**: Captures corrections and builds pattern library

## Usage

**Basic Validation:**
- `/pm-validate-blocks [html]` - Validate block HTML content
- `/pm-validate-blocks --file path/to/blocks.html` - Validate from file

**Learning & Management:**
- `/pm-validate-blocks --learn` - Run Playwright learning mode
- `/pm-validate-blocks --stats` - Show validation statistics
- `/pm-validate-blocks --patterns` - List available validated patterns
- `/pm-validate-blocks --reset-patterns` - Reset pattern library (caution!)

**Integration Modes:**
- `/pm-validate-blocks --auto-fix` - Automatically apply known corrections
- `/pm-validate-blocks --dry-run` - Validate without applying changes
- `/pm-validate-blocks --export-patterns` - Export patterns for sharing

## Validation Process

### 1. Pattern Matching (Instant)
```bash
# Check against known validated patterns
.claude/block-patterns/newsletter.validated.json
.claude/block-patterns/discount.validated.json
```

### 2. Node Validation (Fast)
```javascript
// Use WordPress block parser for syntax validation
const { parse, validateBlock } = require('@wordpress/blocks');
```

### 3. Playwright Validation (Comprehensive)
```typescript
// Browser-based validation with automated recovery
1. Create test post in wp-env
2. Insert block HTML
3. Detect validation errors
4. Apply automatic recovery
5. Extract corrected blocks
6. Update pattern library
```

## Pattern Library Structure

**Location**: `.claude/block-patterns/`

**Files:**
- `newsletter.validated.json` - Newsletter signup patterns
- `discount.validated.json` - Discount/exit intent patterns  
- `cookie.validated.json` - Cookie notice patterns
- `product.validated.json` - Product announcement patterns
- `corrections-log.json` - Learning history and statistics

**Pattern Format:**
```json
{
  "version": "1.0.0",
  "lastUpdated": "2024-01-15T10:00:00Z",
  "layoutType": "newsletter",
  "patterns": {
    "group-wrapper": {
      "original": "<!-- wp:group {...invalid} -->",
      "validated": "<!-- wp:group {...corrected} -->",
      "corrections": ["escaped quotes", "valid JSON"],
      "confidence": 0.98
    }
  },
  "statistics": {
    "timesUsed": 145,
    "successRate": 0.98,
    "lastValidation": "2024-01-15T10:00:00Z"
  }
}
```

## Integration with Layout Generator

The validation system automatically integrates with `/pm-gutenberg-layout`:

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

## Command Implementation

### Core Files
- `.claude/pm-validate-blocks.md` - This command definition
- `bin/validate-gutenberg-blocks.js` - Node.js validator
- `tests/e2e/gutenberg-validator.spec.ts` - Playwright validator
- `.claude/block-patterns/*.json` - Pattern library

### Validation Functions

**Quick Validation (Node.js)**:
```javascript
async function validateBlocks(html) {
  // 1. Parse blocks using WordPress parser
  // 2. Check against known patterns
  // 3. Apply automatic corrections
  // 4. Return validated HTML + confidence score
}
```

**Deep Validation (Playwright)**:
```typescript
async function validateInBrowser(html) {
  // 1. Start wp-env if needed
  // 2. Login to WordPress admin
  // 3. Create new post
  // 4. Insert blocks in code editor
  // 5. Switch to visual editor
  // 6. Detect validation errors
  // 7. Apply recovery corrections
  // 8. Extract corrected HTML
  // 9. Update pattern library
}
```

## Error Detection & Correction

### Common Block Issues:
1. **Invalid JSON in block comments** - Unescaped quotes, trailing commas
2. **Missing closing tags** - Unclosed block comment delimiters
3. **Invalid attributes** - CSS properties not supported in blocks
4. **Improper nesting** - Block hierarchy violations
5. **Encoding issues** - Special characters breaking JSON

### Automated Corrections:
1. **JSON Escaping** - Fix quotes and special characters
2. **Tag Completion** - Add missing closing tags
3. **Attribute Filtering** - Remove invalid CSS properties
4. **Structure Fixing** - Correct block nesting

## Learning System

The system learns from each validation:

1. **Pattern Recognition** - Identifies common correction patterns
2. **Success Tracking** - Monitors validation success rates  
3. **Auto-Improvement** - Updates patterns based on new learnings
4. **Confidence Scoring** - Rates reliability of each pattern

### Learning Workflow:
```
New Block → Validate → Errors Found → Apply Recovery → Extract Corrections → Update Patterns → Commit to Git
```

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

## Usage Examples

**Basic validation:**
```bash
/pm-validate-blocks '<!-- wp:group {"invalid":"json} -->'
```

**Learn from errors:**
```bash
/pm-validate-blocks --learn
```

**Integration with layout generation:**
```bash
/pm-gutenberg-layout newsletter --validate
```

**Check pattern library:**
```bash
/pm-validate-blocks --patterns --stats
```

## Error Handling

- **Node Validation Fails** → Fallback to Playwright
- **Playwright Unavailable** → Return best-effort corrections
- **Pattern Library Corrupt** → Rebuild from backups
- **wp-env Not Running** → Auto-start or graceful degradation

## Future Enhancements

1. **AI Pattern Learning** - Use machine learning for pattern detection
2. **Community Patterns** - Share patterns across installations
3. **Real-time Validation** - Validate as user types in layout generator
4. **Visual Diff Tool** - Show before/after validation changes
5. **Performance Analytics** - Track validation performance metrics

## Implementation Notes

- Uses existing Playwright infrastructure at `tests/e2e/`
- Leverages WordPress's `@wordpress/blocks` npm package
- Stores learning data in git-tracked `.claude/block-patterns/`
- Integrates seamlessly with existing `/pm-gutenberg-layout` command
- Provides both CLI and programmatic APIs