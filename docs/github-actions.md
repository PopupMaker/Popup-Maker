# GitHub Actions Workflows Documentation

## Overview

Popup Maker uses GitHub Actions to automate development, testing, and release processes. Our workflows follow industry best practices with proper security, environment protection, and comprehensive error handling.

## Workflow Architecture

### 🔨 Build Test Package (`build.yml`)
**Purpose**: Creates development test builds with optional quality checks and Slack notifications.

**When to Use**:
- Testing changes on specific branches/commits before releases
- Creating development packages for QA testing
- Running quality checks (linting, tests) on demand
- Getting packages for stakeholder review

**Security**: No approval required - available to all team members

**Trigger Options**:
- **Manual**: Go to Actions → Build Test Package → "Run workflow"
- **API**: Repository dispatch with `build-test` type

**Configuration Options**:
| Option | Description | Default | Use Case |
|--------|-------------|---------|----------|
| **Source Branch/Tag** | Which code to build | `develop` | Test feature branches, specific commits |
| **Version Suffix** | Custom version identifier | Branch name + timestamp | Custom build naming |
| **Run Tests** | Execute test suite | Disabled | Quality assurance builds |
| **Run Linting** | Code quality checks | Disabled | Code review preparation |
| **Slack Notifications** | Team alerts | Disabled | Team collaboration |

**Process Flow**:
1. **Validation** → Extract plugin info, generate build version
2. **Quality Checks** → Optional linting and testing (parallel)
3. **Build Package** → Install dependencies, build assets, create ZIP
4. **Notifications** → Slack alerts with download links
5. **Summary** → Comprehensive build report

**Outputs**:
- Plugin ZIP file with proper WordPress structure
- SHA256 checksum for verification
- 30-day artifact retention
- Detailed build summary with download instructions
- Changelog content extracted from recent changes

**Performance Features**:
- ⚡ **NPM Cache**: 3-5 minute savings on dependencies
- ⚡ **Composer Cache**: 2-3 minute savings on PHP packages
- 🔄 **Parallel Processing**: Quality checks run independently

---

### 🚀 Release WordPress Plugin (`release.yml`)
**Purpose**: Creates official production releases following git-flow methodology.

**When to Use**:
- Publishing new versions to users
- Creating WordPress.org releases
- Following semantic versioning releases
- Production deployments

**Security**: ⚠️ **Requires `production` environment approval** before execution

**Trigger Options**:
- **Manual**: Actions → Release WordPress Plugin → "Run workflow" (requires approval)
- **API**: Repository dispatch with `create-release` type

**Configuration Options**:
| Option | Description | Required | Example |
|--------|-------------|----------|---------|
| **Version** | Semantic version | ✅ | `1.19.0`, `2.0.0-beta.1` |
| **Changelog Message** | Additional release notes | ❌ | Custom release highlights |
| **Pre-release** | Skip master merge, tag develop only | ❌ | Beta/RC releases |
| **Deploy WordPress.org** | Automatic SVN deployment | ❌ | Production releases |
| **Dry-run SVN** | Test deployment without committing | ❌ | Deployment testing |
| **Skip Quality Checks** | Emergency releases only | ❌ | Critical hotfixes |

**Release Types**:

**Production Release** (default):
```
develop → release/X.X.X → master → tag → WordPress.org
```

**Pre-release**:
```  
develop → release/X.X.X → tag develop (skip master)
```

**Git Flow Process**:
1. **Validation** → Version format, branch existence, environment approval
2. **Quality Checks** → Optional linting and testing
3. **Git Flow** → Create release branch, update versions, merge strategy
4. **Build Package** → Production build from release tag
5. **GitHub Release** → Create release with artifacts
6. **WordPress.org Deploy** → Optional SVN deployment
7. **Notifications** → Comprehensive team alerts

**File Updates During Release**:
- `popup-maker.php` → Plugin header version
- `package.json` → NPM version
- `readme.txt` → Stable tag
- `CHANGELOG.md` → Release entry with date
- All version references updated automatically

**Outputs**:
- GitHub release with plugin ZIP and checksum  
- WordPress.org deployment (if enabled)
- Updated master and develop branches
- Comprehensive Slack notifications with download links

---

### 🩹 Hotfix Release (`hotfix.yml`)
**Purpose**: Emergency fixes directly from master branch for critical issues.

**When to Use**:
- Critical security vulnerabilities
- Production-breaking bugs  
- Urgent fixes that can't wait for normal release cycle

**Security**: ⚠️ **Requires `production` environment approval**

**Two-Phase Process**:

#### Phase 1: Create Hotfix Branch
**Action**: `create`
```
Actions → Hotfix WordPress Plugin
- Hotfix Action: create
- Version: 1.18.1
- Source Branch: master
```

**Process**:
1. Creates `hotfix/1.18.1` branch from master
2. Updates version numbers in all files
3. Commits preparation changes
4. Pushes branch for development
5. Sends Slack notification with next steps

#### Phase 2: Complete Hotfix
**Action**: `complete` (after applying fixes)
```
Actions → Hotfix WordPress Plugin  
- Hotfix Action: complete
- Hotfix Branch: hotfix/1.18.1
- Deploy WordPress.org: ✅
```

**Process**:
1. Merges hotfix branch to master
2. Tags the release
3. Merges master back to develop (sync)
4. Creates GitHub release
5. Optionally deploys to WordPress.org
6. Cleans up hotfix branch

**Configuration Options**:
| Option | Description | Required | Phase |
|--------|-------------|----------|-------|
| **Hotfix Action** | `create` or `complete` | ✅ | Both |
| **Version** | Hotfix version (e.g., 1.19.1) | ✅ | Create |
| **Hotfix Branch** | Existing branch name | ✅ | Complete |
| **Source Branch** | Usually master | ❌ | Create |
| **Changelog Message** | Hotfix description | ❌ | Both |
| **Deploy WordPress.org** | SVN deployment | ❌ | Complete |
| **Dry-run SVN** | Test deployment | ❌ | Complete |
| **Skip Quality Checks** | Emergency use only | ❌ | Create |

**Workflow Example**:
```bash
# 1. Create hotfix branch
Actions → Hotfix → create → version: 1.18.1

# 2. Apply your fixes to hotfix/1.18.1 branch
git checkout hotfix/1.18.1
# ... make fixes ...
git commit -m "fix: critical security issue"

# 3. Complete the hotfix
Actions → Hotfix → complete → branch: hotfix/1.18.1
```

---

## Quick Usage Guide

### For Developers

**Testing Feature Branches**:
```bash
Actions → Build Test Package
- Branch: feature/my-feature  
- Run Tests: ✅
- Run Linting: ✅
- Slack Notifications: ✅
```

**Code Quality Checks**:
```bash
Actions → Build Test Package
- Branch: develop
- Run Linting: ✅  
- Run Tests: ✅
```

**QA Testing Packages**:
```bash
Actions → Build Test Package
- Branch: develop
- Version Suffix: "qa-testing-round-2"
- Slack Notifications: ✅
```

### For Release Managers

**Standard Production Release**:
```bash
Actions → Release WordPress Plugin
- Version: 1.19.0
- Deploy WordPress.org: ✅
- Pre-release: ❌
```

**Beta/Pre-release**:
```bash  
Actions → Release WordPress Plugin
- Version: 1.19.0-beta.1
- Pre-release: ✅
- Deploy WordPress.org: ❌
```

**Release Candidate**:
```bash
Actions → Release WordPress Plugin  
- Version: 1.19.0-rc.1
- Pre-release: ✅
- Deploy WordPress.org: ❌
```

### For Emergency Fixes

**Critical Security Hotfix**:
```bash
# Step 1: Create hotfix branch
Actions → Hotfix Release
- Action: create
- Version: 1.18.1
- Changelog: "Fix critical XSS vulnerability"

# Step 2: Apply fixes to hotfix/1.18.1

# Step 3: Complete hotfix  
Actions → Hotfix Release
- Action: complete
- Hotfix Branch: hotfix/1.18.1
- Deploy WordPress.org: ✅
```

**Production Bug Hotfix**:
```bash
# Step 1: Create hotfix branch
Actions → Hotfix Release
- Action: create  
- Version: 1.18.2
- Changelog: "Fix checkout process breaking on mobile"

# Step 2: Apply fixes and test

# Step 3: Complete hotfix
Actions → Hotfix Release  
- Action: complete
- Hotfix Branch: hotfix/1.18.2
- Deploy WordPress.org: ✅
```

---

## Performance Optimizations

### Intelligent Caching Strategy
Our workflows use optimized caching to significantly reduce build times:

**High-Value Caches** (Kept):
- **NPM Dependencies**: 3-5 minute savings on subsequent builds
  - Caches: `~/.npm`, `node_modules`, `packages/*/node_modules`
  - Key: `npm-{OS}-{package-lock.json hash}`
- **Composer Dependencies**: 2-3 minute savings on PHP installs  
  - Caches: `~/.composer/cache`, `vendor`
  - Key: `composer-{OS}-{composer.lock hash}`

**Removed Caches** (Caused overhead):
- Build artifacts (prevented fresh builds)
- TypeScript output (added 20+ seconds)
- Webpack cache (inconsistent performance)
- Strauss vendor prefixing (potential for stale files)

### Parallel Processing
- Quality checks run independently from builds
- Multiple dependency installations in parallel
- Optimized artifact handling reduces wait times

### Smart Build Scripts
- Uses `bin/build-release.js` for consistent packaging
- Parallel composer and npm builds save ~40% build time
- Intelligent fallbacks for missing build tools

---

## Security Features

### Environment Protection
- **Production Environment**: Requires manual approval for releases and hotfixes
- **Separate Webhooks**: Different Slack channels for dev vs production notifications  
- **Secret Management**: WordPress.org credentials stored securely
- **Token Scoping**: Minimal permissions for each workflow

### Quality Gates
- **Optional Quality Checks**: Can skip for emergency releases
- **Semantic Version Validation**: Prevents invalid version formats
- **Branch Verification**: Ensures required branches exist
- **Build Artifact Checksums**: SHA256 verification for all packages

### Approval Process
1. Release/Hotfix workflow triggered
2. GitHub requires production environment approval
3. Designated approvers must manually approve
4. Workflow proceeds with full audit trail

---

## Notification System

### Slack Integration

**Build Test Notifications** (`SLACK_WEBHOOK_DEV`):
- 📦 Direct download links with login instructions
- 📝 Changelog highlights from recent changes
- 🔗 GitHub Actions and source branch links
- 📊 Build metrics (size, quality status, build time)
- ⚠️ Test build warnings with 30-day retention notice

**Release Success Notifications** (`SLACK_WEBHOOK_SUCCESS`):
- 🎉 Release announcement with version and type
- 📦 GitHub release and WordPress.org links
- 📝 Full changelog content
- 📊 Package size and deployment status
- 🚀 Direct action buttons for easy access

**Hotfix Notifications**:
- 🛠️ **Create Phase**: Branch ready, next steps, branch link
- 🚨 **Complete Phase**: Urgent release alert, deployment status
- ⚡ Emphasizes urgency for immediate updates

**Failure Notifications** (`SLACK_WEBHOOK_FAILURE`):
- 🔍 Direct links to build logs
- 📋 Failed stage identification  
- ⚠️ Clear troubleshooting guidance
- 👤 Shows who triggered the failed build

### GitHub Integration
- ✅ **Detailed Step Summaries**: Process status with clear indicators
- 📥 **Artifact Instructions**: Download links and WordPress installation steps
- 🔗 **Release Links**: GitHub releases, WordPress.org, changelog links
- 📊 **Process Metrics**: Build times, package sizes, quality results

---

## Build Artifacts & Distribution

### Artifact Structure
```
popup-maker_1.19.0.zip          # Main plugin package
popup-maker_1.19.0.zip.sha256   # Checksum for verification
```

### Package Contents
Based on `package.json` files array or default patterns:
- **PHP Files**: `*.php`, `classes/**/*`, `includes/**/*`
- **Assets**: `assets/**/*`, `dist/**/*` (production builds)
- **Templates**: `templates/**/*`
- **Dependencies**: `vendor/**/*` (production only)
- **Documentation**: `readme.txt`, `LICENSE`
- **Excluded**: `build/`, `node_modules/`, `tests/`, development files

### Distribution Channels
1. **GitHub Artifacts**: 30-day retention for test builds, 90-day for releases
2. **GitHub Releases**: Permanent storage with semantic versioning
3. **WordPress.org SVN**: Official plugin repository (releases only)
4. **Slack Downloads**: Direct links for team collaboration

### Installation Instructions
**WordPress Admin**:
1. Download ZIP file from artifact/release
2. WordPress Admin → Plugins → Add New → Upload Plugin
3. Select downloaded ZIP file
4. Activate plugin

**Manual Installation**:
1. Download and extract ZIP file
2. Upload folder to `/wp-content/plugins/`
3. Activate in WordPress admin

---

## Troubleshooting

### Common Issues

**Build Failures**:
- Check quality gates (linting, tests) if enabled
- Verify branch exists and is accessible
- Review dependency installation logs
- Check for merge conflicts in release branches

**Release Failures**:
- Ensure semantic versioning format (e.g., `1.19.0`)
- Verify production environment approval  
- Check for existing release tags
- Review git-flow branch requirements

**Hotfix Issues**:
- Ensure master branch is clean before creating hotfix
- Don't forget to apply actual fixes between create/complete
- Verify hotfix branch exists before completing
- Check WordPress.org deployment credentials

**Cache Issues**:
- Clear Actions cache if dependencies seem stale
- Check `package-lock.json` and `composer.lock` changes
- NPM cache auto-invalidates on lock file changes

### Getting Help
1. **Build Logs**: Click workflow run → failed job → expand failed step
2. **Slack Notifications**: Contain direct troubleshooting links
3. **Artifact Downloads**: Check GitHub Actions artifacts section
4. **Manual Builds**: Use local `bin/build-release.js` script for testing

### Emergency Procedures
**Skip Quality Checks**: Use `Skip Quality Checks: ✅` for emergency releases
**Hotfix Priority**: Hotfixes bypass normal release cycle for critical issues
**Manual Deployment**: WordPress.org deployment can be done separately if automated fails
**Rollback**: Use previous release artifacts if current release has issues

---

## Build Scripts Reference

### `bin/build-release.js`
**Purpose**: Unified plugin release builder used by all workflows

**Key Features**:
- Parallel composer and npm builds (40% time savings)
- Uses `package.json` files array for distribution
- Automatic version file updates
- Production dependency optimization
- Comprehensive error handling

**Manual Usage**:
```bash
# Basic build
node bin/build-release.js

# Custom build  
node bin/build-release.js --zip-name "popup-maker_1.19.0.zip" --keep-build

# Skip dependencies (faster iteration)
node bin/build-release.js --skip-composer --skip-npm
```

### `bin/update-changelog.js`
**Purpose**: Official changelog management used by release workflows

**Features**:
- Extracts "Unreleased" section from CHANGELOG.md
- Updates both CHANGELOG.md and readme.txt
- Formats content for different contexts
- Preserves changelog history

**Manual Usage**:
```bash
# Update changelog for version
node bin/update-changelog.js "1.19.0"

# Verbose output (shows extracted content)
node bin/update-changelog.js "1.19.0" --verbose
```

---

## Best Practices

### Development Workflow
1. **Feature Development**: Use Build Test Package on feature branches
2. **Quality Assurance**: Enable linting and tests for pre-release builds  
3. **Team Collaboration**: Use Slack notifications for build sharing
4. **Version Naming**: Use descriptive version suffixes for test builds

### Release Management
1. **Semantic Versioning**: Follow semver strictly (MAJOR.MINOR.PATCH)
2. **Changelog Maintenance**: Keep "Unreleased" section updated
3. **Testing**: Use pre-release versions for beta testing
4. **Documentation**: Update changelog message for significant releases

### Emergency Response
1. **Hotfix Process**: Always use two-phase hotfix workflow
2. **Testing**: Test hotfix thoroughly even under time pressure
3. **Communication**: Use Slack notifications to alert team immediately
4. **Follow-up**: Ensure hotfix changes are properly integrated

### Security Considerations
1. **Approval Gates**: Never bypass production environment approval
2. **Credential Management**: Rotate WordPress.org passwords regularly
3. **Access Control**: Limit who can approve production releases
4. **Audit Trail**: All releases have full GitHub Actions logs

---

This documentation covers all GitHub Actions workflows from a practical usage perspective. For technical implementation details, refer to the workflow files in `.github/workflows/`.