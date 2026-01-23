#!/bin/bash
set -e

echo "=== Building Health Checker Website ==="

# Pre-build validation: Check HTML template is correct
echo "Validating HTML template..."
if ! grep -qE '<link rel="stylesheet" href="/output\.css(\?[^"]*)?"\s*/?>' public/index.html; then
    echo "ERROR: HTML template missing CSS link!"
    echo "Expected: <link rel=\"stylesheet\" href=\"/output.css\">"
    echo "Please ensure public/index.html has the correct CSS link tag."
    exit 1
fi

# Check for accidental inline CSS in template
if grep -q '<style>.*tailwindcss' public/index.html; then
    echo "WARNING: Found inline Tailwind CSS in HTML template!"
    echo "This suggests CSS was accidentally inlined. The template should only have:"
    echo "  <link rel=\"stylesheet\" href=\"/output.css\">"
    echo ""
    echo "Attempting to fix..."

    # Create backup
    cp public/index.html public/index.html.broken

    # Fix by replacing any filled style tag with just the CSS link
    node -e "
    const fs = require('fs');
    let html = fs.readFileSync('./public/index.html', 'utf8');

    // Remove any inline tailwind CSS
    html = html.replace(/<style>\/\*! tailwindcss[\s\S]*?<\/style>/g, '<link rel=\"stylesheet\" href=\"/output.css\">');

    fs.writeFileSync('./public/index.html', html);
    console.log('Fixed: Removed inline CSS, using external link');
    "

    echo "Backup saved to public/index.html.broken"
fi

# Compile Tailwind CSS
echo "Compiling Tailwind CSS..."
./tailwindcss-macos-arm64 -i ./src/input.css -o ./dist/output.css --minify

# Validate CSS output size
CSS_SIZE=$(wc -c < ./dist/output.css)
CSS_SIZE_KB=$((CSS_SIZE / 1024))

echo "Compiled CSS size: ${CSS_SIZE_KB}KB"

if [ $CSS_SIZE -lt 10000 ]; then
    echo "ERROR: CSS file is suspiciously small (${CSS_SIZE_KB}KB). Build may have failed."
    exit 1
fi

if [ $CSS_SIZE -gt 200000 ]; then
    echo "WARNING: CSS file is larger than expected (${CSS_SIZE_KB}KB)."
    echo "Expected size: 80-100KB. Please review Tailwind configuration."
fi

# Copy CSS to public directory for external linking
echo "Copying CSS to public directory..."
cp ./dist/output.css ./public/output.css

# Copy sitemap from docs to public root (if it exists)
if [ -f "public/docs/sitemap.xml" ]; then
    echo "Copying sitemap from docs to public root..."
    cp public/docs/sitemap.xml public/sitemap.xml
    echo "✓ Sitemap copied to public/sitemap.xml"
else
    echo "Warning: docs/sitemap.xml not found. Run VitePress build first to generate it."
fi

# Copy llms.txt files from docs to public root (if they exist)
if [ -f "public/docs/llms.txt" ]; then
    echo "Copying LLM documentation files to public root..."
    cp public/docs/llms.txt public/llms.txt
    echo "✓ llms.txt copied to public/llms.txt"
else
    echo "Warning: docs/llms.txt not found. Run VitePress build first to generate it."
fi

if [ -f "public/docs/llms-full.txt" ]; then
    cp public/docs/llms-full.txt public/llms-full.txt
    echo "✓ llms-full.txt copied to public/llms-full.txt"
else
    echo "Warning: docs/llms-full.txt not found."
fi

# Minify JavaScript files (except search-widget.js which is updated by VitePress buildEnd hook)
echo "Minifying JavaScript files..."
if command -v npx &> /dev/null; then
    for jsfile in public/waitlist-popover.js public/livechat.js; do
        if [ -f "$jsfile" ]; then
            # Save original for comparison
            ORIGINAL_SIZE=$(wc -c < "$jsfile")

            # Minify
            npx terser "$jsfile" -c -m -o "${jsfile}.tmp" 2>/dev/null && mv "${jsfile}.tmp" "$jsfile"

            # Report
            MINIFIED_SIZE=$(wc -c < "$jsfile")
            SAVINGS=$((ORIGINAL_SIZE - MINIFIED_SIZE))
            if [ $SAVINGS -gt 0 ]; then
                PERCENT=$((SAVINGS * 100 / ORIGINAL_SIZE))
                echo "  $(basename $jsfile): ${ORIGINAL_SIZE} → ${MINIFIED_SIZE} bytes (saved ${PERCENT}%)"
            fi
        fi
    done
    echo "JavaScript files minified"
else
    echo "Warning: npx not found, skipping JS minification"
fi

# Fetch latest release information from GitHub using gh CLI
# Only fetch if not already set via environment variables (e.g., from release.sh)
if [ -z "$LATEST_VERSION" ] || [ -z "$RELEASE_DATE" ]; then
    echo "Fetching latest release information from GitHub..."
    cd ..
    RELEASE_INFO=$(gh release list --limit 1 2>/dev/null || echo "")
    cd website

    if [ -n "$RELEASE_INFO" ]; then
        LATEST_VERSION=$(echo "$RELEASE_INFO" | awk -F'\t' '{print $3}')
        RELEASE_DATE=$(echo "$RELEASE_INFO" | awk -F'\t' '{print $4}' | cut -d'T' -f1)
        echo "Latest version: $LATEST_VERSION"
        echo "Release date: $RELEASE_DATE"
    else
        echo "Warning: Could not fetch release info from GitHub, using defaults"
        LATEST_VERSION="v2.0.2"
        RELEASE_DATE="2026-01-16"
    fi
else
    echo "Using version from environment: $LATEST_VERSION ($RELEASE_DATE)"
fi

# Export variables for node subprocess
export LATEST_VERSION
export RELEASE_DATE

# Process HTML: Update Schema.org and version display (CSS link already in template)
echo "Updating Schema.org metadata and version display..."
node -e "
const fs = require('fs');
let html = fs.readFileSync('./public/index.html', 'utf8');

// Get version info from environment
const version = process.env.LATEST_VERSION || 'v2.0.2';
const releaseDate = process.env.RELEASE_DATE || '2026-01-16';
const versionClean = version.replace('v', '');

// Format date for display (e.g., \"16th Jan 2026\")
const date = new Date(releaseDate);
const day = date.getDate();
const daySuffix = day === 1 || day === 21 || day === 31 ? 'st' : (day === 2 || day === 22 ? 'nd' : (day === 3 || day === 23 ? 'rd' : 'th'));
const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const formattedDate = \`\${day}\${daySuffix} \${monthNames[date.getMonth()]} \${date.getFullYear()}\`;

// IMPORTANT: Do NOT modify CSS - it should already be linked externally
// Only update version/date metadata

// Update Schema.org JSON-LD version and URLs
html = html.replace(
  /\"softwareVersion\": \"[^\"]+\"/,
  \`\"softwareVersion\": \"\${versionClean}\"\`
);

html = html.replace(
  /\"releaseNotes\": \"https:\/\/github\.com\/PhilETaylor\/health-checker-for-joomla\/releases\/tag\/[^\"]+\"/,
  \`\"releaseNotes\": \"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/\${version}\"\`
);

html = html.replace(
  /\"downloadUrl\": \"https:\/\/github\.com\/PhilETaylor\/health-checker-for-joomla\/releases\/download\/[^\/]+\/[^\"]+\"/,
  \`\"downloadUrl\": \"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/\${version}/pkg_healthchecker-\${version}.zip\"\`
);

html = html.replace(
  /\"installUrl\": \"https:\/\/github\.com\/PhilETaylor\/health-checker-for-joomla\/releases\/download\/[^\/]+\/[^\"]+\"/,
  \`\"installUrl\": \"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/\${version}/pkg_healthchecker-\${version}.zip\"\`
);

html = html.replace(
  /\"datePublished\": \"[^\"]+\"/,
  \`\"datePublished\": \"\${releaseDate}\"\`
);

html = html.replace(
  /\"dateModified\": \"[^\"]+\"/,
  \`\"dateModified\": \"\${releaseDate}\"\`
);

// Update visible \"Latest version\" text in the HTML (handles multiline HTML)
html = html.replace(
  /<a\\s+href=\"https:\/\/github\\.com\/PhilETaylor\/health-checker-for-joomla\/releases\/tag\/[^\"]+\"[\\s\\S]*?>Latest version:[\\s\\S]*?<\/a\\s*>/,
  \`<a href=\"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/\${version}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"text-xs font-mono text-gray-600 hover:text-joomla-primary transition-colors no-underline\">Latest version: \${version} Released \${formattedDate}</a>\`
);

fs.writeFileSync('./public/index.html', html);
console.log('✓ Schema.org and version display updated to ' + version);

// Also update 404.html if it exists
if (fs.existsSync('./public/404.html')) {
  let html404 = fs.readFileSync('./public/404.html', 'utf8');

  // Update any version references in 404 page (handles multiline HTML)
  html404 = html404.replace(
    /<a\\s+href=\"https:\/\/github\\.com\/PhilETaylor\/health-checker-for-joomla\/releases\/tag\/[^\"]+\"[\\s\\S]*?>Latest version:[\\s\\S]*?<\/a\\s*>/,
    \`<a href=\"https://github.com/mySites-guru/HealthCheckerForJoomla/releases/tag/\${version}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"text-xs font-mono text-gray-600 hover:text-joomla-primary transition-colors no-underline\">Latest version: \${version} Released \${formattedDate}</a>\`
  );

  fs.writeFileSync('./public/404.html', html404);
  console.log('✓ 404 page updated');
}
"

# Post-build validation
echo ""
echo "=== Build Validation ==="

# Check final HTML size
HTML_SIZE=$(wc -c < ./public/index.html)
HTML_SIZE_KB=$((HTML_SIZE / 1024))
echo "HTML size: ${HTML_SIZE_KB}KB"

if [ $HTML_SIZE -gt 500000 ]; then
    echo "ERROR: HTML file is too large (${HTML_SIZE_KB}KB)!"
    echo "Expected size: ~220KB. This suggests CSS was accidentally inlined."
    echo "Please check public/index.html for inline <style> tags."
    exit 1
fi

# Verify CSS is linked, not inlined
if grep -qE '<link rel="stylesheet" href="/output\.css(\?[^"]*)?"\s*/?>' public/index.html; then
    echo "✓ CSS correctly linked externally"
else
    echo "ERROR: CSS link not found in HTML!"
    exit 1
fi

# Check for accidental CSS duplication
if grep -q "tailwindcss v4" public/index.html; then
    CSS_COUNT=$(grep -c "tailwindcss v4" public/index.html)
    echo "ERROR: Found inline Tailwind CSS in HTML (${CSS_COUNT} copies)!"
    echo "CSS should be external. Build failed."
    exit 1
fi

echo "✓ No inline CSS duplication detected"

# Check output.css exists in public
if [ -f "public/output.css" ]; then
    OUTPUT_CSS_SIZE=$(wc -c < public/output.css)
    OUTPUT_CSS_KB=$((OUTPUT_CSS_SIZE / 1024))
    echo "✓ External CSS file: ${OUTPUT_CSS_KB}KB"
else
    echo "ERROR: public/output.css not found!"
    exit 1
fi

echo ""
echo "=== Build Summary ==="
echo "HTML: ${HTML_SIZE_KB}KB (uncompressed)"
echo "CSS: ${OUTPUT_CSS_KB}KB (external, cached separately)"
echo "JS: $(du -ch public/*.js 2>/dev/null | tail -1 | awk '{print $1}')"
echo ""
echo "Build complete! ✓"
echo "Version: $LATEST_VERSION"
