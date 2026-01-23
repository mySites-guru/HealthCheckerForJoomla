---
url: /docs/checks/content.md
---
# Content Quality Checks

Content quality checks evaluate the health of your articles, menus, and media files. Quality content is essential for user engagement, SEO, and site maintenance.

**Total checks in this category: 11**

## Content Health (4 checks)

### Orphaned Articles

Identifies published articles not accessible via menu.

* **Good**: All articles linked from menus
* **Warning**: Orphaned articles found

**Why it matters**: Orphaned articles are:

* Hard for users to discover
* Poorly indexed by search engines
* Wasted content effort
* Potential duplicate content

**How to find**: Articles → Manage → Filter by Menu: None

**How to fix**:

1. Create menu item for article
2. Link from related articles
3. Add to category blog
4. Feature on homepage
5. Unpublish if truly orphaned

### Empty Categories

Detects categories with no published content.

* **Good**: All categories contain content
* **Warning**: Empty categories found

**Why it matters**: Empty categories:

* Create poor user experience
* Generate 404-like pages
* Waste crawl budget
* Indicate outdated structure

**How to fix**:

1. Add content to category
2. Merge with related category
3. Unpublish if no longer needed
4. Delete if completely unused

### Unpublished Draft Articles

Finds articles in draft status for extended periods.

* **Good**: No stale drafts
* **Warning**: Drafts older than 90 days

**Why it matters**: Old drafts indicate:

* Abandoned content projects
* Editor workflow issues
* Content planning problems
* Database clutter

**How to handle**:

1. Review old drafts monthly
2. Publish if ready
3. Set completion deadline
4. Archive or delete if abandoned
5. Assign to different writer

### Articles with Broken Images

Scans for articles referencing missing images.

* **Good**: All images exist
* **Warning**: Broken image references found

**Why it matters**: Broken images:

* Create poor user experience
* Hurt SEO rankings
* Indicate migration issues
* Waste server resources (404 requests)

**How to fix**:

1. Re-upload missing images
2. Update image paths
3. Remove broken references
4. Check for case sensitivity issues
5. Verify file permissions

### Articles in Unpublished Categories

Identifies published articles that exist in unpublished categories.

* **Good**: All published articles are in published categories
* **Warning**: Published articles found in unpublished categories

**Why it matters**: When an article is published but its category is unpublished:

* The article becomes invisible to visitors even though editors think it's live
* Content appears "missing" to users even though it exists
* Internal links to these articles return 404 or access denied errors
* SEO impact from dead links and missing content

**Common causes**:

* Category unpublished for maintenance but articles weren't updated
* Category access levels changed without considering child content
* Content imported or migrated without proper category states

**How to fix**:

1. Publish the category if content should be visible
2. Unpublish the articles if the category should stay hidden
3. Move articles to a published category

## Menu & Navigation (4 checks)

### Broken Menu Links

Identifies menu items pointing to non-existent content.

* **Good**: All menu links functional
* **Warning**: Broken links found

**Why it matters**: Broken menu links:

* Frustrate users with 404 errors
* Hurt site credibility
* Indicate poor maintenance
* Waste navigation hierarchy

**Common causes**:

* Deleted articles
* Unpublished content
* Changed article aliases
* Component uninstallation

**How to fix**:

1. Identify broken menu items
2. Update to point to correct content
3. Remove if content permanently gone
4. Set up 301 redirects for external links

### Orphaned Menu Items

Detects menu items not displayed in any menu position.

* **Good**: All menu items assigned to modules
* **Warning**: Orphaned menu items found

**Why it matters**: Orphaned menu items:

* Don't appear anywhere on site
* Create confusion in admin
* Waste database space
* Indicate incomplete setup

**How to find**: Menus → All Menu Items → Check for items without module assignment

**How to fix**:

1. Assign to appropriate menu module
2. Trash if no longer needed
3. Move to correct menu
4. Check menu module is published

### Menu Items to Unpublished Content

Finds menu links pointing to unpublished articles.

* **Good**: All menu items point to published content
* **Warning**: Links to unpublished content found

**Why it matters**: Users see link but get 404 or access denied.

**Common scenarios**:

* Article unpublished for editing
* Scheduled publish date in future
* Access level mismatch
* Forgot to republish after changes

**How to fix**:

1. Publish the target article
2. Update menu item to different article
3. Remove menu item temporarily
4. Check publish dates and access levels

### Duplicate Menu Aliases

Identifies menu items with identical aliases.

* **Good**: All aliases unique
* **Warning**: Duplicate aliases found

**Why it matters**: Duplicate aliases cause:

* URL conflicts
* Routing confusion
* SEO duplicate content issues
* Unpredictable behavior

**How to fix**:

1. Menus → All Menu Items → Sort by Alias
2. Identify duplicates
3. Edit to make unique
4. Consider numbering scheme for similar items

## Media Management (2 checks)

### Unused Media Files

Identifies media files not referenced in content.

* **Good**: All media files in use
* **Warning**: Unused files found

**Why it matters**: Unused media:

* Wastes disk space
* Increases backup size
* Slows media manager
* Costs money on storage

**How to handle**:

1. Run media audit quarterly
2. Archive old campaign images
3. Delete truly unused files
4. Back up before mass deletion
5. Consider external storage for archives

**Caution**: Some files may be used in:

* Custom modules
* Template code
* Third-party extensions
* Verify before deleting

### Missing Media References

Detects content references to non-existent media.

* **Good**: All media references valid
* **Warning**: Missing media found

**Why it matters**: Missing media:

* Creates broken image icons
* Hurts professional appearance
* Indicates migration issues
* Generates 404 errors

**Common causes**:

* Deleted media files
* Incorrect paths after migration
* Case sensitivity issues (Linux)
* Missing uploads folder

**How to fix**:

1. Re-upload missing files
2. Update file paths in content
3. Use find/replace for bulk updates
4. Check folder permissions
5. Verify file extensions correct

## Common Content Issues & Solutions

### Stale Content

**Symptoms**: Old, outdated articles still published

**Solutions**:

1. Implement content review schedule
2. Add "Last Updated" dates
3. Archive or update old content
4. Use content expiration dates
5. Assign content owners
6. Quarterly content audit

### Inconsistent Formatting

**Symptoms**: Articles with varying styles and structures

**Solutions**:

1. Create content style guide
2. Use consistent headings
3. Standardize image sizes
4. Use templates for common content types
5. Train content creators
6. Review before publishing

### Poor Content Organization

**Symptoms**: Difficult to find content, confusing categories

**Solutions**:

1. Restructure categories logically
2. Use consistent naming conventions
3. Implement tagging strategy
4. Create category landing pages
5. Use breadcrumbs
6. Add search functionality

### Content Migration Issues

**Symptoms**: Broken links, missing images after migration

**Solutions**:

1. Audit all content post-migration
2. Update internal links
3. Re-upload missing images
4. Fix absolute URLs
5. Update image paths
6. Test all menu links
7. Set up 301 redirects

## Content Management Best Practices

### Content Creation

* \[ ] Follow established style guide
* \[ ] Use proper heading hierarchy (H1, H2, H3)
* \[ ] Optimize images before upload
* \[ ] Add alt text to all images
* \[ ] Write unique meta descriptions
* \[ ] Include internal links
* \[ ] Proofread before publishing
* \[ ] Set appropriate categories/tags

### Content Maintenance

* \[ ] Review content quarterly
* \[ ] Update outdated information
* \[ ] Remove truly obsolete content
* \[ ] Fix broken links monthly
* \[ ] Archive old news/events
* \[ ] Consolidate duplicate content
* \[ ] Monitor for orphaned articles
* \[ ] Check for broken images

### Media Management

* \[ ] Organize media in folders
* \[ ] Use descriptive filenames
* \[ ] Optimize images before upload
* \[ ] Set maximum upload dimensions
* \[ ] Delete unused media quarterly
* \[ ] Archive old campaign assets
* \[ ] Use CDN for large media libraries
* \[ ] Implement media versioning

### Menu Management

* \[ ] Logical menu structure
* \[ ] Limit top-level items (5-7 max)
* \[ ] Consistent naming conventions
* \[ ] Mobile-friendly menus
* \[ ] Test all menu links monthly
* \[ ] Remove orphaned menu items
* \[ ] Use descriptive titles
* \[ ] Consider mega menus for large sites

### Quality Assurance

* \[ ] Content review before publishing
* \[ ] Test on multiple devices
* \[ ] Check broken links weekly
* \[ ] Verify images display correctly
* \[ ] Ensure proper formatting
* \[ ] Validate against style guide
* \[ ] Test user journeys
* \[ ] Monitor bounce rates

## Content Audit Process

### Monthly Tasks

1. Check for broken links
2. Review recent content performance
3. Update stale content
4. Clean up unused media (small batches)
5. Test menu navigation

### Quarterly Tasks

1. Full content inventory
2. Identify low-performing content
3. Update or remove outdated articles
4. Reorganize categories if needed
5. Comprehensive media audit
6. Review menu structure
7. Analyze content gaps

### Annual Tasks

1. Complete content strategy review
2. Major restructuring if needed
3. Archive old content
4. Update content templates
5. Review and update style guide
6. Content performance analysis
7. Plan content calendar for next year

## Content Performance Metrics

### Engagement Metrics

* **Page Views** - Traffic to each article
* **Time on Page** - How long users read
* **Bounce Rate** - Single-page sessions
* **Scroll Depth** - How far users read
* **Social Shares** - Content virality

### SEO Metrics

* **Organic Traffic** - Search engine visitors
* **Search Rankings** - Position in SERPs
* **Click-Through Rate** - From search results
* **Backlinks** - External links to content
* **Indexed Pages** - Pages in search index

### Conversion Metrics

* **Goal Completions** - Desired actions taken
* **Conversion Rate** - Visitors who convert
* **Lead Generation** - Form submissions
* **Newsletter Signups** - Email captures
* **Downloads** - Resource downloads

## Next Steps

* [SEO Checks](./seo.md) - Optimize content for search
* [Performance Checks](./performance.md) - Improve content delivery speed
* [Users Checks](./users.md) - Review content access permissions
