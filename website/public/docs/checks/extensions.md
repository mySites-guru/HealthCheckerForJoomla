---
url: /docs/checks/extensions.md
---
# Extensions Checks

Extension checks review installed components, modules, plugins, and templates. Outdated or incompatible extensions are a common source of security vulnerabilities and compatibility issues.

**Total checks in this category: 13**

## Core Updates (3 checks)

### Joomla Core Version

Checks if Joomla is up to date.

* **Good**: Latest stable version
* **Warning**: Minor version behind
* **Critical**: Major version behind or unsupported version

**Why it matters**: Updates contain security patches, bug fixes, and new features.

**How to update**: Components → Joomla Update

### Update Server Accessible

Verifies connection to Joomla update servers.

* **Good**: Can reach update servers
* **Critical**: Cannot connect

**Why it matters**: Without update server access, you won't receive security updates.

**Common causes**:

* Firewall blocking outbound connections
* DNS resolution issues
* Server-side outbound restrictions
* SSL certificate problems

### Update Channel

Checks which update channel is configured.

* **Good**: Stable channel
* **Warning**: Testing or custom channel

**Why it matters**: Non-stable channels may contain untested code.

**How to check**: Components → Joomla Update → Options → Update Channel

## Extension Health (5 checks)

### Extensions with Updates

Identifies outdated extensions.

* **Good**: All extensions current
* **Warning**: 1-5 updates available
* **Critical**: More than 5 updates or security updates available

**Why it matters**: Updates fix security vulnerabilities and bugs.

**How to update**: System → Update → Extensions

**Priority**: Security updates > major updates > minor updates

### Disabled Extensions Present

Checks for disabled but installed extensions.

* **Good**: All extensions enabled or few disabled
* **Warning**: Multiple disabled extensions

**Why it matters**: Disabled extensions still consume resources and may contain vulnerabilities.

**How to handle**:

1. Review disabled extensions
2. Re-enable if needed
3. Uninstall if not needed
4. Don't leave disabled indefinitely

### Missing Extension Files

Detects extensions with missing files.

* **Good**: All extension files present
* **Critical**: Missing files detected

**Why it matters**: Missing files cause errors and indicate:

* Incomplete installation
* File deletion (manual or attack)
* File permission issues
* Corrupted installation

**How to fix**:

1. Reinstall the extension
2. Restore from backup if reinstall fails
3. Check file permissions

### Extension Update Sites Valid

Verifies update URLs are accessible.

* **Good**: All update sites reachable
* **Warning**: Some update sites unreachable

**Why it matters**: Broken update sites prevent receiving updates.

**Common causes**:

* Developer discontinued extension
* Update server moved/changed
* Extension company out of business
* Domain expired

**How to fix**:

1. Find new update URL from developer
2. System → Update → Update Sites
3. Edit broken URLs
4. Disable if no longer maintained

### Unsigned Extensions

Identifies extensions without code signing.

* **Good**: All extensions signed
* **Warning**: Unsigned extensions found

**Why it matters**: Code signing verifies extension authenticity and integrity.

**How to handle**: Only install extensions from trusted sources

## Extension Compatibility (4 checks)

### PHP Version Compatibility

Checks extension compatibility with current PHP.

* **Good**: All extensions compatible
* **Warning**: Some extensions may have issues
* **Critical**: Extensions incompatible with current PHP

**Why it matters**: Incompatible extensions cause errors or break your site.

**How to check**:

1. Review extension documentation
2. Test on staging site first
3. Contact developers for compatibility confirmation

### Joomla Version Compatibility

Verifies extensions support current Joomla version.

* **Good**: All extensions compatible
* **Warning**: Some extensions uncertain
* **Critical**: Extensions incompatible

**Why it matters**: Using incompatible extensions risks crashes and data loss.

**Before upgrading Joomla**:

1. Check all extension compatibility
2. Update incompatible extensions first
3. Test on staging site
4. Have rollback plan

### Deprecated API Usage

Scans for extensions using deprecated Joomla APIs.

* **Good**: No deprecated API usage
* **Warning**: Some deprecated APIs in use

**Why it matters**: Deprecated APIs will be removed in future Joomla versions.

**How to address**:

1. Contact extension developers
2. Check for updated versions
3. Plan replacement if not updated
4. Test alternatives

### Known Vulnerable Extensions

Checks against database of vulnerable extensions.

* **Good**: No known vulnerabilities
* **Critical**: Known vulnerabilities found

**Why it matters**: Vulnerable extensions are actively exploited by attackers.

**Immediate action**:

1. Update immediately
2. Disable if no update available
3. Check for signs of compromise
4. Find alternative if discontinued

## Plugin Status (3 checks)

### System Plugins Order

Verifies critical plugins load in correct order.

* **Good**: Proper plugin ordering
* **Warning**: Potential ordering issues

**Why it matters**: Plugin execution order affects functionality.

**Common ordering**:

1. System - Log
2. System - Debug (if enabled)
3. System - Cache
4. System - Language Filter
5. Custom system plugins

**How to fix**: System → Plugins → Filter: System → Reorder

### Conflicting Plugins

Identifies plugins that may conflict.

* **Good**: No known conflicts
* **Warning**: Potential conflicts detected

**Why it matters**: Conflicting plugins cause errors or broken functionality.

**Common conflicts**:

* Multiple SEF URL plugins
* Multiple cache plugins
* Duplicate functionality plugins

**How to resolve**:

1. Identify which plugin you need
2. Disable or uninstall duplicates
3. Test functionality after changes

### Orphaned Plugin Files

Detects plugin files without database entries.

* **Good**: No orphaned files
* **Warning**: Orphaned files found

**Why it matters**: Orphaned files indicate incomplete uninstallation.

**How to handle**:

1. Identify plugin from file structure
2. Verify truly orphaned (not just disabled)
3. Manually delete files
4. Clear cache after deletion

## Template Overrides (2 checks)

### Overrides Needing Review

Identifies overrides for files changed in core.

* **Good**: All overrides current
* **Warning**: Some overrides may need updates

**Why it matters**: Outdated overrides miss bug fixes and new features.

**How to check**: System → Templates → Select template → Overrides

**When to update**:

* After Joomla major updates
* If override file has core changes
* If functionality breaks

### Outdated Override Files

Checks override modification dates vs core files.

* **Good**: Overrides newer than core
* **Warning**: Core files newer than overrides

**Why it matters**: Outdated overrides can break functionality.

**How to fix**:

1. Compare override with current core file
2. Merge changes if needed
3. Test thoroughly
4. Delete override if no longer needed

## Common Issues & Solutions

### Extension Update Failures

**Symptoms**: Updates fail with errors

**Solutions**:

1. Increase PHP memory limit
2. Increase max execution time
3. Check file permissions
4. Verify disk space
5. Try manual update via FTP
6. Check error logs

### Incompatibility After Update

**Symptoms**: Site broken after Joomla/extension update

**Solutions**:

1. Check System → System Information → PHP → Errors
2. Review error logs
3. Disable suspect extensions one by one
4. Restore from backup if necessary
5. Contact extension developers

### Orphaned Extensions

**Symptoms**: Extensions in database but files missing

**Solutions**:

1. Try reinstalling extension
2. If extension discontinued, find alternative
3. Uninstall via Joomla admin
4. Manually remove database entries if needed:
   ```sql
   DELETE FROM #__extensions WHERE extension_id = XXX;
   ```

### Too Many Extensions

**Symptoms**: Site slow, frequent conflicts

**Solutions**:

1. Audit all installed extensions
2. Uninstall unused extensions
3. Consolidate similar functionality
4. Prefer core features over extensions
5. Regular cleanup (quarterly)

## Extension Management Best Practices

### Installation

* Only install from trusted sources
* Check compatibility first
* Test on staging site
* Read documentation
* Review permissions before installing
* Keep installation files for rollback

### Updates

* Review changelog before updating
* Test on staging first
* Backup before major updates
* Update regularly (weekly)
* Subscribe to security announcements
* Prioritize security updates

### Maintenance

* Quarterly extension audit
* Remove unused extensions completely
* Monitor for abandonment
* Check developer reputation
* Document why each extension is needed
* Keep extension inventory

### Security

* Only use extensions from JED or trusted developers
* Check for code signing
* Review permissions granted
* Monitor security advisories
* Have replacement options for critical extensions
* Verify developer contact info

### Performance

* Minimize number of plugins
* Disable unused plugins
* Use caching wisely
* Monitor load impact
* Replace heavy extensions with lighter alternatives
* Optimize plugin order

## Next Steps

* [Performance Checks](./performance.md) - Optimize extension performance
* [Security Checks](./security.md) - Review extension security
* [System Checks](./system.md) - Ensure compatibility
