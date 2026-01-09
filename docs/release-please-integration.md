# Release Please Integration (Phase 2)

## Overview

Release Please automates semantic versioning and CHANGELOG generation based on conventional commits. It runs on a weekly schedule (Monday 9am UTC) to aggregate all commits into a single release PR.

## How It Works

1. **Weekly schedule**: Monday 9am UTC, Release Please analyzes commits since last release
2. **Version calculation**: Automatically calculates next version from commit types:
   - `feat:` → minor version bump (1.21.5 → 1.22.0)
   - `fix:` → patch version bump (1.21.5 → 1.21.6)
   - `BREAKING CHANGE:` → major version bump (1.21.5 → 2.0.0)
3. **Release PR created/updated**: Automated PR with changelog updates
4. **Rollup behavior**: PR accumulates commits until merged (no duplicate version bumps)
5. **Team review**: Review and approve the release PR
6. **Merge triggers release**: Creates tag and updates version files via `update-versions.js`

## Workflow Triggers

- **Weekly schedule** (`cron: '0 9 * * 1'`): Primary trigger - Monday 9am UTC
- **Manual dispatch** (`workflow_dispatch`): For testing or emergency patches
- **No push trigger**: Releases are weekly rollups, not per-commit

## Configuration Files

### `.release-please-config.json`
Defines how Release Please handles this repository:
- `release-type: simple` - Basic version bumping
- `changelog-path: changelog.txt` - WordPress-style changelog file

### `.release-please-manifest.json`
Tracks current version:
```json
{
  ".": "1.21.5"
}
```

Updated automatically by Release Please when releases are created.

## Version File Updates

When Release Please creates a release, the workflow uses the existing `bin/update-versions.js` script (via `npm run version:update`) to update all version references:

- `popup-maker.php` - Plugin header version
- `bootstrap.php` - Class version constant
- `readme.txt` - Stable tag
- `package.json` / `composer.json` - Package versions
- PHP docblocks - `@since X.X.X` annotations

This approach leverages the battle-tested version update script already used in manual releases.

## Workflow File

`.github/workflows/release-please.yml`:
1. Runs on weekly schedule or manual dispatch
2. Analyzes commits since last release
3. Creates/updates release PR with calculated version
4. When PR is merged: runs `npm run version:update` to update all files
5. Outputs release metadata for Phase 3+ (Slack notifications, deployments)

## Integration Points

### Phase 1 Foundation
- **Commitlint** validates commit format (Phase 1)
- **Release Please** processes valid commits (Phase 2)

### Phase 3 Slack Approval (Future)
- Release Please PR triggers Slack notification
- Team approves via Slack button
- Approval merges PR and triggers release

### Phase 4 Testing (Future)
- Merged release PR triggers InstaWP test instance
- Automated testing validates release

### Phase 5 Deployment (Future)
- Successful tests trigger WordPress.org and EDD deployment
- Automated release to production

## Weekly Release Schedule

**Monday 9am UTC**:
1. Release Please creates/updates PR with week's accumulated changes
2. Slack notification sent to team (Phase 3)
3. Team reviews changelog and approves
4. Merge triggers version updates, testing, and deployment

## Emergency Patches

For critical hotfixes that can't wait for weekly schedule:

```bash
npm run prepare-release:patch -- --auto
```

This bypasses Release Please and uses the manual release process directly.

## Version Bumping Rules

| Commit Type | Version Change | Example |
|-------------|----------------|---------|
| `feat:` | Minor bump | 1.21.5 → 1.22.0 |
| `fix:` | Patch bump | 1.21.5 → 1.21.6 |
| `perf:` | Patch bump | 1.21.5 → 1.21.6 |
| `BREAKING CHANGE:` | Major bump | 1.21.5 → 2.0.0 |
| `chore:`, `docs:`, `style:` | No bump | No release |

## Testing Phase 2

To test Release Please integration:

1. **Trigger manually**: Go to Actions → Release Please → Run workflow

2. **Watch for PR**: Release Please should create a PR within 1-2 minutes

3. **Review PR**: Check that:
   - Version calculated correctly
   - Changelog updated with commit messages

4. **Merge PR**: Merging will:
   - Tag the release
   - Run `npm run version:update` to update all files
   - Trigger Phase 3+ workflows (when implemented)

## Next Steps

- **Phase 3**: Slack approval workflow with approval buttons
- **Phase 4**: InstaWP testing integration
- **Phase 5**: Automated deployment to WordPress.org and EDD
