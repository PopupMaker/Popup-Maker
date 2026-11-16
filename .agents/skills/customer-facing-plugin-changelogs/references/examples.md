# Changelog examples

Use these as wording patterns only. Confirm the exact behavior, terminology, ownership, and release scope in the current product before adapting them.

## Generated coupons

**Before — too broad:**

> Added individual native coupons for every generated offer.

This hides the actual default and treats an optional mode as universal.

**After — accurate when source confirms these controls and behavior:**

> With LMS Popups, generated discounts use grouped mode by default: visitor codes are collected under one reporting coupon for a CTA. Change the integration default to individual mode to create a separate native LifterLMS coupon for each code, or set a CTA to inherit the integration default or use another method. Existing coupons keep their current behavior.

Use the actual integration name and shipped behavior. If the release only supports one platform or add-on, name it in the first sentence. State a restriction such as replacement-only checkout only when it ships. Describe integration defaults and CTA overrides separately, and do not imply a CTA choice changes a store-wide setting. Avoid final UI labels until the product owner has tested and approved them.

## Order attribution

**Before — internal and overclaimed:**

> Added a provider-neutral bridge, immutable attribution ledger, and durable snapshot reconciliation.

**After — customer outcome:**

> See which Popup Maker popup and CTA is linked to an order in WooCommerce or Easy Digital Downloads. Filter the order list by popup or CTA, view attribution badges, and use WP-CLI to backfill existing orders.

This example reflects an Ecommerce Popups release scope that includes both integrations, order-list filters, badges, and the backfill command. For another release, name only the platforms and controls verified in its shipped source and diff. If attribution is owned by an add-on, keep the note in that add-on's changelog. Do not claim cross-platform or complete attribution from partial integration coverage.

## Intentionally omitted internal notes

Keep notes like these in engineering documentation or maintenance records, not the customer changelog:

- “Replaced the provider-neutral bridge with a ledger and immutable snapshot.”
- “Added stubs for the future LMS integration.”
- “Fixed initialization in the new feature before its first release.”
- “Refactored coupon metadata and added unit coverage.”
- “Performance should improve” without measured evidence and an approved customer-facing claim.

A customer-visible effect may still merit a separate note, but state only what users can observe and what the release evidence supports.
