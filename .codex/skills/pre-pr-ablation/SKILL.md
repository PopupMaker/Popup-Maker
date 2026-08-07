---
name: pre-pr-ablation
description: Use before finalizing or opening/updating a PR for non-trivial code changes, especially third-party integrations, compatibility layers, hooks and filters, lifecycle code, providers, abstractions, caching, or asynchronous behavior. Systematically remove and restore experimental code to prove every remaining part is necessary. Do not use for copy-only edits, simple visual UI changes, generated-file updates, or deterministic mechanical refactors.
---

# Pre-PR Ablation

Ship the smallest behaviorally complete change. Treat working experimental code
as a hypothesis: remove each part, reproduce the relevant behavior, and retain it
only when evidence shows that it owns a requirement or failure mode.

## Establish the contract

Before removing anything:

1. List the user-visible behaviors and safety invariants the change must preserve.
2. Record exact project baselines: test counts, assertions/skips when stable,
   lint/static-analysis totals, and any accepted pre-existing failures.
3. Build the smallest realistic fixture for each failure mode. For integrations,
   use the installed third-party version and its real editor, preview, visitor
   path, save path, and late/deferred path when applicable.
4. Record temporary environment changes so they can be restored: options,
   roles/capabilities, active plugins/themes, fixture metadata, and caches.
5. Identify regeneration boundaries such as autoloaders, prefixed vendor trees,
   bundles, compiled CSS, page caches, and application asset caches.

Do not accept computed styles, DOM presence, or a passing assertion as the sole
evidence for a user-facing workflow. Interact with and inspect the actual UI.

## Inventory every candidate

Review the complete diff and enumerate each independently removable unit:

- hooks, filters, priorities, callbacks, and request recognizers;
- interfaces, traits, providers, adapters, coordinator branches, and state;
- retries, memoization, finalizers, fallbacks, feature gates, and catches;
- JavaScript listeners, ready passes, markers, reinitializers, and CSS overrides;
- test-only accessors, instrumentation, debug logging, and compatibility shims;
- duplicated ownership between the application and a third party.

Include existing experimental code touched by the work, not only the latest
addition. State the claimed behavior for every candidate before testing it.

## Run the ablation loop

Test one candidate at a time:

1. Confirm the full current solution passes the smallest sensitive test and, for
   user-facing behavior, works in the real UI.
2. Remove or comment out exactly one candidate. Do not combine removals yet.
3. Regenerate every artifact needed for the edited layer and invalidate caches.
4. Repeat the same focused test and UI interaction under the same fixture.
5. Classify the candidate:

   - `required`: removal reproduces a failure and restoration fixes it;
   - `conditional`: required only for a named version, mode, timing, or path;
   - `redundant`: removal changes no contract or safety invariant;
   - `harmful`: removal fixes behavior or prevents duplicate work;
   - `unproven`: the test cannot distinguish it; improve the fixture before
     deciding.

6. Restore required/conditional code and delete redundant/harmful code.
7. Record the command, meaningful output, and visible result in an ablation
   table or durable discovery note.

Never keep code merely because it looks defensive. Tie it to a reproduced edge
case or an explicit invariant. For authorization, validation, escaping, data
integrity, and destructive-action guards, use adversarial tests instead of
removing protection on a happy path.

## Prove the abstraction

For integration or framework work:

1. Fit at least two consumers with meaningfully different lifecycles when the
   available test environment permits it.
2. Include a negative control that already works natively and should need no
   integration capability.
3. Let consumers implement only capabilities they actually possess. Change the
   interface when a consumer would otherwise fake compliance.
4. Keep lifecycle, ordering, authorization, batching, and deduplication in the
   coordinator. Keep each provider operation narrow and third-party-specific.
5. Separate independent ownership dimensions such as editing, rendering,
   assets, preview URLs, DOM ownership, and initialization timing.
6. Gate every third-party call on the required symbol and method at the point of
   use so API drift becomes a no-op rather than a fatal error.

After single-unit ablations, retest the minimal combined set. One removal can
make another branch newly redundant, and two individually harmless removals can
expose a coupling. Exercise early/late timing and coexistence with other active
integrations.

## Clean and package

Before declaring the branch ready:

1. Remove temporary instrumentation, production test accessors, comments that
   describe deleted experiments, and unused imports/files.
2. Restore the runtime environment and clear generated caches once more.
3. Run the exact full baselines and confirm new code contributes no accepted
   warning or static-analysis error.
4. Review the final diff for PHP/language-version constraints, third-party hook
   defensiveness, and accidental generated or unrelated changes.
5. Commit by coherent behavioral reason. The PR body should describe the final
   minimal design, runtime evidence, ablations, exact verification output, and
   any intentionally native/no-provider paths.

Do not claim a behavior works without the command and its output. Do not finish
with `unproven` candidates silently present; either improve the experiment,
remove the candidate, or state the unresolved risk explicitly.
