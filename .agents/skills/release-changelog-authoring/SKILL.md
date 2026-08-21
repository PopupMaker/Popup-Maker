---
name: release-changelog-authoring
description: Author or revise Popup Maker release notes, CHANGELOG.md entries, readme.txt changelogs, or GitHub release descriptions using the supported categorized or prefixed formats. Use whenever preparing a release or changing shipped-release documentation.
---

# Release changelog authoring

Read [the format reference](references/formats.md) before editing release notes.

1. Describe notable user-visible outcomes. Omit internal tooling and implementation details unless they affect users, integrators, compatibility, privacy, or security.
2. Use one format consistently within a release:
   - Prefer categorized Keep a Changelog-style sections for substantial releases.
   - A compact prefixed list is valid for small releases when every classified item uses an explicit delimiter such as `Fixed: `.
3. Preserve links, emphasis, nested lists, and explanatory paragraphs. Nested material must be indented beneath its parent list item; never infer parentage from proximity alone.
4. Keep a feature entry concise. Link to a dedicated explainer when the supporting material is longer than a short nested list.
5. Update the current branch's `CHANGELOG.md`, `readme.txt`, and GitHub release draft/source together when the repository workflow requires them.
6. Treat existing tags as immutable. Never rewrite files in an existing tag or move/recreate a shipped tag. An explicitly authorized correction may update the GitHub release description without altering its tag, commit, or assets.
7. Before publishing or rewriting historical notes, show or save a reviewable diff and verify headings, list nesting, links, and user-facing meaning.
8. If syncing WordPress content, operate only on the `ca_release` post type. Do not modify pages, posts, settings, or any other post type.

