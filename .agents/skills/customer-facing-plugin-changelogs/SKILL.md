---
name: customer-facing-plugin-changelogs
description: Write and review concise customer-facing changelog entries for WordPress plugins when preparing a release, describing confirmed behavior, limits, and add-on ownership accurately.
license: MIT
metadata:
  version: "1.0.1"
---

# Customer-Facing Plugin Changelogs

Write release notes that help a merchant understand what changed, what they can do now, and any practical limits or required add-ons. Keep customer-facing notes separate from developer and maintenance notes.

## When to use

- When drafting or reviewing a WordPress plugin changelog entry for a release.
- When a feature spans a core plugin, a paid add-on, or a product-specific integration and ownership must be clear.
- When a change has configurable modes, setup requirements, restrictions, security implications, or dependencies customers need to know.

Do not use this skill to decide whether unreleased work should ship, invent release scope, or replace the repository's release process.

## Workflow

1. **Inspect the release contract.** Read the repository's changelog, release-note parser/category conventions, and any local release instructions. Preserve headings, labels, link style, punctuation, and security advisory format. Do not create a version number or release date.
2. **Confirm what ships.** Read the implementation, relevant tests or acceptance evidence, and the exact proposed diff or release scope. Identify the owning product, supported integrations, actual defaults, user-visible controls, and restrictions. Include verified changes in the confirmed target release even while they are under an Unreleased heading. Exclude deferred, speculative, unverified, and out-of-scope behavior.
3. **Separate audiences.** Put only user-relevant behavior in the customer changelog. Keep customer-required upgrade or migration steps and visible data changes or interruptions in the customer notes. Put internal implementation details, migration internals, architecture, test notes, and follow-up work in a developer or maintenance section/document that the project already supports. Do not add internal sections to a customer changelog unless its existing convention explicitly calls for them.
4. **Lead with the outcome.** Start with what the merchant can do, see, or rely on. State conditions and practical limits in plain language. Name an add-on only when customers need it, and attribute the feature to the product that actually ships it. For the Popup Maker family, Core must not claim add-on functionality; an integration add-on must not claim ownership of Pro's shared workflow. Apply equivalent ownership checks to other product families.
5. **Describe choices exactly.** Distinguish defaults from optional settings. When generated coupons are in scope, distinguish grouped reporting codes from separate native coupons only if those modes are supported. State the integration-level default and whether each CTA inherits or overrides that setting, only when both controls ship. State integration-specific behavior, coupon stacking or replacement limits, and any CTA override only when verified in the shipped implementation. Explain that native checkout behavior remains controlled by its commerce platform where relevant.
6. **Keep fixes in release context.** Do not describe a fix to a newly introduced, unreleased feature as a bug customers experienced. Describe an improvement to an already released feature only when the prior customer behavior and shipped correction are verified. Preserve security advisories and their required detail without softening or reclassifying them.
7. **Draft and verify.** Write concise, active-voice bullets in the repository's established category. Use the [examples](references/examples.md) and [checklist](references/checklist.md); product-specific examples apply only when verified for the target plugin. Check the rendered/parser input shape, product attribution, dependencies, defaults, restrictions, and claims against the source and diff. Leave terminology for new UI in plain language until the owner has tested and finalized the labels.

## Writing rules

- Prefer a direct customer outcome, followed by the condition or limit that matters.
- Explain observable behavior, not internal mechanisms.
- Avoid implementation terms such as “provider-neutral bridge,” “ledger,” “immutable snapshot,” and “stub.”
- Do not promise speed, compatibility, reliability, or security beyond the evidence and approved security wording.
- Do not imply a capability is enabled by default when it is optional, or available without its required add-on.
- Do not use “fixed” for a regression or defect that was never present in a released customer experience.
- Keep developer and maintenance notes out of customer-facing entries.

## Output

Return a proposed customer changelog entry in the repository's existing format, plus any separate developer/maintenance note only when requested or already required by the release workflow. Include a short evidence note for reviewers naming the source/diff checked and any unresolved release or terminology dependency. Do not edit historical release entries or unrelated changelog content.

## Anti-patterns

- Writing from a feature brief without checking the shipped source and exact release diff.
- Combining Core, Pro, Ecommerce Popups, or LMS Popups ownership into a vague product-wide claim.
- Calling grouped coupon behavior “individual native coupons” when that is an optional mode.
- Omitting checkout limitations or presenting a deferred integration as available.
- Turning an internal refactor, test fix, or unreleased-feature correction into a customer benefit or customer bug fix.
- Changing category conventions, security advisories, parser expectations, version numbers, or release dates as part of copy editing.
