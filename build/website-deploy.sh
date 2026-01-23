#!/bin/bash

# Health Checker for Joomla - Website Deploy Script
# Build and deploy the website independently from package releases

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${GREEN}Health Checker for Joomla - Website Deploy${NC}"
echo "======================================================"
echo ""

# Rebuild documentation
echo -e "${YELLOW}Rebuilding documentation...${NC}"
cd "$PROJECT_ROOT/docs/USER"

if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}Installing documentation dependencies...${NC}"
    npm install
fi

npm run docs:build

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Documentation built${NC}"
else
    echo -e "${RED}✗ Documentation build failed${NC}"
    echo -e "${YELLOW}Continuing anyway...${NC}"
fi

# Get latest version from GitHub
echo ""
echo -e "${YELLOW}Fetching latest release information...${NC}"
cd "$PROJECT_ROOT"
RELEASE_INFO=$(gh release list --limit 1 2>/dev/null || echo "")

if [ -n "$RELEASE_INFO" ]; then
    LATEST_VERSION=$(echo "$RELEASE_INFO" | awk -F'\t' '{print $3}')
    RELEASE_DATE=$(echo "$RELEASE_INFO" | awk -F'\t' '{print $4}' | cut -d'T' -f1)
    echo -e "${BLUE}Latest version: $LATEST_VERSION${NC}"
    echo -e "${BLUE}Release date: $RELEASE_DATE${NC}"
else
    echo -e "${YELLOW}Could not fetch release info, using defaults${NC}"
    LATEST_VERSION="v3.0.19"
    RELEASE_DATE="2026-01-17"
fi

# Build website
echo ""
echo -e "${YELLOW}Building website...${NC}"
cd "$PROJECT_ROOT/website"
export LATEST_VERSION="$LATEST_VERSION"
export RELEASE_DATE="$RELEASE_DATE"
./build.sh

if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Website build failed${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Website built${NC}"

# Check for changes
cd "$PROJECT_ROOT"
CHANGES=0

if ! git diff --quiet website/public/docs/; then
    CHANGES=1
fi

if ! git diff --quiet website/public/index.html website/public/404.html website/public/output.css website/public/*.js 2>/dev/null; then
    CHANGES=1
fi

if [ $CHANGES -eq 1 ]; then
    echo ""
    echo -e "${YELLOW}Committing website changes...${NC}"

    git add website/public/docs/
    git add website/public/search-widget.js
    git add website/public/index.html
    git add website/public/404.html
    git add website/public/output.css
    git add website/public/*.js 2>/dev/null || true

    if ! git diff --cached --quiet; then
        git commit -m "Update website

- Rebuild documentation
- Update version to $LATEST_VERSION
- Rebuild and minify assets"
        git push origin main
        echo -e "${GREEN}✓ Changes committed and pushed${NC}"
    fi
else
    echo -e "${BLUE}No website changes to commit${NC}"
fi

# Deploy to Cloudflare Workers
echo ""
echo -e "${YELLOW}Deploying to Cloudflare Workers...${NC}"
cd "$PROJECT_ROOT/website"
npx wrangler deploy

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Website deployed successfully${NC}"
else
    echo -e "${RED}✗ Website deployment failed${NC}"
    exit 1
fi

echo ""
echo "======================================================"
echo -e "${GREEN}Website deployment complete!${NC}"
echo ""
echo "Website: https://www.joomlahealthchecker.com"
echo "Version: $LATEST_VERSION"
echo ""
