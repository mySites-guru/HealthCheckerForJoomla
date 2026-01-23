#!/bin/bash

# Update the search index hash in the website's search-widget.js
# Run this after building VitePress docs

set -e

# Find the search index file
SEARCH_INDEX=$(find ../../website/public/docs/assets/chunks -name "@localSearchIndexroot.*.js" 2>/dev/null | head -1)

if [ -z "$SEARCH_INDEX" ]; then
    echo "Error: Could not find search index file"
    echo "Make sure you've built the docs first: npm run docs:build"
    exit 1
fi

# Extract the hash from the filename
HASH=$(basename "$SEARCH_INDEX" | sed -E 's/@localSearchIndexroot\.(.+)\.js/\1/')

echo "Found search index hash: $HASH"

# Update the search-widget.js file
SEARCH_WIDGET="../../website/public/search-widget.js"

if [ ! -f "$SEARCH_WIDGET" ]; then
    echo "Error: search-widget.js not found at $SEARCH_WIDGET"
    exit 1
fi

# Replace the hash using sed
sed -i.bak -E "s/(const SEARCH_INDEX_HASH = ')[^']+'/\1$HASH'/" "$SEARCH_WIDGET"

echo "✓ Updated search-widget.js with hash: $HASH"
echo "✓ Backup created: search-widget.js.bak"

# Verify the change
if grep -q "SEARCH_INDEX_HASH = '$HASH'" "$SEARCH_WIDGET"; then
    echo "✓ Verified: Hash successfully updated"
    rm -f "${SEARCH_WIDGET}.bak"  # Remove backup if successful
else
    echo "✗ Warning: Could not verify the update"
    echo "  Please manually check $SEARCH_WIDGET"
fi
