---
url: /docs/checks/seo.md
---
# SEO Checks

SEO checks evaluate search engine optimization settings, URL configuration, and metadata. Proper SEO helps search engines index and rank your content effectively.

**Total checks in this category: 12**

## Social Media Sharing (2 checks)

### Facebook Open Graph Tags

Verifies essential Open Graph meta tags are present on your homepage.

* **Good**: All essential Open Graph tags found (og:title, og:description, og:image, og:url)
* **Warning**: Some or all Open Graph tags missing

**Why it matters**: Facebook uses Open Graph meta tags to create rich previews when your pages are shared. Without them, Facebook makes guesses that often result in poor previews with wrong images or truncated text. Proper Open Graph tags significantly increase click-through rates from Facebook shares.

**Required tags**:

* `og:title` - Share preview title
* `og:description` - Share preview description
* `og:image` - Preview image (ideally 1200x630px)
* `og:url` - Canonical URL

**Optional but recommended**:

* `fb:app_id` - For Facebook Insights
* `og:site_name` - Your site's name
* `og:type` - Content type (website, article, etc.)

**How to add**: Install a social meta extension or add the tags to your template's `<head>` section.

### X/Twitter Cards Tags

Verifies X (Twitter) Card meta tags are present for rich share previews.

* **Good**: All essential Twitter Card tags found (or Open Graph fallbacks)
* **Warning**: Missing Twitter Card tags

**Why it matters**: X uses Card meta tags to create rich previews when your pages are shared. Without them, posts linking to your site show plain text links instead of attractive cards with images and descriptions. Rich previews dramatically increase engagement and click-through rates.

**Required tags**:

* `twitter:card` - Card type (summary, summary\_large\_image)
* `twitter:title` - Post preview title
* `twitter:description` - Post preview description

**Recommended tags**:

* `twitter:image` - Preview image URL
* `twitter:site` - X username (e.g., @yoursite)

**Note**: X also falls back to Open Graph tags if Twitter-specific tags are missing.

**How to add**: Install a social meta extension or add the tags to your template.

## Meta Information (4 checks)

### Site Meta Description Set

Verifies global meta description is configured.

* **Good**: Unique, descriptive meta description (120-160 chars)
* **Warning**: Missing or default description

**Why it matters**: Meta descriptions appear in search results and influence click-through rates.

**How to set**: System → Global Configuration → Site → Site Meta Description

**Best practices**:

* 120-160 characters
* Include target keywords naturally
* Compelling call-to-action
* Unique for homepage
* Avoid keyword stuffing

### Default Meta Keywords

Checks if meta keywords are set.

* **Good**: Not set (keywords meta tag deprecated)
* **Warning**: Set but unnecessary

**Why it matters**: Google ignores meta keywords tag. It's obsolete and can leak competitive keyword strategy.

**How to handle**: Leave blank or remove existing keywords

### Site Name Configured

Verifies site name is set and descriptive.

* **Good**: Unique, descriptive site name
* **Warning**: Generic or default name

**Why it matters**: Site name appears in browser tabs, search results, and social shares.

**How to set**: System → Global Configuration → Site → Site Name

**Best practices**:

* Clear, memorable brand name
* Include key topic/industry if appropriate
* Keep under 60 characters
* Consistent across all platforms

### Robots Meta Tag

Checks robots meta tag configuration.

* **Good**: Not blocking search engines
* **Critical**: Blocking indexing in production

**Why it matters**: Incorrect robots tag prevents search engine indexing.

**Common mistakes**:

* `<meta name="robots" content="noindex">` in production
* Left over from staging/development
* Blocks entire site from Google

**How to check**: View page source, search for "robots" meta tag

## URL Structure (4 checks)

### SEF URLs Enabled

Verifies Search Engine Friendly URLs are active.

* **Good**: Enabled
* **Critical**: Disabled

**Why it matters**: SEF URLs are readable, shareable, and rank better in search engines.

**Example**:

* ❌ Bad: `/index.php?option=com_content&view=article&id=123`
* ✅ Good: `/blog/article-title`

**How to enable**: System → Global Configuration → Site → Search Engine Friendly URLs: Yes

### URL Rewriting Active

Checks if mod\_rewrite is working.

* **Good**: Active and functioning
* **Warning**: Not configured

**Why it matters**: Removes "index.php" from URLs for cleaner appearance.

**Example**:

* ⚠️ Warning: `/index.php/blog/article-title`
* ✅ Good: `/blog/article-title`

**How to enable**:

1. Rename `htaccess.txt` to `.htaccess`
2. Verify Apache mod\_rewrite enabled
3. System → Global Configuration → Site → Use URL Rewriting: Yes

### Canonical URLs Configured

Verifies canonical URL settings.

* **Good**: Properly configured
* **Warning**: Not set or misconfigured

**Why it matters**: Prevents duplicate content penalties from search engines.

**Common duplicate content issues**:

* HTTP vs HTTPS
* www vs non-www
* Trailing slash variations
* URL parameters

**How to configure**:

1. Force HTTPS in Global Configuration
2. Choose www or non-www (redirect other)
3. Use canonical tag in templates
4. Configure in `.htaccess`:
   ```apache
   RewriteCond %{HTTP_HOST} ^example\.com [NC]
   RewriteRule ^(.*)$ https://www.example.com/$1 [L,R=301]
   ```

### Sitemap Present (XML)

Checks if XML sitemap exists.

* **Good**: XML sitemap present and accessible
* **Warning**: Missing or inaccessible

**Why it matters**: Helps search engines discover and index all pages.

**How to create**:

1. Install sitemap extension (e.g., OSMap, JSitemap)
2. Generate sitemap
3. Submit to Google Search Console
4. Submit to Bing Webmaster Tools
5. Add to robots.txt:
   ```
   Sitemap: https://example.com/sitemap.xml
   ```

**Sitemap requirements**:

* XML format
* Under 50,000 URLs
* Under 50MB uncompressed
* Updated regularly (weekly minimum)

## Content SEO (2 checks)

### Images Missing Alt Text

Scans for images without alt attributes.

* **Good**: All images have descriptive alt text
* **Warning**: Images missing alt text

**Why it matters**: Alt text helps:

* Accessibility for screen readers
* Image search ranking
* Content understanding by search engines
* Displays when images fail to load

**How to add**: When inserting image → Image Description field

**Best practices**:

* Describe image content
* Include relevant keywords naturally
* Keep concise (125 chars or less)
* Don't start with "image of" or "picture of"

### Broken Internal Links

Detects non-working links within your site.

* **Good**: No broken internal links
* **Warning**: Broken links found

**Why it matters**: Broken links:

* Hurt user experience
* Waste search engine crawl budget
* Indicate poor site maintenance
* May indicate content removal issues

**How to fix**:

1. Use link checker tool
2. Update or remove broken links
3. Set up 301 redirects for moved content
4. Fix menu items pointing to unpublished content

## Technical SEO (4 checks)

### robots.txt File

Checks if robots.txt exists and is properly configured.

* **Good**: robots.txt exists and allows indexing
* **Warning**: robots.txt missing or blocking content
* **Critical**: Blocking all search engines

**Why it matters**: robots.txt controls which pages search engines can crawl.

**How to check**: Visit yoursite.com/robots.txt

**Best practices**:

* Don't block CSS/JS files
* Include sitemap location
* Don't use robots.txt for sensitive content (use noindex)

### Structured Data (Schema.org)

Checks for structured data markup.

* **Good**: Schema.org markup present
* **Warning**: No structured data detected

**Why it matters**: Structured data helps search engines understand your content and can enable rich snippets in search results.

**How to add**: Use Joomla extensions that support Schema.org markup or add JSON-LD manually to templates.

### Open Graph Plugin

Detects if an Open Graph plugin is installed.

* **Good**: Open Graph plugin installed and enabled
* **Warning**: No Open Graph plugin detected

**Why it matters**: Open Graph plugins automatically add social sharing meta tags to your pages.

**Recommended plugins**: Install a social meta or SEO extension that provides Open Graph support.

### Articles Missing Meta Description

Identifies articles without unique descriptions.

* **Good**: All articles have unique meta descriptions
* **Warning**: Some articles missing descriptions

**Why it matters**: Unique meta descriptions improve search result click-through rates.

**How to add**: Articles → Edit → Publishing tab → Meta Description

**Best practices**:

* Unique for each article
* 120-160 characters
* Include target keyword
* Compelling and accurate
* Match article content

## Common SEO Issues & Solutions

### Poor Search Rankings

**Symptoms**: Site not appearing in Google results

**Diagnostics**:

1. Check Google Search Console for issues
2. Verify robots.txt not blocking
3. Check for manual penalties
4. Review indexing status
5. Analyze competitor sites

**Solutions**:

1. Create quality, original content
2. Build quality backlinks
3. Optimize technical SEO
4. Improve site speed
5. Ensure mobile-friendliness
6. Fix crawl errors
7. Submit sitemap to Google

### Duplicate Content

**Symptoms**: Multiple URLs with same content

**Common causes**:

* HTTP vs HTTPS
* www vs non-www
* Print URLs
* Session IDs in URLs
* Pagination issues

**Solutions**:

1. Set up 301 redirects
2. Use canonical tags
3. Configure preferred domain
4. Use parameter handling in GSC
5. Implement HREFLANG for multi-language
6. Fix URL structure

### Low Click-Through Rates

**Symptoms**: Good rankings but few clicks

**Solutions**:

1. Write compelling title tags
2. Optimize meta descriptions
3. Use schema markup
4. Earn rich snippets
5. Match search intent
6. Update outdated publish dates
7. Add FAQ schema

### Mobile SEO Issues

**Symptoms**: Poor mobile rankings

**Solutions**:

1. Use responsive design
2. Optimize page speed
3. Ensure touch targets are large enough
4. Avoid pop-ups on mobile
5. Test with Mobile-Friendly Test
6. Implement AMP (if appropriate)
7. Optimize for Core Web Vitals

## SEO Best Practices

### On-Page SEO

* \[ ] Unique, descriptive title tags (50-60 chars)
* \[ ] Unique meta descriptions (120-160 chars)
* \[ ] Proper heading hierarchy (H1, H2, H3)
* \[ ] Keyword in first paragraph
* \[ ] Internal linking to related content
* \[ ] Alt text on all images
* \[ ] Clean, descriptive URLs
* \[ ] Schema markup where appropriate

### Technical SEO

* \[ ] XML sitemap submitted
* \[ ] Robots.txt configured
* \[ ] HTTPS enabled
* \[ ] Mobile-friendly design
* \[ ] Fast page load times
* \[ ] Fix broken links
* \[ ] Canonical tags set
* \[ ] Proper 301 redirects

### Content SEO

* \[ ] High-quality, original content
* \[ ] Regular content updates
* \[ ] Proper keyword targeting
* \[ ] Comprehensive coverage of topics
* \[ ] Multimedia content (images, video)
* \[ ] Content length appropriate for topic
* \[ ] Clear content structure
* \[ ] Engaging, readable writing

### Local SEO (if applicable)

* \[ ] Google Business Profile claimed
* \[ ] NAP (Name, Address, Phone) consistent
* \[ ] Local schema markup
* \[ ] Location pages for multiple locations
* \[ ] Local citations
* \[ ] Reviews management
* \[ ] Local keywords in content

## SEO Tools & Resources

### Google Tools

* **Google Search Console** - Monitor search performance
* **Google Analytics** - Track traffic and behavior
* **PageSpeed Insights** - Analyze performance
* **Mobile-Friendly Test** - Check mobile compatibility
* **Rich Results Test** - Validate schema markup

### Third-Party Tools

* **Ahrefs** - Backlink analysis, keyword research
* **SEMrush** - Comprehensive SEO platform
* **Moz** - SEO metrics and tools
* **Screaming Frog** - Site crawler
* **GTmetrix** - Performance analysis

### Joomla SEO Extensions

* **SH404SEF** - Advanced SEO management
* **OSMap** - XML sitemap generation
* **JSitemap** - Sitemap with Google integration
* **JCH Optimize** - Performance optimization
* **EasyBlog** - SEO-friendly blogging

## Next Steps

* [Content Quality Checks](./content.md) - Improve content quality
* [Performance Checks](./performance.md) - Optimize page speed
* [Security Checks](./security.md) - Secure your site for better trust signals
