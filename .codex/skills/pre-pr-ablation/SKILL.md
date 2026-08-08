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
2. Run the same checks against the target branch in a separate clean worktree or
   equivalent isolated checkout before accepting failures or reduced counts as
   pre-existing. Record its test counts, assertions/skips when stable, and
   lint/static-analysis totals, then record the proposed change's baseline.
3. Build the smallest realistic fixture for each failure mode. For integrations,
   use the installed third-party version and its real editor, preview, visitor
   path, save path, and late/deferred path when applicable.
4. Record temporary environment and persistent-state changes so they can be
   restored: options, roles/capabilities, active plugins/themes, database
   records, fixture metadata, generated assets, and caches.
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
Before ablating a production test accessor or instrumentation hook, make its
test observe public behavior or use external test instrumentation; otherwise a
test failure proves only that the test is coupled to the candidate.

## Run the ablation loop

Create a reversible checkpoint or patch of the original index and worktree
before the first ablation. Preserve unrelated changes and never use restoration
steps that reset or overwrite them.

Test one candidate at a time:

1. Restore or recreate the fixture and all persistent runtime state from the
   same pre-trial snapshot. Confirm the full current solution passes the
   smallest sensitive test and, for user-facing behavior, works in the real UI.
2. Remove exactly one candidate as a coherent change. When it is structural,
   remove its dependent references so the variant remains syntactically valid,
   autoloadable, and interface-compliant without removing unrelated behavior.
3. Restore the same pre-trial fixture and persistent-state snapshot, regenerate
   every artifact needed for the edited layer, and invalidate caches.
4. Confirm the variant builds or loads successfully, then repeat the same
   focused test and UI interaction.
5. Classify the candidate:

   - `required`: removal reproduces a failure and restoration fixes it;
   - `conditional`: required only for a named version, mode, timing, or path;
   - `redundant`: removal changes no contract or safety invariant;
   - `harmful`: removal fixes behavior or prevents duplicate work and
     restoration reproduces the defect;
   - `unproven`: the test cannot distinguish it; improve the fixture before
     deciding.

6. Restore required code. For conditional code, narrow it behind the
   demonstrated condition or prove it is inert outside that condition, then
   test both sides. Delete redundant/harmful code.
7. Record the command, meaningful output, and visible result in an ablation
   table or durable discovery note.

Repeat remove/restore trials when timing or test variance could affect a
classification. Do not infer causality from a single flaky result.

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
6. Gate optional third-party calls on the required symbol and method at the
   point of use, and emit an actionable diagnostic when the API is unavailable.
   Surface an explicit failure for required operations. Fail closed when a call
   protects authorization, validation, escaping, data integrity, or destructive
   action safety.

After single-unit ablations, retest the minimal combined set. One removal can
make another branch newly redundant, and two individually harmless removals can
expose a coupling. When candidates have overlapping ownership, also test viable
alternative sets rather than assuming the first surviving set is minimal.
Exercise early/late timing and coexistence with other active integrations.

## Clean and package

Before declaring the branch ready:

1. Remove temporary instrumentation, production test accessors, comments that
   describe deleted experiments, and unused imports/files.
2. Restore the runtime environment and clear generated caches once more.
3. Run the exact full baselines and confirm new code contributes no accepted
   warning or static-analysis error.
4. Review the final diff for PHP/language-version constraints, third-party hook
   defensiveness, and accidental generated or unrelated changes.
5. When the project produces a package or deployable artifact, run its
   production package/build check and validate the resulting artifact through
   the target install or smoke path. Otherwise record why packaging does not
   apply.
6. Commit by coherent behavioral reason. The PR body should describe the final
   minimal design, runtime evidence, ablations, sanitized verification output,
   and any intentionally native/no-provider paths.

Do not claim a behavior works without durable evidence. For automated checks,
record the command and meaningful output. For manual UI checks, record the
fixture, interaction steps, and visible result. Redact credentials, tokens,
signed URLs, customer data, and other secrets from recorded commands and output;
keep unredacted logs only in an appropriately protected local location. Do not
finish with `unproven` candidates silently present; either improve the
experiment, remove the candidate, or state the unresolved risk explicitly.
