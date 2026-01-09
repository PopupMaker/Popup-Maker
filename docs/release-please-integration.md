# Release Please Integration (Phase 2)

## Overview

Release Please automates semantic versioning and CHANGELOG generation based on conventional commits. It creates and updates release PRs automatically when commits are pushed to the `develop` branch.

## How It Works

1. **Commit to develop**: Push commits using conventional commit format (feat, fix, etc.)
2. **Release Please analyzes**: GitHub Action runs and analyzes commits since last release
3. **Version calculation**: Automatically calculates next version:
   - `feat:` → minor version bump (1.21.5 → 1.22.0)
   - `fix:` → patch version bump (1.21.5 → 1.21.6)
   - `BREAKING CHANGE:` → major version bump (1.21.5 → 2.0.0)
4. **Release PR created**: Automated PR with version bumps and changelog updates
5. **Weekly review**: Team reviews and approves release PR (Monday 9am UTC)
6. **Merge triggers release**: Merging the PR triggers Phase 3+ workflows (Slack, testing, deployment)

## Configuration Files

### `.release-please-config.json`
Defines how Release Please handles this repository:
- `release-type: simple` - Basic version bumping without complex strategies
- `changelog-path: changelog.txt` - WordPress-style changelog file
- `extra-files` - Additional files to update with version numbers (plugin header, readme)

### `.release-please-manifest.json`
Tracks current version:
```json
{
  ".": "1.21.5"
}
```

Updated automatically by Release Please when releases are created.

## Workflow File

`.github/workflows/release-please.yml` runs on every push to develop and:
1. Analyzes commits since last release
2. Creates/updates release PR with calculated version
3. Outputs release metadata for future phases (Slack notifications, deployments)

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
- Successful tests trigger wordpress.org and EDD deployment
- Automated release to production

## Weekly Release Schedule

**Monday 9am UTC**:
1. Release Please has created/updated PR with week's changes
2. Slack notification sent to team (Phase 3)
3. Team reviews changelog and approves
4. Merge triggers testing and deployment

## Manual Override

For emergency hotfixes:
1. Create fix commit with `fix:` type
2. Add `HOTFIX` label to Release Please PR
3. Approve and merge immediately (bypasses weekly schedule)
4. Deployment triggered instantly

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

1. **Make a test commit**:
   ```bash
   git checkout develop
   git commit --allow-empty -m "feat(test): test Release Please integration"
   git push origin develop
   ```

2. **Watch for PR**: Release Please should create a PR within 1-2 minutes

3. **Review PR**: Check that:
   - Version calculated correctly (should be 1.22.0 for feat)
   - Changelog updated with commit message
   - Version files updated (popup-maker.php, readme.txt, etc.)

4. **Merge PR** (optional): Merging will tag the release (Phase 3+ workflows not yet implemented)

## Next Steps

- **Phase 3**: Slack approval workflow with approval buttons
- **Phase 4**: InstaWP testing integration
- **Phase 5**: Automated deployment to wordpress.org and EDD
