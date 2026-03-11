#!/bin/bash

# Health Checker for Joomla - Complete Release Script
# Handles version bumping, building packages, updating XML files, and creating GitHub releases

set -e

# Allow claude_print to work when called from within a Claude Code session
unset CLAUDECODE

# Helper: run claude --print from /tmp to avoid loading project CLAUDE.md/memory
claude_print() {
    (cd /tmp && claude --print --no-session-persistence --disable-slash-commands --model sonnet "$@")
}

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
SOURCE_DIR="$PROJECT_ROOT/healthchecker"
BUILD_DIR="$PROJECT_ROOT/build/dist"
DOWNLOADS_DIR="$PROJECT_ROOT/website/public/downloads"
UPDATE_DIR="$PROJECT_ROOT/website/public/update"
MANIFEST_FILE="$PROJECT_ROOT/healthchecker/component/healthchecker.xml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# --update-notes <tag> mode: regenerate release notes for an existing release
if [ "$1" = "--update-notes" ]; then
    UPDATE_TAG="$2"
    if [ -z "$UPDATE_TAG" ]; then
        echo -e "${RED}Usage: $0 --update-notes <tag>${NC}"
        echo -e "${RED}Example: $0 --update-notes v3.9.0${NC}"
        exit 1
    fi

    # Ensure tag exists
    if ! git rev-parse "$UPDATE_TAG" >/dev/null 2>&1; then
        echo -e "${RED}ERROR: Tag $UPDATE_TAG does not exist${NC}"
        exit 1
    fi

    # Ensure GitHub release exists
    if ! gh release view "$UPDATE_TAG" >/dev/null 2>&1; then
        echo -e "${RED}ERROR: No GitHub release found for $UPDATE_TAG${NC}"
        exit 1
    fi

    UPDATE_VERSION="${UPDATE_TAG#v}"
    echo -e "${GREEN}Health Checker for Joomla - Update Release Notes${NC}"
    echo "===================================================================="
    echo -e "${BLUE}Updating release notes for ${UPDATE_TAG}${NC}"
    echo ""

    # Find the previous tag
    PREV_TAG=$(git describe --tags --abbrev=0 "${UPDATE_TAG}^" 2>/dev/null || echo "")
    if [ -z "$PREV_TAG" ]; then
        echo -e "${RED}ERROR: Could not find a tag before $UPDATE_TAG${NC}"
        exit 1
    fi
    echo -e "${BLUE}Previous release: ${PREV_TAG}${NC}"

    # Gather commits between tags
    GIT_LOG=$(git log --pretty=format:"%h %s" "${PREV_TAG}..${UPDATE_TAG}" | grep -v -E "^[a-f0-9]+ (Rebuild documentation|Update website|Update changelog|Bump version|Rebuild minified assets)")
    echo -e "${BLUE}Commits between ${PREV_TAG} and ${UPDATE_TAG}:${NC}"
    echo "$GIT_LOG"
    echo ""

    # Fetch closed issues and merged PRs between the two tags
    PREV_TAG_DATE=$(git log -1 --format=%aI "$PREV_TAG" 2>/dev/null || echo "")
    UPDATE_TAG_DATE=$(git log -1 --format=%aI "$UPDATE_TAG" 2>/dev/null || echo "")
    CLOSED_ISSUES=""
    if [ -n "$PREV_TAG_DATE" ]; then
        echo -e "${BLUE}Fetching closed issues/PRs since ${PREV_TAG_DATE}...${NC}"
        CLOSED_ISSUES=$(gh issue list --state closed --search "closed:${PREV_TAG_DATE}..${UPDATE_TAG_DATE}" --json number,title,labels,author --template '{{range .}}#{{.number}} {{.title}}{{range .labels}} [{{.name}}]{{end}} (by @{{.author.login}})
{{end}}' 2>/dev/null || echo "")
        MERGED_PRS=$(gh pr list --state merged --search "merged:${PREV_TAG_DATE}..${UPDATE_TAG_DATE}" --json number,title,labels,author --template '{{range .}}PR #{{.number}} {{.title}}{{range .labels}} [{{.name}}]{{end}} (by @{{.author.login}})
{{end}}' 2>/dev/null || echo "")
        if [ -n "$MERGED_PRS" ]; then
            CLOSED_ISSUES="${CLOSED_ISSUES}
${MERGED_PRS}"
        fi
    fi

    if [ -n "$CLOSED_ISSUES" ]; then
        echo -e "${BLUE}Closed issues/PRs:${NC}"
        echo "$CLOSED_ISSUES"
        echo ""
    fi

    # Generate release notes with Claude
    echo -e "${YELLOW}Generating release notes with Claude...${NC}"

    ISSUES_CONTEXT=""
    if [ -n "$CLOSED_ISSUES" ]; then
        ISSUES_CONTEXT="

Closed issues and merged PRs since ${PREV_TAG}:
${CLOSED_ISSUES}
"
    fi

    CLAUDE_PROMPT="Generate release notes for Health Checker for Joomla version ${UPDATE_VERSION}.

Git commits since ${PREV_TAG}:
${GIT_LOG}
${ISSUES_CONTEXT}
Create concise, user-focused release notes. Only include:
- New features (with brief description)
- Bug fixes (important ones only)
- Breaking changes or deprecations
- Security/Performance improvements

CRITICAL FORMATTING RULES:
1. Output ONLY the release notes content. NO preamble.
2. Start immediately with bullet points.
3. DO NOT include commit hashes.
4. DO NOT use emoji characters.
5. Use prefixes: [Feature], [Fix], [Security], [Performance], [Internal]
6. If a bullet point relates to a closed GitHub issue or merged PR, append the reference as a markdown link at the end of the line.
   Format: ([#N](https://github.com/mySites-guru/HealthCheckerForJoomla/issues/N))
   Match commits to issues/PRs by comparing their descriptions.

7. If an issue or PR was reported/opened by an EXTERNAL contributor (not @PhilETaylor), append (Thanks @username) after the issue link.
   Do NOT credit @PhilETaylor as he is the project maintainer.

Example:
- [Fix] Fixed missing translation key for brute force protection check ([#2](https://github.com/mySites-guru/HealthCheckerForJoomla/issues/2)) (Thanks @janedoe)
- [Feature] Added backup status monitoring
- [Internal] Updated build tooling"

    PROMPT_FILE=$(mktemp)
    echo "$CLAUDE_PROMPT" > "$PROMPT_FILE"
    echo -e "${BLUE}--- Prompt ($(wc -c < "$PROMPT_FILE" | tr -d ' ') bytes) ---${NC}"
    echo -e "${BLUE}--- First 5 lines of prompt ---${NC}"
    head -5 "$PROMPT_FILE"
    echo -e "${BLUE}--- end prompt preview ---${NC}"

    CLAUDE_STDERR=$(mktemp)
    CLAUDE_EXIT=0
    CLAUDE_RAW=$(cat "$PROMPT_FILE" | claude_print 2>"$CLAUDE_STDERR") || CLAUDE_EXIT=$?

    echo -e "${BLUE}Claude exit code: ${CLAUDE_EXIT}${NC}"

    if [ -s "$CLAUDE_STDERR" ]; then
        echo -e "${YELLOW}Claude stderr:${NC}"
        cat "$CLAUDE_STDERR"
    fi
    rm -f "$CLAUDE_STDERR"

    # If pipe failed, try positional argument
    if [ -z "$CLAUDE_RAW" ]; then
        echo -e "${YELLOW}Pipe returned empty, trying positional argument...${NC}"
        CLAUDE_RAW=$(claude_print "$(cat "$PROMPT_FILE")" 2>/dev/null) || true
    fi
    rm -f "$PROMPT_FILE"

    echo -e "${BLUE}--- Claude raw output ---${NC}"
    echo "$CLAUDE_RAW"
    echo -e "${BLUE}--- end Claude raw output ---${NC}"

    if [ -z "$CLAUDE_RAW" ]; then
        echo -e "${RED}Claude returned empty output. Cannot update release notes.${NC}"
        exit 1
    fi

    RELEASE_NOTES=$(echo "$CLAUDE_RAW" | awk '
        /^[[:space:]]*[*-]/ { sub(/^[[:space:]]+/, ""); print; in_list = 1; next }
        /^$/ && in_list { print; next }
        /^[[:space:]]+/ && in_list { print; next }
    ')

    if [ -z "$RELEASE_NOTES" ]; then
        echo -e "${YELLOW}No bullet points found in Claude output. Using raw output.${NC}"
        RELEASE_NOTES="$CLAUDE_RAW"
    fi

    echo -e "${BLUE}--- Final release notes ---${NC}"
    echo "$RELEASE_NOTES"
    echo -e "${BLUE}--- end release notes ---${NC}"
    echo ""

    # Build the full release body
    REPO_URL=$(git config --get remote.origin.url | sed 's/git@github.com:/https:\/\/github.com\//' | sed 's/\.git$//')
    RELEASE_BODY="${RELEASE_NOTES}

---

**Full Changelog**: ${REPO_URL}/compare/${PREV_TAG}...${UPDATE_TAG}

## Installation

Download the **Complete Package (Recommended)** which installs everything you need.

**What gets installed:**
- ✓ Component: com_healthchecker
- ✓ Module: mod_healthchecker (dashboard widget)
- ✓ Plugin: plg_healthchecker_core (All core checks)
- ✓ Plugin: plg_healthchecker_example (SDK reference)
- ✓ Plugin: plg_healthchecker_mysitesguru (API integration)
- ✓ Plugin: plg_healthchecker_akeebabackup (auto-enabled if Akeeba Backup installed)
- ✓ Plugin: plg_healthchecker_akeebaadmintools (auto-enabled if Admin Tools installed)

## Requirements

- Joomla 5.0 or later
- PHP 8.1 or later"

    # Update the GitHub release
    echo -e "${YELLOW}Updating GitHub release ${UPDATE_TAG}...${NC}"
    gh release edit "$UPDATE_TAG" --notes "$RELEASE_BODY"
    echo -e "${GREEN}✓ GitHub release updated${NC}"

    # Regenerate changelog from GitHub releases
    echo ""
    echo -e "${YELLOW}Regenerating changelog...${NC}"
    bash "$SCRIPT_DIR/changelog.sh"
    git add docs/USER/changelog.md
    if ! git diff --cached --quiet; then
        git commit -m "Update changelog for ${UPDATE_TAG}"
        git push origin main
        echo -e "${GREEN}✓ Changelog updated and pushed${NC}"
    else
        echo -e "${BLUE}No changelog changes to commit${NC}"
    fi

    # Build and deploy website
    echo ""
    bash "$SCRIPT_DIR/website-deploy.sh"

    echo ""
    echo "===================================================================="
    echo -e "${GREEN}Release notes updated for ${UPDATE_TAG}!${NC}"
    echo ""
    echo "Release URL: https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/${UPDATE_TAG}"
    echo "Website:     https://www.joomlahealthchecker.com"
    echo ""
    exit 0
fi

echo -e "${GREEN}Health Checker for Joomla - Complete Release Script${NC}"
echo "===================================================================="
echo ""

# Validate source directory
if [ ! -d "$SOURCE_DIR" ]; then
    echo -e "${RED}ERROR: Source directory not found: $SOURCE_DIR${NC}"
    exit 1
fi

# Get current version from manifest
CURRENT_VERSION=$(sed -n 's/.*<version>\(.*\)<\/version>.*/\1/p' "$MANIFEST_FILE" | head -n 1)
echo -e "${BLUE}Current version: ${CURRENT_VERSION}${NC}"

# Parse current version
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

# Rebuild documentation BEFORE analyzing commits
echo ""
echo -e "${YELLOW}Rebuilding documentation...${NC}"
cd "$PROJECT_ROOT/docs/USER"

if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}Installing documentation dependencies...${NC}"
    npm install
fi

npm run docs:build

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Documentation built successfully${NC}"

    cd "$PROJECT_ROOT"

    # Copy llms and sitemap files from docs to public root
    if [ -f "website/public/docs/sitemap.xml" ]; then
        cp website/public/docs/sitemap.xml website/public/sitemap.xml
    fi
    if [ -f "website/public/docs/llms.txt" ]; then
        cp website/public/docs/llms.txt website/public/llms.txt
    fi
    if [ -f "website/public/docs/llms-full.txt" ]; then
        cp website/public/docs/llms-full.txt website/public/llms-full.txt
    fi

    if git diff --quiet website/public/docs/ website/public/sitemap.xml website/public/llms.txt website/public/llms-full.txt && \
       git diff --cached --quiet website/public/docs/ website/public/sitemap.xml website/public/llms.txt website/public/llms-full.txt; then
        echo -e "${BLUE}No documentation changes to commit${NC}"
    else
        echo -e "${YELLOW}Committing documentation changes...${NC}"
        git add docs/USER/changelog.md
        git add website/public/docs/
        git add website/public/search-widget.js
        git add website/public/sitemap.xml
        git add website/public/llms.txt
        git add website/public/llms-full.txt
        git commit -m "Rebuild documentation before v${MAJOR}.${MINOR}.$((PATCH + 1)) release"
        git push origin main
        echo -e "${GREEN}✓ Documentation changes committed and pushed${NC}"
    fi
else
    echo -e "${RED}✗ Documentation build failed${NC}"
    echo -e "${YELLOW}Continuing with release anyway...${NC}"
fi

cd "$PROJECT_ROOT"

# Analyze changes to determine version bump
echo ""
echo -e "${YELLOW}Analyzing changes to determine version bump...${NC}"
LAST_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "")

if [ -z "$LAST_TAG" ]; then
    echo -e "${BLUE}No previous tags found. This appears to be the first release.${NC}"
    GIT_LOG=""
    CLOSED_ISSUES=""
else
    echo -e "${BLUE}Last release: ${LAST_TAG}${NC}"
    GIT_LOG=$(git log --pretty=format:"%h %s" "${LAST_TAG}..HEAD" | grep -v -E "^[a-f0-9]+ (Rebuild documentation|Update website|Update changelog|Bump version|Rebuild minified assets)")

    # Fetch closed issues and merged PRs since last tag
    LAST_TAG_DATE=$(git log -1 --format=%aI "$LAST_TAG" 2>/dev/null || echo "")
    if [ -n "$LAST_TAG_DATE" ]; then
        echo -e "${BLUE}Fetching closed issues/PRs since ${LAST_TAG_DATE}...${NC}"
        CLOSED_ISSUES=$(gh issue list --state closed --search "closed:>=${LAST_TAG_DATE}" --json number,title,labels,author --template '{{range .}}#{{.number}} {{.title}}{{range .labels}} [{{.name}}]{{end}} (by @{{.author.login}})
{{end}}' 2>/dev/null || echo "")
        MERGED_PRS=$(gh pr list --state merged --search "merged:>=${LAST_TAG_DATE}" --json number,title,labels,author --template '{{range .}}PR #{{.number}} {{.title}}{{range .labels}} [{{.name}}]{{end}} (by @{{.author.login}})
{{end}}' 2>/dev/null || echo "")
        if [ -n "$MERGED_PRS" ]; then
            CLOSED_ISSUES="${CLOSED_ISSUES}
${MERGED_PRS}"
        fi
    else
        CLOSED_ISSUES=""
    fi
fi

# Use Claude to determine appropriate version bump
if [ -z "$GIT_LOG" ]; then
    echo -e "${YELLOW}No commits found since last tag.${NC}"
    echo -e "${YELLOW}Defaulting to patch version bump.${NC}"
    VERSION_TYPE="patch"
else
    echo -e "${YELLOW}Analyzing commits with Claude...${NC}"
    PROMPT_FILE=$(mktemp)
    cat > "$PROMPT_FILE" << EOF
Analyze these git commits and determine the appropriate semantic version bump.

Current version: ${CURRENT_VERSION}

Commits since ${LAST_TAG}:
${GIT_LOG}

Semantic versioning rules:
- MAJOR: Breaking changes, incompatible API changes, major feature overhauls
- MINOR: New features, new functionality, backwards-compatible changes
- PATCH: Bug fixes, internal improvements, documentation, build tooling changes

CRITICAL: Output ONLY ONE WORD: either "major", "minor", or "patch"
No explanation, no formatting, just the word.
EOF
    VERSION_ANALYSIS=$(cat "$PROMPT_FILE" | claude_print 2>/dev/null) || true
    rm -f "$PROMPT_FILE"

    VERSION_TYPE=$(echo "$VERSION_ANALYSIS" | tr -d '[:space:]' | tr '[:upper:]' '[:lower:]')

    if [[ ! "$VERSION_TYPE" =~ ^(major|minor|patch)$ ]]; then
        echo -e "${YELLOW}Claude returned unexpected response: '$VERSION_TYPE'${NC}"
        echo -e "${YELLOW}Defaulting to patch version bump.${NC}"
        VERSION_TYPE="patch"
    fi
fi

# Calculate new version
case $VERSION_TYPE in
    major)
        NEW_VERSION="$((MAJOR + 1)).0.0"
        ;;
    minor)
        NEW_VERSION="${MAJOR}.$((MINOR + 1)).0"
        ;;
    patch)
        NEW_VERSION="${MAJOR}.${MINOR}.$((PATCH + 1))"
        ;;
esac

echo ""
echo -e "${GREEN}Version bump type: ${VERSION_TYPE}${NC}"
echo -e "${GREEN}New version: ${NEW_VERSION}${NC}"
echo ""

# Generate release notes using Claude
echo -e "${YELLOW}Generating release notes with Claude...${NC}"

if [ -z "$GIT_LOG" ] && [ -z "$LAST_TAG" ]; then
    CLAUDE_PROMPT="Generate release notes for Health Checker for Joomla version ${NEW_VERSION}.

This is the initial release. Create brief, welcoming release notes mentioning it's a comprehensive health monitoring extension with over 130 checks across 8+ categories.

CRITICAL FORMATTING RULES:
1. Output ONLY the release notes content. NO preamble, NO explanation.
2. Start immediately with bullet points.
3. DO NOT use emoji characters.
4. Use text prefixes: [Feature], [Info], etc.

Example:
- [Release] Initial release of Health Checker for Joomla
- [Feature] Over 130 health checks across 8+ categories"
elif [ -z "$GIT_LOG" ] && [ -n "$LAST_TAG" ] && [ -z "$CLOSED_ISSUES" ]; then
    CLAUDE_PROMPT="Generate release notes for Health Checker for Joomla version ${NEW_VERSION}.

There are no commits since ${LAST_TAG}. This is a maintenance release.

Output ONLY one bullet point:
- [Release] Maintenance release with no functional changes"
else
    ISSUES_CONTEXT=""
    if [ -n "$CLOSED_ISSUES" ]; then
        ISSUES_CONTEXT="

Closed issues and merged PRs since ${LAST_TAG}:
${CLOSED_ISSUES}
"
    fi

    CLAUDE_PROMPT="Generate release notes for Health Checker for Joomla version ${NEW_VERSION}.

Git commits since ${LAST_TAG}:
${GIT_LOG}
${ISSUES_CONTEXT}
Create concise, user-focused release notes. Only include:
- New features (with brief description)
- Bug fixes (important ones only)
- Breaking changes or deprecations
- Security/Performance improvements

CRITICAL FORMATTING RULES:
1. Output ONLY the release notes content. NO preamble.
2. Start immediately with bullet points.
3. DO NOT include commit hashes.
4. DO NOT use emoji characters.
5. Use prefixes: [Feature], [Fix], [Security], [Performance], [Internal]
6. If a bullet point relates to a closed GitHub issue or merged PR, append the reference as a markdown link at the end of the line.
   Format: ([#N](https://github.com/mySites-guru/HealthCheckerForJoomla/issues/N))
   Match commits to issues/PRs by comparing their descriptions.

7. If an issue or PR was reported/opened by an EXTERNAL contributor (not @PhilETaylor), append (Thanks @username) after the issue link.
   Do NOT credit @PhilETaylor as he is the project maintainer.

Example:
- [Fix] Fixed missing translation key for brute force protection check ([#2](https://github.com/mySites-guru/HealthCheckerForJoomla/issues/2)) (Thanks @janedoe)
- [Feature] Added backup status monitoring
- [Internal] Updated build tooling"
fi

PROMPT_FILE=$(mktemp)
echo "$CLAUDE_PROMPT" > "$PROMPT_FILE"
CLAUDE_STDERR=$(mktemp)
CLAUDE_RAW=$(cat "$PROMPT_FILE" | claude_print 2>"$CLAUDE_STDERR") || true
rm -f "$PROMPT_FILE"

if [ -s "$CLAUDE_STDERR" ]; then
    echo -e "${YELLOW}Claude stderr:${NC}"
    cat "$CLAUDE_STDERR"
fi
rm -f "$CLAUDE_STDERR"

echo -e "${BLUE}--- Claude raw output ---${NC}"
echo "$CLAUDE_RAW"
echo -e "${BLUE}--- end Claude raw output ---${NC}"

if [ -z "$CLAUDE_RAW" ]; then
    echo -e "${YELLOW}Claude returned empty output. Using default message.${NC}"
    RELEASE_NOTES="Release version ${NEW_VERSION}"
else
    RELEASE_NOTES=$(echo "$CLAUDE_RAW" | awk '
        /^[[:space:]]*[*-]/ { sub(/^[[:space:]]+/, ""); print; in_list = 1; next }
        /^$/ && in_list { print; next }
        /^[[:space:]]+/ && in_list { print; next }
    ')

    echo -e "${BLUE}--- After awk filter ---${NC}"
    echo "$RELEASE_NOTES"
    echo -e "${BLUE}--- end awk filter ---${NC}"

    if [ -z "$RELEASE_NOTES" ]; then
        echo -e "${YELLOW}No bullet points survived the awk filter. Using raw output.${NC}"
        RELEASE_NOTES="$CLAUDE_RAW"
    else
        echo -e "${GREEN}Release notes generated!${NC}"
    fi
fi

# Update version and creation date in all manifest files
echo ""
echo -e "${YELLOW}Updating version numbers and creation dates...${NC}"

CREATION_DATE=$(date +%Y-%m-%d)

sed -i.bak "s/<version>.*<\/version>/<version>${NEW_VERSION}<\/version>/;s/<creationDate>.*<\/creationDate>/<creationDate>${CREATION_DATE}<\/creationDate>/" "$SOURCE_DIR/component/healthchecker.xml"
rm -f "$SOURCE_DIR/component/healthchecker.xml.bak"
echo "  ✓ Component manifest"

sed -i.bak "s/<version>.*<\/version>/<version>${NEW_VERSION}<\/version>/;s/<creationDate>.*<\/creationDate>/<creationDate>${CREATION_DATE}<\/creationDate>/" "$SOURCE_DIR/module/mod_healthchecker.xml"
rm -f "$SOURCE_DIR/module/mod_healthchecker.xml.bak"
echo "  ✓ Module manifest"

for plugin in core example akeebabackup akeebaadmintools mysitesguru; do
    sed -i.bak "s/<version>.*<\/version>/<version>${NEW_VERSION}<\/version>/;s/<creationDate>.*<\/creationDate>/<creationDate>${CREATION_DATE}<\/creationDate>/" "$SOURCE_DIR/plugins/$plugin/${plugin}.xml"
    rm -f "$SOURCE_DIR/plugins/$plugin/${plugin}.xml.bak"
    echo "  ✓ $plugin plugin manifest"
done

sed -i.bak "s/\"version\": \".*\"/\"version\": \"${NEW_VERSION}\"/" "$SOURCE_DIR/component/media/joomla.asset.json"
rm -f "$SOURCE_DIR/component/media/joomla.asset.json.bak"
echo "  ✓ Component joomla.asset.json"

sed -i.bak "s/\"version\": \".*\"/\"version\": \"${NEW_VERSION}\"/" "$SOURCE_DIR/module/joomla.asset.json"
rm -f "$SOURCE_DIR/module/joomla.asset.json.bak"
echo "  ✓ Module joomla.asset.json"

# Commit version changes
echo ""
echo -e "${YELLOW}Committing version changes...${NC}"
git add healthchecker/component/healthchecker.xml
git add healthchecker/module/mod_healthchecker.xml
git add healthchecker/plugins/*/*.xml
git add healthchecker/component/media/joomla.asset.json
git add healthchecker/module/joomla.asset.json

if ! git diff --cached --quiet; then
    git commit -m "Bump version to ${NEW_VERSION}"
    echo -e "${GREEN}✓ Version changes committed${NC}"
fi

# BUILD PACKAGES
echo ""
echo -e "${YELLOW}Building release packages...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/tmp"

# Minify assets
echo "Minifying assets..."
npx terser "$SOURCE_DIR/component/media/js/admin-report.js" -c -m -o "$SOURCE_DIR/component/media/js/admin-report.min.js" --source-map "url=admin-report.min.js.map"
npx csso-cli "$SOURCE_DIR/component/media/css/admin-report.css" -o "$SOURCE_DIR/component/media/css/admin-report.min.css" --source-map "$SOURCE_DIR/component/media/css/admin-report.min.css.map"
npx terser "$SOURCE_DIR/module/media/js/module-stats.js" -c -m -o "$SOURCE_DIR/module/media/js/module-stats.min.js" --source-map "url=module-stats.min.js.map"
npx csso-cli "$SOURCE_DIR/module/media/css/module-card.css" -o "$SOURCE_DIR/module/media/css/module-card.min.css" --source-map "$SOURCE_DIR/module/media/css/module-card.min.css.map"
echo -e "${GREEN}✓ Assets minified${NC}"

# Commit minified assets
echo ""
echo -e "${YELLOW}Committing minified assets...${NC}"
git add healthchecker/component/media/js/admin-report.min.js healthchecker/component/media/js/admin-report.min.js.map
git add healthchecker/component/media/css/admin-report.min.css healthchecker/component/media/css/admin-report.min.css.map
git add healthchecker/module/media/js/module-stats.min.js healthchecker/module/media/js/module-stats.min.js.map
git add healthchecker/module/media/css/module-card.min.css healthchecker/module/media/css/module-card.min.css.map

if ! git diff --cached --quiet; then
    git commit -m "Rebuild minified assets for v${NEW_VERSION}"
    echo -e "${GREEN}✓ Minified assets committed${NC}"
else
    echo -e "${BLUE}No minified asset changes to commit${NC}"
fi

# Component
echo "Building component..."
COMP_DIR="$BUILD_DIR/tmp/com_healthchecker"
mkdir -p "$COMP_DIR"
for item in "$SOURCE_DIR/component/"*; do
    if [ "$(basename "$item")" != "media" ] && [ "$(basename "$item")" != "healthchecker.xml" ]; then
        cp -r "$item" "$COMP_DIR/"
    fi
done
cp -r "$SOURCE_DIR/component/media" "$COMP_DIR/"
cp "$SOURCE_DIR/component/healthchecker.xml" "$COMP_DIR/"
cd "$COMP_DIR"
zip -r "$BUILD_DIR/com_healthchecker-${NEW_VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
echo -e "${GREEN}✓ com_healthchecker-${NEW_VERSION}.zip${NC}"

# Module
echo "Building module..."
MOD_DIR="$BUILD_DIR/tmp/mod_healthchecker"
mkdir -p "$MOD_DIR"
cp -r "$SOURCE_DIR/module/"* "$MOD_DIR/"
cd "$MOD_DIR"
zip -r "$BUILD_DIR/mod_healthchecker-${NEW_VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
echo -e "${GREEN}✓ mod_healthchecker-${NEW_VERSION}.zip${NC}"

# Plugins
for plugin in core example akeebabackup akeebaadmintools mysitesguru; do
    echo "Building $plugin plugin..."
    PLG_DIR="$BUILD_DIR/tmp/plg_healthchecker_${plugin}"
    mkdir -p "$PLG_DIR"
    cp -r "$SOURCE_DIR/plugins/$plugin/"* "$PLG_DIR/"
    cd "$PLG_DIR"
    zip -r "$BUILD_DIR/plg_healthchecker_${plugin}-${NEW_VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
    echo -e "${GREEN}✓ plg_healthchecker_${plugin}-${NEW_VERSION}.zip${NC}"
done

# Package
echo "Building unified package..."
PKG_DIR="$BUILD_DIR/tmp/pkg_healthchecker"
mkdir -p "$PKG_DIR/packages"
cp "$BUILD_DIR"/*.zip "$PKG_DIR/packages/"

cat > "$PKG_DIR/pkg_healthchecker.xml" << EOF
<?xml version="1.0" encoding="utf-8"?>
<extension type="package" method="upgrade">
    <name>Health Checker for Joomla</name>
    <packagename>healthchecker</packagename>
    <author>mySites.guru / Phil E. Taylor</author>
    <creationDate>$(date +%Y-%m)</creationDate>
    <copyright>(C) $(date +%Y) mySites.guru / Phil E. Taylor</copyright>
    <license>GNU General Public License version 2 or later; see LICENSE.txt</license>
    <authorEmail>phil@phil-taylor.com</authorEmail>
    <authorUrl>https://phil-taylor.com</authorUrl>
    <version>${NEW_VERSION}</version>
    <description>Comprehensive health check extension for Joomla with over 130 checks across 8+ categories.</description>
    <packager>mySites.guru</packager>
    <packagerurl>https://mysites.guru</packagerurl>
    <blockChildUninstall>true</blockChildUninstall>
    <files folder="packages">
        <file type="component" id="com_healthchecker">com_healthchecker-${NEW_VERSION}.zip</file>
        <file type="module" id="mod_healthchecker" client="administrator">mod_healthchecker-${NEW_VERSION}.zip</file>
        <file type="plugin" id="core" group="healthchecker">plg_healthchecker_core-${NEW_VERSION}.zip</file>
        <file type="plugin" id="example" group="healthchecker">plg_healthchecker_example-${NEW_VERSION}.zip</file>
        <file type="plugin" id="akeebabackup" group="healthchecker">plg_healthchecker_akeebabackup-${NEW_VERSION}.zip</file>
        <file type="plugin" id="akeebaadmintools" group="healthchecker">plg_healthchecker_akeebaadmintools-${NEW_VERSION}.zip</file>
        <file type="plugin" id="mysitesguru" group="healthchecker">plg_healthchecker_mysitesguru-${NEW_VERSION}.zip</file>
    </files>
    <scriptfile>script.php</scriptfile>
    <updateservers>
        <server type="extension" priority="1" name="Health Checker Package">https://www.joomlahealthchecker.com/update/pkg_healthchecker.xml</server>
    </updateservers>
</extension>
EOF

cat > "$PKG_DIR/script.php" << 'SCRIPT'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseInterface;

class Pkg_HealthcheckerInstallerScript
{
    /**
     * Plugins included in this package. Joomla's package uninstaller sometimes
     * fails to cascade-delete plugins in custom groups, so we handle it manually.
     */
    private const PLUGINS = ['core', 'example', 'akeebabackup', 'akeebaadmintools', 'mysitesguru'];

    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if (version_compare(JVERSION, '5.0.0', '<')) {
            Factory::getApplication()->enqueueMessage('Health Checker requires Joomla 5.0 or later.', 'error');
            return false;
        }
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            Factory::getApplication()->enqueueMessage('Health Checker requires PHP 8.1 or later.', 'error');
            return false;
        }
        return true;
    }

    public function uninstall(InstallerAdapter $parent): void
    {
        $installer = \Joomla\CMS\Installer\Installer::getInstance();
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Remove all plugins first
        foreach (self::PLUGINS as $element) {
            $id = $this->getExtensionId($db, 'plugin', $element, 'healthchecker');

            if ($id) {
                $installer->uninstall('plugin', $id);
            }
        }

        // Remove the module
        $moduleId = $this->getExtensionId($db, 'module', 'mod_healthchecker');

        if ($moduleId) {
            $installer->uninstall('module', $moduleId);
        }

        // Remove the component
        $componentId = $this->getExtensionId($db, 'component', 'com_healthchecker');

        if ($componentId) {
            $installer->uninstall('component', $componentId);
        }

        // Remove the plugin group directory if empty
        $groupDir = JPATH_PLUGINS . '/healthchecker';
        if (is_dir($groupDir) && count(glob($groupDir . '/*')) === 0) {
            @rmdir($groupDir);
        }
    }

    private function getExtensionId(DatabaseInterface $db, string $type, string $element, string $folder = ''): int
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element));

        if ($folder !== '') {
            $query->where($db->quoteName('folder') . ' = ' . $db->quote($folder));
        }

        return (int) $db->setQuery($query)->loadResult();
    }

    public function postflight(string $type, InstallerAdapter $parent): void
    {
        $this->removeObsoleteFiles();

        if ($type === 'install') {
            $this->enablePlugin('healthchecker', 'core');
            $this->enablePlugin('healthchecker', 'example');
            $this->enablePlugin('healthchecker', 'mysitesguru');

            if ($this->isExtensionInstalled('component', 'com_akeebabackup')) {
                $this->enablePlugin('healthchecker', 'akeebabackup');
            }
            if ($this->isExtensionInstalled('component', 'com_admintools')) {
                $this->enablePlugin('healthchecker', 'akeebaadmintools');
            }

            $this->publishModule('mod_healthchecker', 'cpanel');

            Factory::getApplication()->enqueueMessage(
                'Health Checker installed successfully! Access it from Components > Health Checker.',
                'success'
            );
        }
    }

    private function enablePlugin(string $group, string $element): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($group))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element));
        $db->setQuery($query)->execute();
    }

    private function isExtensionInstalled(string $type, string $element): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element))
            ->where($db->quoteName('enabled') . ' = 1');
        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function removeObsoleteFiles(): void
    {
        $files = [
            // Removed in 3.0.38: BackupAgeCheck replaced by akeeba_backup.last_backup
            JPATH_PLUGINS . '/healthchecker/core/src/Checks/Database/BackupAgeCheck.php',
            // Removed in 3.0.36: Phantom check for non-existent plg_user_userlog
            JPATH_PLUGINS . '/healthchecker/core/src/Checks/Security/UserActionsLogCheck.php',
            // Removed in 3.0.41: Redundant and not performing well (GitHub #11)
            JPATH_PLUGINS . '/healthchecker/core/src/Checks/Extensions/LegacyExtensionsCheck.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    private function publishModule(string $module, string $position = 'cpanel'): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . ' = ' . $db->quote($module))
            ->where($db->quoteName('client_id') . ' = 1');
        $moduleId = $db->setQuery($query)->loadResult();

        if ($moduleId) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__modules'))
                ->set($db->quoteName('published') . ' = 1')
                ->set($db->quoteName('position') . ' = ' . $db->quote($position))
                ->set($db->quoteName('showtitle') . ' = 1')
                ->set($db->quoteName('ordering') . ' = 1')
                ->where($db->quoteName('id') . ' = ' . (int) $moduleId);
            $db->setQuery($query)->execute();

            try {
                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__modules_menu'))
                    ->columns([$db->quoteName('moduleid'), $db->quoteName('menuid')])
                    ->values((int) $moduleId . ', 0');
                $db->setQuery($query)->execute();
            } catch (\Exception $e) {
                // Already assigned
            }
        }
    }
}
SCRIPT

cd "$PKG_DIR"
zip -r "$BUILD_DIR/pkg_healthchecker-${NEW_VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
echo -e "${GREEN}✓ pkg_healthchecker-${NEW_VERSION}.zip${NC}"

# Cleanup
rm -rf "$BUILD_DIR/tmp"
echo -e "${GREEN}✓ Packages built successfully${NC}"

# UPDATE XML FILES AND COPY TO WEBSITE
echo ""
echo -e "${YELLOW}Updating XML files and copying to website...${NC}"

# Clear and copy ZIPs to website downloads
echo "Clearing old downloads..."
rm -rf "$DOWNLOADS_DIR"
mkdir -p "$DOWNLOADS_DIR"
cp "$BUILD_DIR"/*.zip "$DOWNLOADS_DIR/"
echo -e "${GREEN}✓ Packages copied to website${NC}"

# Generate checksums
echo "Generating SHA512 checksums..."
CHECKSUMS_FILE="/tmp/checksums-${NEW_VERSION}.txt"
rm -f "$CHECKSUMS_FILE"
cd "$DOWNLOADS_DIR"
for file in *-${NEW_VERSION}.zip; do
    if [ -f "$file" ]; then
        sha512=$(shasum -a 512 "$file" | cut -d' ' -f1)
        base_name=$(echo "$file" | sed "s/-${NEW_VERSION}\.zip$//")
        echo "${base_name}=${sha512}" >> "$CHECKSUMS_FILE"
    fi
done

get_checksum() {
    grep "^${1}=" "$CHECKSUMS_FILE" | cut -d'=' -f2
}

# Update individual XML files
cd "$PROJECT_ROOT"
update_xml() {
    local xml_file=$1
    local extension=$2
    local checksum=$(get_checksum "$extension")

    if [ -f "$xml_file" ] && [ -n "$checksum" ]; then
        sed -i '' "s|<version>[^<]*</version>|<version>${NEW_VERSION}</version>|" "$xml_file"
        sed -i '' "s|/${extension}-[0-9.]*\.zip|/${extension}-${NEW_VERSION}.zip|g" "$xml_file"
        if grep -q "<sha512>" "$xml_file"; then
            sed -i '' "s|<sha512>.*</sha512>|<sha512>${checksum}</sha512>|" "$xml_file"
        else
            sed -i '' "s|</downloads>|</downloads>\\
        <sha512>${checksum}</sha512>|" "$xml_file"
        fi
    fi
}

update_xml "$UPDATE_DIR/com_healthchecker.xml" "com_healthchecker"
update_xml "$UPDATE_DIR/mod_healthchecker.xml" "mod_healthchecker"
update_xml "$UPDATE_DIR/pkg_healthchecker.xml" "pkg_healthchecker"
update_xml "$UPDATE_DIR/plg_healthchecker_core.xml" "plg_healthchecker_core"
update_xml "$UPDATE_DIR/plg_healthchecker_example.xml" "plg_healthchecker_example"
update_xml "$UPDATE_DIR/plg_healthchecker_akeebabackup.xml" "plg_healthchecker_akeebabackup"
update_xml "$UPDATE_DIR/plg_healthchecker_akeebaadmintools.xml" "plg_healthchecker_akeebaadmintools"
update_xml "$UPDATE_DIR/plg_healthchecker_mysitesguru.xml" "plg_healthchecker_mysitesguru"

# Update collection XML
sed -i '' "s|<version>[0-9.]*</version>|<version>${NEW_VERSION}</version>|g" "$UPDATE_DIR/update_healthchecker.xml"
sed -i '' "s|-[0-9.]*\.zip|-${NEW_VERSION}.zip|g" "$UPDATE_DIR/update_healthchecker.xml"

while IFS='=' read -r extension checksum; do
    awk -v ext="$extension" -v cs="$checksum" -v ver="${NEW_VERSION}" '
    /<downloadurl.*'$extension'-'$NEW_VERSION'.zip/ { found=1 }
    found && /<sha512>/ { sub(/<sha512>.*<\/sha512>/, "<sha512>" cs "</sha512>"); found=0 }
    { print }
    ' "$UPDATE_DIR/update_healthchecker.xml" > /tmp/collection.xml && mv /tmp/collection.xml "$UPDATE_DIR/update_healthchecker.xml"
done < "$CHECKSUMS_FILE"

rm -f "$CHECKSUMS_FILE"
echo -e "${GREEN}✓ XML files updated${NC}"

# Update website download buttons to point to GitHub release
echo ""
echo -e "${YELLOW}Updating website download buttons...${NC}"

GITHUB_DOWNLOAD_URL="https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/v${NEW_VERSION}/pkg_healthchecker-${NEW_VERSION}.zip"
GITHUB_RELEASE_URL="https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/v${NEW_VERSION}"
RELEASE_DATE=$(date "+%d %b %Y" | sed 's/^0//')

# Update index.html download buttons and version info
WEBSITE_INDEX="$PROJECT_ROOT/website/public/index.html"
if [ -f "$WEBSITE_INDEX" ]; then
    # Update all href="#download" to point to the GitHub package download
    sed -i '' "s|href=\"#download\"|href=\"${GITHUB_DOWNLOAD_URL}\"|g" "$WEBSITE_INDEX"

    # Update the version release link
    sed -i '' "s|href=\"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/v[^\"]*\"|href=\"${GITHUB_RELEASE_URL}\"|g" "$WEBSITE_INDEX"

    # Update the version text (e.g., "Latest version: v3.0.28 Released 21st Jan 2026")
    # Use perl for multiline matching since text may span lines
    perl -i -0pe "s|>Latest version: v[0-9.]+ Released [^<]+<|>Latest version: v${NEW_VERSION} Released ${RELEASE_DATE}<|gs" "$WEBSITE_INDEX"

    # Update Schema.org metadata
    sed -i '' "s|\"softwareVersion\": \"[^\"]*\"|\"softwareVersion\": \"${NEW_VERSION}\"|g" "$WEBSITE_INDEX"
    sed -i '' "s|\"downloadUrl\": \"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/[^\"]*\"|\"downloadUrl\": \"${GITHUB_DOWNLOAD_URL}\"|g" "$WEBSITE_INDEX"
    sed -i '' "s|\"installUrl\": \"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/[^\"]*\"|\"installUrl\": \"${GITHUB_DOWNLOAD_URL}\"|g" "$WEBSITE_INDEX"
    sed -i '' "s|\"releaseNotes\": \"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/[^\"]*\"|\"releaseNotes\": \"${GITHUB_RELEASE_URL}\"|g" "$WEBSITE_INDEX"

    # Update download links that point to GitHub package downloads
    sed -i '' "s|href=\"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/v[^/]*/pkg_healthchecker-[^\"]*\.zip\"|href=\"${GITHUB_DOWNLOAD_URL}\"|g" "$WEBSITE_INDEX"

    echo -e "${GREEN}✓ Website download buttons updated to v${NEW_VERSION}${NC}"
fi

# Create git tag
echo ""
echo -e "${YELLOW}Creating git tag...${NC}"
git tag -a "v${NEW_VERSION}" -m "Release version ${NEW_VERSION}

${RELEASE_NOTES}"
echo -e "${GREEN}✓ Tag v${NEW_VERSION} created${NC}"

# Push changes and tag
echo ""
echo -e "${YELLOW}Pushing to GitHub...${NC}"
git push origin main
git push origin "v${NEW_VERSION}"
echo -e "${GREEN}✓ Pushed to GitHub${NC}"

# Commit website updates
echo ""
echo -e "${YELLOW}Committing website updates...${NC}"
git add website/public/update/
git add website/public/downloads/

if ! git diff --cached --quiet; then
    git commit -m "Update website files for v${NEW_VERSION}

- Update all XML files to v${NEW_VERSION}
- Add SHA512 checksums for all packages
- Copy v${NEW_VERSION} ZIPs to website downloads"
    git push origin main
    echo -e "${GREEN}✓ Website updates pushed${NC}"
fi

# Create GitHub release
echo ""
echo -e "${YELLOW}Creating GitHub release...${NC}"

COMMIT_LIST=""
if [ -n "$GIT_LOG" ] && [ -n "$LAST_TAG" ]; then
    REPO_URL=$(git config --get remote.origin.url | sed 's/git@github.com:/https:\/\/github.com\//' | sed 's/\.git$//')
    COMMIT_LIST="

---

**Full Changelog**: ${REPO_URL}/compare/${LAST_TAG}...v${NEW_VERSION}"
fi

RELEASE_NOTES_FILE="$BUILD_DIR/release-notes-${NEW_VERSION}.md"
cat > "$RELEASE_NOTES_FILE" << EOF
${RELEASE_NOTES}
${COMMIT_LIST}

## Installation

Download the **Complete Package (Recommended)** which installs everything you need.

**What gets installed:**
- ✓ Component: com_healthchecker
- ✓ Module: mod_healthchecker (dashboard widget)
- ✓ Plugin: plg_healthchecker_core (All core checks)
- ✓ Plugin: plg_healthchecker_example (SDK reference)
- ✓ Plugin: plg_healthchecker_mysitesguru (API integration)
- ✓ Plugin: plg_healthchecker_akeebabackup (auto-enabled if Akeeba Backup installed)
- ✓ Plugin: plg_healthchecker_akeebaadmintools (auto-enabled if Admin Tools installed)

## Requirements

- Joomla 5.0 or later
- PHP 8.1 or later
EOF

gh release create "v${NEW_VERSION}" \
    --title "Health Checker for Joomla v${NEW_VERSION}" \
    --notes-file "$RELEASE_NOTES_FILE" \
    "$BUILD_DIR/pkg_healthchecker-${NEW_VERSION}.zip#Complete Package (Recommended)" \
    "$BUILD_DIR/com_healthchecker-${NEW_VERSION}.zip" \
    "$BUILD_DIR/mod_healthchecker-${NEW_VERSION}.zip" \
    "$BUILD_DIR/plg_healthchecker_core-${NEW_VERSION}.zip" \
    "$BUILD_DIR/plg_healthchecker_example-${NEW_VERSION}.zip" \
    "$BUILD_DIR/plg_healthchecker_akeebabackup-${NEW_VERSION}.zip" \
    "$BUILD_DIR/plg_healthchecker_akeebaadmintools-${NEW_VERSION}.zip" \
    "$BUILD_DIR/plg_healthchecker_mysitesguru-${NEW_VERSION}.zip"

rm -f "$RELEASE_NOTES_FILE"
echo -e "${GREEN}✓ GitHub release created${NC}"

# Comment on referenced issues and PRs
if [ -n "$CLOSED_ISSUES" ]; then
    echo ""
    echo -e "${YELLOW}Commenting on referenced issues and PRs...${NC}"
    RELEASE_URL="https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/v${NEW_VERSION}"
    COMMENT_BODY="Thanks. This change has just been released in [v${NEW_VERSION}](${RELEASE_URL})."

    echo "$CLOSED_ISSUES" | grep -oE '#[0-9]+' | sort -t'#' -k2 -n -u | while read -r ref; do
        num="${ref#\#}"
        if gh issue comment "$num" --body "$COMMENT_BODY" 2>/dev/null; then
            echo -e "  ${GREEN}✓ Commented on issue #${num}${NC}"
        elif gh pr comment "$num" --body "$COMMENT_BODY" 2>/dev/null; then
            echo -e "  ${GREEN}✓ Commented on PR #${num}${NC}"
        else
            echo -e "  ${YELLOW}⚠ Could not comment on #${num}${NC}"
        fi
    done
    echo -e "${GREEN}✓ Issue/PR comments posted${NC}"
fi

# Regenerate changelog (now that the GitHub release exists, it will be included)
echo ""
echo -e "${YELLOW}Regenerating changelog with new release...${NC}"
bash "$SCRIPT_DIR/changelog.sh"
git add docs/USER/changelog.md
if ! git diff --cached --quiet; then
    git commit -m "Update changelog for v${NEW_VERSION}"
    git push origin main
    echo -e "${GREEN}✓ Changelog updated${NC}"
fi

# Build and deploy website (rebuilds docs including changelog, deploys to Cloudflare)
echo ""
bash "$SCRIPT_DIR/website-deploy.sh"

# CHECK TRANSLATIONS AND OPEN ISSUES FOR MISSING KEYS
echo ""
echo -e "${YELLOW}Checking for missing translation keys...${NC}"

COMMIT_SHA=$(git rev-parse HEAD)
REPO_BASE="https://github.com/mySites-guru/HealthCheckerForJoomla/blob/${COMMIT_SHA}"

# Fetch existing open translation issues to avoid duplicates (keyed by language in title)
OPEN_TRANSLATION_JSON=$(gh issue list --label translations --state open --json title,body 2>/dev/null || echo "[]")

# Find all English .ini files
EN_FILES=$(find "$SOURCE_DIR" -path "*/language/en-GB/*.ini" -type f | sort)

for LANG_ENTRY in "ru-RU:alex-revo" "es-ES:alamarte"; do
    LANG="${LANG_ENTRY%%:*}"
    TRANSLATOR="${LANG_ENTRY##*:}"

    # Extract already-requested keys and files from open issues matching this language
    LANG_ISSUE_BODIES=$(echo "$OPEN_TRANSLATION_JSON" | \
        jq -r ".[] | select(.title | contains(\"${LANG}\")) | .body" 2>/dev/null)
    ALREADY_REQUESTED_KEYS=$(echo "$LANG_ISSUE_BODIES" | \
        grep -oE '`[A-Z][A-Z0-9_]+`' | tr -d '`' | sort -u)
    # Also extract .ini filenames already mentioned (for "missing file entirely" references)
    ALREADY_MENTIONED_FILES=$(echo "$LANG_ISSUE_BODIES" | \
        grep -oE '[a-z_]+(\.[a-z]+)?\.ini' | sort -u)
    ISSUE_BODY=""
    TOTAL_NEW_KEYS=0

    while IFS= read -r EN_FILE; do
        # Derive the translated file path
        TRANS_FILE=$(echo "$EN_FILE" | sed "s|/en-GB/|/${LANG}/|g")

        # Get relative path for display
        REL_PATH=$(echo "$EN_FILE" | sed "s|${PROJECT_ROOT}/healthchecker/||")
        BASENAME=$(basename "$EN_FILE")

        # Skip if this file is already mentioned in an open issue
        if echo "$ALREADY_MENTIONED_FILES" | grep -q "^${BASENAME}$" && [ ! -f "$TRANS_FILE" ]; then
            continue
        fi

        # Extract English keys (lines starting with a key before =, skip comments/blank)
        EN_KEYS=$(grep -oE '^[A-Z][A-Z0-9_]+=' "$EN_FILE" | sed 's/=$//' | sort)

        if [ -f "$TRANS_FILE" ]; then
            TRANS_KEYS=$(grep -oE '^[A-Z][A-Z0-9_]+=' "$TRANS_FILE" | sed 's/=$//' | sort)
        else
            TRANS_KEYS=""
        fi

        # Find missing keys
        MISSING_KEYS=$(comm -23 <(echo "$EN_KEYS") <(echo "$TRANS_KEYS"))

        # Filter out keys already requested in open issues
        if [ -n "$ALREADY_REQUESTED_KEYS" ] && [ -n "$MISSING_KEYS" ]; then
            MISSING_KEYS=$(comm -23 <(echo "$MISSING_KEYS") <(echo "$ALREADY_REQUESTED_KEYS"))
        fi

        if [ -z "$MISSING_KEYS" ]; then
            continue
        fi

        KEY_COUNT=$(echo "$MISSING_KEYS" | wc -l | tr -d ' ')
        TOTAL_NEW_KEYS=$((TOTAL_NEW_KEYS + KEY_COUNT))

        # Build table for this file
        # Get the en-GB reference file path relative to repo root
        EN_REL=$(echo "$EN_FILE" | sed "s|${PROJECT_ROOT}/||")
        ISSUE_BODY="${ISSUE_BODY}

### \`${REL_PATH}\` — ${KEY_COUNT} keys

English reference: [\`${BASENAME}\`](${REPO_BASE}/${EN_REL})

| Key | en-GB line |
|-----|-----------|
"
        while IFS= read -r KEY; do
            LINE_NUM=$(grep -n "^${KEY}=" "$EN_FILE" | head -1 | cut -d: -f1)
            ISSUE_BODY="${ISSUE_BODY}| \`${KEY}\` | [L${LINE_NUM}](${REPO_BASE}/${EN_REL}#L${LINE_NUM}) |
"
        done <<< "$MISSING_KEYS"
    done <<< "$EN_FILES"

    if [ "$TOTAL_NEW_KEYS" -eq 0 ]; then
        echo -e "  ${BLUE}${LANG}: No new missing keys${NC}"
        continue
    fi

    # Use Claude to generate a natural issue title and intro paragraph, then humanize
    echo -e "  ${YELLOW}Generating issue text with Claude...${NC}"
    PROMPT_FILE=$(mktemp)
    cat > "$PROMPT_FILE" << ISSUE_PROMPT
Write a GitHub issue title and body for a translation update request.

Context:
- Language: ${LANG}
- Translator GitHub username: ${TRANSLATOR}
- Version just released: v${NEW_VERSION}
- Number of missing keys: ${TOTAL_NEW_KEYS}
- The key tables below must be included verbatim in the body after your intro paragraph.

Rules:
1. Title must be concise, under 80 chars, include the language code and version.
2. Body must start by @-mentioning the translator (use @${TRANSLATOR}).
3. Body should be friendly and direct — one short paragraph explaining what is needed and why (new release, new keys).
4. Do NOT use emoji. Do NOT use corporate or AI-sounding language.
5. Do NOT repeat the key tables — just output the intro paragraph. The tables will be appended automatically.
6. Output format — first line is the title, then a blank line, then the body paragraph. Nothing else.
ISSUE_PROMPT
    ISSUE_TEXT=$(cat "$PROMPT_FILE" | claude_print 2>/dev/null) || true
    rm -f "$PROMPT_FILE"

    PROMPT_FILE=$(mktemp)
    cat > "$PROMPT_FILE" << HUMANIZE_PROMPT
Review this GitHub issue title and body text. Remove any signs of AI-generated writing:
- No inflated significance ("crucial", "pivotal", "vital", "key", "testament")
- No promotional language ("vibrant", "comprehensive", "groundbreaking", "exciting")
- No em dash overuse — use commas or periods instead
- No rule-of-three lists
- No negative parallelisms ("not just X, but Y")
- No sycophantic tone ("Great!", "Absolutely!")
- No filler phrases ("In order to", "It is important to note")
- No copula avoidance — use "is/are/has" instead of "serves as/stands as"
- No curly quotes — use straight quotes only
- Keep it short, friendly, and direct. One paragraph max for the body.

The text must keep the exact same format: first line is the title, blank line, then the body.
Do NOT add anything extra. Output ONLY the cleaned text.

Text to humanize:
${ISSUE_TEXT}
HUMANIZE_PROMPT
    ISSUE_TEXT=$(cat "$PROMPT_FILE" | claude_print 2>/dev/null) || true
    rm -f "$PROMPT_FILE"

    ISSUE_TITLE=$(echo "$ISSUE_TEXT" | head -1)
    ISSUE_INTRO=$(echo "$ISSUE_TEXT" | tail -n +3)
    FULL_BODY="${ISSUE_INTRO}
${ISSUE_BODY}"

    gh issue create \
        --title "$ISSUE_TITLE" \
        --body "$FULL_BODY" \
        --label "translations" 2>/dev/null

    if [ $? -eq 0 ]; then
        echo -e "  ${GREEN}✓ ${LANG}: Opened issue for ${TOTAL_NEW_KEYS} missing keys${NC}"
    else
        echo -e "  ${YELLOW}⚠ ${LANG}: Failed to create issue${NC}"
    fi
done

echo -e "${GREEN}✓ Translation check complete${NC}"

echo ""
echo "===================================================================="
echo -e "${GREEN}Release ${NEW_VERSION} complete!${NC}"
echo ""
echo "Release URL: https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/v${NEW_VERSION}"
echo "Website:     https://www.joomlahealthchecker.com"
echo ""
