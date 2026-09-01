# GitHub Actions

Popup Maker uses reviewed pull requests as the only production publication gate.

## Full releases

1. Prepare `release/X.Y.Z` from `develop`.
2. Update the plugin versions and dated changelogs.
3. Open the PR against `master` with `pnpm run prepare-release finish`.
4. Review the candidate ZIP and required checks in the PR.
5. Approve and merge the PR.

The merged PR is re-authorized before any external write. It must:

-   be merged into `master`;
-   come from this repository;
-   have a current maintainer approval or be merged by an authorized maintainer;
-   use the exact `release/X.Y.Z` branch name;
-   advance every canonical version field together;
-   include dated `CHANGELOG.md` and `readme.txt` entries; and
-   not reuse a version tag that points to another commit.

After those checks pass, `release.yml` builds one canonical ZIP and uses that same artifact for:

-   the version tag and GitHub Release;
-   the EDD release record;
-   Google Drive;
-   WordPress.org SVN;
-   the review-required visual changelog draft; and
-   Slack status.

It also attempts to open a `master` to `develop` back-sync PR. A failed downstream step is visible and can be retried by manually running the workflow with the original merged PR number.

Direct tags, direct pushes to `master`, and unapproved PRs do not publish a plugin release.

## WordPress.org readme and assets

A PR containing only `readme.txt` and/or files below `.wordpress-org/` may be opened against `master`. After it is approved and merged, `deploy-readme-assets.yml` re-checks the approval and exact file list, then syncs only those files to WordPress.org.

A mixed code/readme PR never enters this narrow path. Release PRs deploy their readme and assets with the full canonical package.

## PR publication preview

`publication-gate.yml` runs on every PR to `master` and states which result a merge would have:

-   full release;
-   readme/assets-only update; or
-   no publication.

Release PRs also build and verify a downloadable candidate ZIP before approval.

## Development builds

Use `build.yml` manually for QA packages from a branch, tag, or commit. These artifacts never publish to a production channel.

## Changelog retries and edits

`changelog-sync.yml` remains available for GitHub Release edits and explicit retries. The full release workflow calls the same pinned, draft-first sync directly because actions created with `GITHUB_TOKEN` do not reliably trigger another workflow.

## Security boundaries

-   Production workflows use the trusted workflow from `master`, never PR-authored workflow code.
-   Publication PRs must originate inside this repository.
-   External actions and reusable workflows are pinned to immutable commit SHAs.
-   WordPress.org credentials are exposed only after the merged PR is authorized.
-   The WordPress changelog receiver uses its dedicated route-scoped token and creates drafts for human review.
