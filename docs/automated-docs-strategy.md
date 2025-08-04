# Automated Documentation Strategy

## Overview

This document outlines strategies for implementing automated documentation generation and maintenance for the Popup Maker repository, leveraging our existing GitHub Actions workflows and project structure.

## Current Documentation Assets

### Existing Documentation
- **CLAUDE.md**: Project-specific development guidelines and architecture
- **README.md**: Basic project overview and setup instructions  
- **CHANGELOG.md**: Structured release history with "Unreleased" section
- **Workflow Outputs**: Rich build summaries, artifact links, and process status

### GitHub Actions Integration Points
- **Build Summaries**: Comprehensive markdown output for each workflow run
- **Changelog Content**: Automated extraction via `bin/update-changelog.js`
- **Artifact Information**: Package names, sizes, checksums, download links
- **Release Data**: Version info, deployment status, GitHub release URLs

---

## Strategy 1: Enhanced README.md with Dynamic Sections

### Implementation
Add auto-updating sections to README.md using GitHub Actions workflow outputs.

**Dynamic Sections to Add**:
```markdown
<!-- AUTO-GENERATED: Latest Release -->
## 🚀 Latest Release

**Version**: [v1.19.0](https://github.com/user/repo/releases/tag/v1.19.0) • **Released**: 2024-01-15  
**Download**: [WordPress.org](https://wordpress.org/plugins/popup-maker/) | [GitHub Release](https://github.com/user/repo/releases/latest)

### Recent Changes
- Enhanced popup targeting with new trigger options
- Improved accessibility and keyboard navigation
- Performance optimizations for mobile devices
- Security enhancements and vulnerability fixes

[📖 View Full Changelog](CHANGELOG.md) • [🚀 All Releases](https://github.com/user/repo/releases)
<!-- END AUTO-GENERATED -->

<!-- AUTO-GENERATED: Build Status -->
## 🔧 Development Status

**Latest Build**: ✅ Passing • **Quality**: ✅ All checks pass  
**Test Branch**: [develop](https://github.com/user/repo/tree/develop) • **Last Updated**: 2 hours ago

[🔨 Request Test Build](https://github.com/user/repo/actions/workflows/build.yml) • [📊 View Build History](https://github.com/user/repo/actions)
<!-- END AUTO-GENERATED -->
```

**Automation Workflow** (`update-readme.yml`):
```yaml
name: Update README
on:
  release:
    types: [published]
  workflow_run:
    workflows: ["Build Test Package", "Release WordPress Plugin"]
    types: [completed]

jobs:
  update-readme:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Update README sections
        run: |
          # Extract latest release info
          # Update build status
          # Commit changes if modified
```

**Benefits**:
- ✅ Simple to implement
- ✅ Version controlled
- ✅ Always visible on repository homepage
- ❌ Limited formatting and search capabilities

---

## Strategy 2: GitHub Wiki Integration

### Implementation
Automatically generate and update GitHub Wiki pages using workflow data.

**Wiki Structure**:
```
Home                     # Project overview with quick links
├── Installation         # Installation guide with latest releases
├── GitHub Actions       # Workflow documentation (auto-generated)
├── Release History      # Comprehensive release archive
├── Development Guide    # Developer setup and contribution guide
└── API Documentation    # Generated from code comments
```

**Automation Approach**:
```yaml
- name: Update Wiki
  uses: actions/github-script@v7
  with:
    script: |
      const { createOrUpdatePage } = require('./scripts/wiki-updater');
      
      // Update GitHub Actions documentation
      await createOrUpdatePage('GitHub-Actions', {
        title: 'GitHub Actions Workflows',
        content: generateWorkflowDocs(),
        lastUpdated: new Date().toISOString()
      });
      
      // Update release history
      await createOrUpdatePage('Release-History', {
        title: 'Release History',
        content: generateReleaseHistory(),
        releases: await getAllReleases()
      });
```

**Wiki Content Sources**:
- **Workflow docs**: Generated from `.github/workflows/*.yml` comments
- **Release history**: Generated from GitHub releases API
- **Installation guides**: Templates with dynamic version/download links
- **API docs**: Extracted from code comments and README files

**Benefits**:
- ✅ Rich formatting with images and tables
- ✅ Searchable across all pages
- ✅ Easy cross-linking between topics  
- ✅ Separate from main repository
- ❌ Requires separate repository clone for updates
- ❌ Wiki pages not included in main repository

---

## Strategy 3: GitHub Pages Documentation Website

### Implementation
Create a documentation website using GitHub Pages with automated content generation.

**Site Structure**:
```
docs/
├── _config.yml          # Jekyll configuration
├── index.md             # Homepage with dynamic content
├── installation/        # Installation guides
├── workflows/           # GitHub Actions documentation
├── api/                 # API reference (generated)
├── releases/            # Release notes archive
└── _includes/           # Reusable templates
    ├── latest-release.html
    ├── build-status.html
    └── download-links.html
```

**Automation Workflow** (`docs-site.yml`):
```yaml
name: Update Documentation Site
on:
  push:
    branches: [master, develop]
    paths: ['docs/**', '.github/workflows/**', 'CHANGELOG.md']
  release:
    types: [published]

jobs:
  update-docs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Generate workflow documentation
        run: |
          node scripts/generate-workflow-docs.js
          
      - name: Update release archive
        run: |
          node scripts/generate-release-archive.js
          
      - name: Extract API documentation
        run: |
          # Generate API docs from code comments
          npm run docs:api
          
      - name: Deploy to GitHub Pages
        uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./docs
```

**Dynamic Content Examples**:

**Latest Release Widget** (`_includes/latest-release.html`):
```html
<div class="latest-release">
  <h3>🚀 Latest Release</h3>
  <p><strong>{{ site.data.latest_release.name }}</strong> • {{ site.data.latest_release.published_at | date: "%B %d, %Y" }}</p>
  <p>{{ site.data.latest_release.description }}</p>
  <a href="{{ site.data.latest_release.html_url }}" class="btn btn-primary">Download</a>
</div>
```

**Build Status Widget** (`_includes/build-status.html`):
```html
<div class="build-status">
  <h3>🔧 Build Status</h3>
  <p>
    <img src="https://img.shields.io/github/actions/workflow/status/user/repo/build.yml?branch=develop&label=develop" alt="Build Status">
    <img src="https://img.shields.io/github/actions/workflow/status/user/repo/build.yml?branch=master&label=master" alt="Build Status">
  </p>
</div>
```

**Benefits**:
- ✅ Full control over design and functionality
- ✅ Rich formatting with custom CSS/JS
- ✅ SEO-friendly with proper URLs
- ✅ Integrated search functionality
- ✅ Mobile-responsive design
- ❌ More complex setup and maintenance
- ❌ Requires Jekyll/static site knowledge

---

## Strategy 4: Structured Documentation Repository

### Implementation
Create a `docs/` directory in the main repository with automated content generation.

**Directory Structure**:
```
docs/
├── README.md                    # Documentation index
├── github-actions.md           # Workflow documentation (generated)
├── installation/
│   ├── wordpress.md            # WordPress installation guide
│   ├── manual.md               # Manual installation
│   └── development.md          # Development setup
├── api/
│   ├── hooks.md                # WordPress hooks (generated)
│   ├── classes.md              # PHP classes (generated)
│   └── javascript.md           # JS API (generated)
├── releases/
│   ├── latest.md               # Latest release info (generated)
│   ├── archive.md              # Release archive (generated)
│   └── changelog.md            # Link to main CHANGELOG.md
└── development/
    ├── contributing.md         # Contribution guidelines
    ├── testing.md              # Testing procedures
    └── build-system.md         # Build system documentation
```

**Automation Integration**:
```yaml
# In existing workflows, add documentation updates
- name: Update Documentation
  run: |
    # Update workflow documentation
    node scripts/generate-workflow-docs.js > docs/github-actions.md
    
    # Update API documentation  
    npm run docs:generate
    
    # Update latest release info
    node scripts/update-release-docs.js
    
    # Commit changes if modified
    if [[ -n $(git status --porcelain docs/) ]]; then
      git add docs/
      git commit -m "docs: auto-update documentation"
      git push
    fi
```

**Benefits**:
- ✅ Version controlled with main repository
- ✅ Easy to maintain and review changes
- ✅ Searchable through repository search
- ✅ Integrated with existing development workflow
- ✅ Can be consumed by GitHub Pages if needed later
- ❌ Documentation changes create "noise" in main repository
- ❌ Limited formatting compared to dedicated documentation sites

---

## Recommended Implementation Strategy

### Phase 1: Quick Wins (Week 1)
**Implement Strategy 1 + 4**: Enhanced README.md + Structured docs directory

1. **Create `docs/` directory** with initial documentation files
2. **Add dynamic README sections** for latest release and build status
3. **Move existing GitHub Actions docs** to `docs/github-actions.md`
4. **Create simple automation** to update README on releases

**Implementation Steps**:
```bash
mkdir docs
mv docs/github-actions.md docs/
echo "# Documentation Index" > docs/README.md
# Add README automation to release workflow
```

### Phase 2: Content Generation (Week 2-3)  
**Expand documentation automation**

1. **API Documentation Generation**: Extract from code comments
2. **Release Archive**: Generate from GitHub releases API  
3. **Installation Guides**: Create comprehensive installation documentation
4. **Workflow Integration**: Update docs automatically on relevant changes

### Phase 3: Advanced Features (Month 2)
**Consider Strategy 2 or 3**: GitHub Wiki or Pages site

Based on Phase 1-2 success, evaluate:
- **GitHub Wiki**: If team prefers separate documentation space
- **GitHub Pages**: If you need custom design and advanced features

### Phase 4: Maintenance & Optimization
**Ongoing improvements**

1. **Content Quality**: Review and improve generated documentation
2. **Search Optimization**: Add tags, categories, and cross-references  
3. **User Feedback**: Gather feedback and iterate on documentation structure
4. **Performance**: Optimize automation scripts and build times

---

## Technical Implementation Details

### GitHub Actions Integration Points

**Release Workflow Integration**:
```yaml
# Add to release.yml after github-release job
docs-update:
  name: Update Documentation
  needs: github-release
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
      with:
        token: ${{ secrets.GITHUB_TOKEN }}
        
    - name: Update release documentation
      run: |
        # Extract release information
        RELEASE_INFO=$(gh api repos/${{ github.repository }}/releases/latest)
        
        # Update README.md sections
        node scripts/update-readme-release.js "$RELEASE_INFO"
        
        # Update docs/releases/latest.md
        node scripts/update-release-docs.js "$RELEASE_INFO"
        
    - name: Commit documentation updates
      run: |
        git config --local user.email "action@github.com"
        git config --local user.name "GitHub Action"
        
        if [[ -n $(git status --porcelain) ]]; then
          git add README.md docs/
          git commit -m "docs: update for release ${{ needs.validate.outputs.release_version }}"
          git push
        fi
```

**Build Workflow Integration**:
```yaml
# Add to build.yml for development status updates
- name: Update build status
  if: github.ref == 'refs/heads/develop'
  run: |
    # Update README build status section
    sed -i 's/Latest Build: .*/Latest Build: ✅ Passing • Last Updated: $(date)/' README.md
```

### Content Generation Scripts

**`scripts/generate-workflow-docs.js`**:
```javascript
const fs = require('fs');
const yaml = require('js-yaml');
const glob = require('glob');

// Read all workflow files
const workflows = glob.sync('.github/workflows/*.yml');

// Extract documentation from workflow comments and structure
const docs = workflows.map(file => {
  const content = fs.readFileSync(file, 'utf8');
  const workflow = yaml.load(content);
  
  return {
    name: workflow.name,
    description: extractDescription(content),
    triggers: workflow.on,
    jobs: Object.keys(workflow.jobs)
  };
});

// Generate markdown documentation
const markdown = generateWorkflowMarkdown(docs);
fs.writeFileSync('docs/github-actions.md', markdown);
```

**`scripts/update-readme-release.js`**:
```javascript
const fs = require('fs');

function updateReadmeRelease(releaseInfo) {
  let readme = fs.readFileSync('README.md', 'utf8');
  
  const releaseSection = `<!-- AUTO-GENERATED: Latest Release -->
## 🚀 Latest Release

**Version**: [${releaseInfo.tag_name}](${releaseInfo.html_url}) • **Released**: ${new Date(releaseInfo.published_at).toLocaleDateString()}
**Download**: [WordPress.org](https://wordpress.org/plugins/popup-maker/) | [GitHub Release](${releaseInfo.html_url})

### Recent Changes
${releaseInfo.body}

[📖 View Full Changelog](CHANGELOG.md) • [🚀 All Releases](https://github.com/${process.env.GITHUB_REPOSITORY}/releases)
<!-- END AUTO-GENERATED -->`;

  // Replace existing section or add new one
  const pattern = /<!-- AUTO-GENERATED: Latest Release -->[\s\S]*?<!-- END AUTO-GENERATED -->/;
  if (pattern.test(readme)) {
    readme = readme.replace(pattern, releaseSection);
  } else {
    readme = readme + '\n\n' + releaseSection;
  }
  
  fs.writeFileSync('README.md', readme);
}
```

### Documentation Templates

**`docs/templates/workflow-template.md`**:
```markdown
# {{workflow.name}}

## Purpose
{{workflow.description}}

## When to Use
{{workflow.usage_scenarios}}

## Configuration
{{workflow.inputs_table}}

## Process Flow
{{workflow.job_flow}}

## Outputs
{{workflow.outputs}}

## Examples
{{workflow.examples}}
```

---

## Maintenance Considerations

### Content Quality
- **Review Generated Content**: Automated content needs human review for accuracy
- **Template Updates**: Keep documentation templates updated with workflow changes
- **Link Validation**: Regularly check that auto-generated links are valid
- **User Feedback**: Gather feedback on documentation usefulness and clarity

### Performance Impact
- **Build Time**: Documentation generation adds ~30-60 seconds to workflows
- **Repository Size**: Generated docs increase repository size
- **API Rate Limits**: GitHub API calls for release data have rate limits
- **Caching**: Cache generated content when possible to reduce rebuild times

### Security Considerations
- **Token Permissions**: Documentation automation needs appropriate GitHub token scopes
- **Sensitive Information**: Ensure no secrets or sensitive data in generated docs
- **User Access**: Consider who can trigger documentation updates
- **Content Validation**: Validate generated content to prevent injection attacks

---

This strategy provides a comprehensive approach to automated documentation that builds on your existing GitHub Actions infrastructure while providing immediate value and room for future expansion.
