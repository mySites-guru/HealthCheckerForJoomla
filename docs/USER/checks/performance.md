# Performance Checks

Performance checks analyze caching configuration, optimization settings, and resource usage. These checks help improve page load times and server efficiency.

**Total checks in this category: 10**

## Caching (5 checks)

### System Cache Enabled
Verifies system cache is active.

- **Good**: Enabled
- **Warning**: Disabled

**Why it matters**: System cache stores compiled configuration, significantly reducing load times.

**How to enable**: System → Global Configuration → System → Cache: ON (Conservative or Progressive)

**Impact**: Can reduce page generation time by 30-50%

### Cache Handler Type
Checks which cache mechanism is used.

- **Good**: File, Redis, or Memcached
- **Warning**: Database caching

**Why it matters**: Database caching is slowest option.

**Recommended handlers**:
1. **Redis** - Best for high traffic (requires Redis server)
2. **Memcached** - Excellent for distributed setups
3. **File** - Good default for most sites
4. **Database** - Slowest, avoid if possible

**How to change**: System → Global Configuration → System → Cache Handler

### Page Cache for Guests
Checks if page caching is enabled for non-logged-in users.

- **Good**: Enabled
- **Warning**: Disabled

**Why it matters**: Page caching serves pre-rendered HTML, drastically reducing server load.

**How to enable**: System → Plugins → System - Page Cache → Enable

**Note**: Only works for guest (non-logged-in) users

### Cache Lifetime Setting
Verifies cache TTL is reasonable.

- **Good**: 15-60 minutes
- **Warning**: Below 15 minutes or above 60 minutes

**Why it matters**:
- Too short: Frequent cache regeneration
- Too long: Stale content displayed

**How to configure**: System → Global Configuration → System → Cache Time

### Cache Directory Writable
Ensures cache directory has proper permissions.

- **Good**: Writable
- **Critical**: Not writable

**Why it matters**: Unwritable cache directory prevents caching from working.

**How to fix**:
```bash
chmod 755 cache/
chown www-data:www-data cache/
```

## Compression & Optimization (4 checks)

### Gzip Compression Enabled
Checks if HTTP compression is active.

- **Good**: Enabled
- **Warning**: Disabled

**Why it matters**: Reduces transferred data by 60-80%, dramatically improving load times.

**How to enable**:
- Joomla: System → Global Configuration → Server → Gzip Page Compression: Yes
- Server level (Apache `.htaccess`):
  ```apache
  <IfModule mod_deflate.c>
      AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript
  </IfModule>
  ```

### CSS/JS Minification
Verifies asset optimization.

- **Good**: Minification enabled
- **Warning**: Disabled

**Why it matters**: Reduces file sizes and HTTP requests.

**How to enable**:
- Install optimization extension (e.g., JCH Optimize)
- Use template's built-in optimization
- Configure CDN with automatic minification

**Impact**: Can reduce CSS/JS size by 50-70%

### Image Optimization
Checks if images are optimized.

- **Good**: Images optimized
- **Warning**: Unoptimized images detected

**Why it matters**: Images typically account for 50-90% of page weight.

**How to optimize**:
1. Use modern formats (WebP, AVIF)
2. Compress before upload (TinyPNG, ImageOptim)
3. Use responsive images (`<picture>` element)
4. Install image optimization extension
5. Configure lazy loading

### Lazy Loading Enabled
Checks if lazy loading is active for images.

- **Good**: Enabled
- **Warning**: Disabled

**Why it matters**: Defers loading off-screen images, improving initial page load.

**How to enable**:
- Joomla 5: Built-in, enable in article options
- Extensions: Install lazy load plugin
- Template: Use template's lazy load feature

**Impact**: Can improve Largest Contentful Paint by 20-40%

### Media Manager Thumbnails
Checks if thumbnail generation is enabled for the Media Manager.

- **Good**: Thumbnail generation enabled (shows thumbnail size)
- **Warning**: Thumbnail generation disabled

**Why it matters**: By default, the Joomla Media Manager loads full-size images when browsing, which can be extremely slow for sites with large media libraries or high-resolution images. Enabling thumbnail generation creates smaller preview images that load much faster, dramatically improving the Media Manager user experience.

**How to enable**:
1. Go to System → Plugins
2. Find "Filesystem - Local" plugin
3. Set "Generate Thumbnails" to Yes
4. Configure the thumbnail size (e.g., 200px)

**Impact**: Significantly faster Media Manager browsing, especially for sites with many images.

## Joomla Performance Settings (4 checks)

### Debug Plugin Disabled
Verifies debug plugin is off in production.

- **Good**: Disabled
- **Critical**: Enabled in production

**Why it matters**: Debug plugin adds significant overhead and exposes sensitive information.

**How to disable**: System → Plugins → System - Debug → Disable

### Debug Language Disabled
Checks if language debugging is off.

- **Good**: Disabled
- **Warning**: Enabled

**Why it matters**: Language debugging shows translation keys and adds processing overhead.

**How to disable**: System → Global Configuration → System → Debug Language: No

### SEF URLs Enabled
Verifies search engine friendly URLs are active.

- **Good**: Enabled
- **Warning**: Disabled

**Why it matters**: While primarily an SEO feature, SEF URLs also improve caching efficiency.

**How to enable**: System → Global Configuration → Site → Search Engine Friendly URLs: Yes

### URL Rewriting Working
Checks if Apache/Nginx rewriting is configured.

- **Good**: Working correctly
- **Warning**: Not configured

**Why it matters**: Removes "index.php" from URLs, improving caching and SEO.

**How to enable**:
1. Rename `htaccess.txt` to `.htaccess` (Apache)
2. Configure nginx rewrite rules (Nginx)
3. System → Global Configuration → Site → Use URL Rewriting: Yes

## Server Performance (3 checks)

### PHP OPcache Configured
Verifies PHP opcache is enabled and tuned.

- **Good**: Enabled with adequate settings
- **Warning**: Disabled or poorly configured

**Why it matters**: OPcache caches compiled PHP code, dramatically improving execution speed.

**Recommended settings** (`php.ini`):
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

**Impact**: Can improve PHP execution by 2-3x

### Admin Response Time
Monitors backend response time.

- **Good**: Under 500ms
- **Warning**: 500ms - 2s
- **Critical**: Over 2s

**Why it matters**: Slow admin indicates server or configuration issues.

**Common causes**:
- Insufficient resources
- Slow database queries
- Too many plugins
- Poor hosting

### Peak Memory Usage
Checks PHP memory consumption.

- **Good**: Under 80% of memory limit
- **Warning**: 80-95% of limit
- **Critical**: Over 95% of limit

**Why it matters**: High memory usage causes crashes and errors.

**How to reduce**:
1. Increase PHP memory limit
2. Optimize database queries
3. Remove unnecessary plugins
4. Enable object caching

## Common Performance Issues & Solutions

### Slow Page Load Times

**Symptoms**: Pages take 3+ seconds to load

**Solutions**:
1. Enable page caching (biggest impact)
2. Enable Gzip compression
3. Optimize images
4. Enable lazy loading
5. Minify CSS/JS
6. Use CDN
7. Upgrade hosting
8. Optimize database
9. Reduce HTTP requests
10. Enable browser caching

**Quick wins** (do first):
- Enable system cache
- Enable page cache
- Enable Gzip compression
- Optimize images

### Cache Not Working

**Symptoms**: No performance improvement after enabling cache

**Diagnostics**:
1. Check cache directory writable
2. Verify plugin order
3. Check for cache-busting headers
4. Review logged-in user detection
5. Check for dynamic content

**Solutions**:
1. Fix file permissions
2. Clear cache and test
3. Check conflicting plugins
4. Review cache configuration
5. Test with different cache handler

### High Memory Usage

**Symptoms**: "Allowed memory size exhausted" errors

**Solutions**:
1. Increase PHP memory limit to 256MB+
2. Disable unnecessary plugins
3. Optimize images before upload
4. Review extension resource usage
5. Upgrade hosting plan
6. Enable OPcache
7. Use external caching (Redis/Memcached)

### Database Bottlenecks

**Symptoms**: Slow queries, timeouts

**Solutions**:
1. Optimize tables regularly
2. Add missing indexes
3. Clean up old data
4. Use external caching
5. Enable query cache
6. Increase database resources
7. Consider read replicas

## Performance Optimization Checklist

### Immediate Wins
- [ ] Enable system cache (Conservative or Progressive)
- [ ] Enable Gzip compression
- [ ] Disable debug mode and plugins
- [ ] Enable PHP OPcache
- [ ] Optimize and compress images

### Caching Strategy
- [ ] Enable page cache for guests
- [ ] Set appropriate cache lifetime (15-30 min)
- [ ] Use Redis or Memcached if available
- [ ] Configure browser caching
- [ ] Enable CDN if high traffic

### Asset Optimization
- [ ] Minify CSS and JavaScript
- [ ] Combine files where possible
- [ ] Use modern image formats (WebP)
- [ ] Enable lazy loading
- [ ] Optimize fonts (WOFF2, subset)
- [ ] Reduce HTTP requests

### Database Optimization
- [ ] Optimize tables monthly
- [ ] Add indexes for slow queries
- [ ] Clean up expired sessions
- [ ] Archive old content
- [ ] Enable query cache

### Server Configuration
- [ ] PHP 8.1+ with opcache
- [ ] Adequate memory (256MB+)
- [ ] SSD storage
- [ ] HTTP/2 or HTTP/3
- [ ] Modern web server (Nginx, LiteSpeed)

### Monitoring
- [ ] Track Core Web Vitals
- [ ] Monitor server resources
- [ ] Check slow queries
- [ ] Review error logs
- [ ] Test with tools (GTmetrix, PageSpeed Insights)

## Performance Testing Tools

### Online Tools
- **Google PageSpeed Insights** - Core Web Vitals
- **GTmetrix** - Detailed performance analysis
- **WebPageTest** - Advanced testing options
- **Pingdom** - Speed monitoring
- **Chrome DevTools** - In-browser analysis

### What to Monitor
- **LCP** (Largest Contentful Paint) - Under 2.5s
- **FID** (First Input Delay) - Under 100ms
- **CLS** (Cumulative Layout Shift) - Under 0.1
- **TTFB** (Time to First Byte) - Under 600ms
- **Total Page Size** - Under 2MB
- **HTTP Requests** - Under 50

## Next Steps

- [System Checks](./system.md) - Optimize server configuration
- [Database Checks](./database.md) - Tune database performance
- [SEO Checks](./seo.md) - Improve search engine performance
