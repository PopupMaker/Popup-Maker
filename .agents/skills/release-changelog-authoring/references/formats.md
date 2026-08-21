# Supported changelog formats

## Categorized format (preferred)

Use the relevant Keep a Changelog categories: `Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, and `Security`. Repositories may use an established user-facing alias such as `Improvements` for `Changed`, but do not invent a new synonym within a release.

```markdown
## v1.2.0 - 2026-08-21

### Added

- **Split testing** — Compare popup variants and measure the winner. [Learn more](https://example.com/feature/)

  Supported experiment types:

  - Popup versus popup.
  - Content variants.
  - Holdout tests.

### Fixed

- Prevented duplicate conversion attribution during checkout.
```

The blank line and indentation keep the paragraph and nested list inside the parent `<li>`. Use four spaces when a renderer or linter does not reliably accept two.

## Compact prefixed format

Use this for short releases without section headings. The prefix must be followed by `:` so ordinary prose beginning with “fixed” or “added” is not misclassified.

```markdown
- Added: Support for holdout tests.
- Changed: Improved purchase attribution accuracy.
- Fixed: Prevented duplicate conversion attribution during checkout.
- Security: Hardened webhook signature validation.
```

Accepted canonical prefixes are `Added:`, `Changed:`, `Deprecated:`, `Removed:`, `Fixed:`, and `Security:`. Existing repositories may retain `Improvement:` as a compatibility alias for `Changed:`. Prefer `Fixed:` over `Fix:` in new entries; parsers may continue accepting `Fix:` for historical content.

When displaying a prefix as a label, remove it only when the delimiter is present. Capitalize the remaining sentence without otherwise rewriting it.

## WordPress readme.txt

Use WordPress.org heading syntax while preserving the same category and list hierarchy:

```text
= 1.2.0 - 2026-08-21 =

= Added =

* **Split testing** — Compare popup variants and measure the winner.

= Fixed =

* Prevented duplicate conversion attribution during checkout.
```

Keep the public readme concise. Put extensive tutorials or feature explanations in durable documentation and link to them.

## Review checklist

- Version and date match the release metadata.
- Every item is under exactly one category.
- A compact release uses delimited prefixes consistently.
- Nested paragraphs/lists remain children of the intended item.
- Links and emphasis survive conversion between Markdown and readme.txt.
- No item is truncated in the middle of a list element.
- No existing tag, tagged file tree, release asset, or shipped commit changes.

