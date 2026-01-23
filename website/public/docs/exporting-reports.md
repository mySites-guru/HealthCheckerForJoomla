---
url: /docs/exporting-reports.md
---
# Exporting Reports

Health Checker allows you to export your check results in multiple formats for sharing with your team, hosting provider, or for record-keeping.

## Available Export Formats

### JSON Export

Structured data format ideal for:

* Programmatic analysis
* Integration with monitoring systems
* Version control tracking
* Automated processing

### HTML Export

Human-readable formatted report perfect for:

* Sharing with clients or team members
* Sending to your hosting provider
* Archiving for compliance
* Printing for offline review

## How to Export

### From the Results Page

1. Run a health check to generate results
2. Review the results (optional)
3. Click the export button in the toolbar:
   * **Export JSON**: Download `health-check-report-YYYY-MM-DD.json`
   * **Export HTML**: Download `health-check-report-YYYY-MM-DD.html`

### File Naming

Exported files are automatically named with the date:

* `health-check-report-2026-01-12.json`
* `health-check-report-2026-01-12.html`

This makes it easy to track reports over time.

## JSON Export Format

### Structure

```json
{
  "generated": "2026-01-12T15:30:00+00:00",
  "site": {
    "name": "My Joomla Site",
    "url": "https://example.com",
    "joomla_version": "5.2.0",
    "php_version": "8.2.14"
  },
  "summary": {
    "total": 133,
    "critical": 2,
    "warning": 15,
    "good": 116
  },
  "checks": [
    {
      "slug": "core.php_version",
      "title": "PHP Version Check",
      "category": "system",
      "provider": "core",
      "status": "good",
      "description": "PHP 8.2.14 meets Joomla 5 requirements"
    }
  ]
}
```

### Use Cases

**Tracking Changes Over Time**

```bash
# Save weekly snapshots
cp health-check-report-2026-01-12.json reports/week-02.json

# Compare with previous week
diff reports/week-01.json reports/week-02.json
```

**Automated Monitoring**

```bash
# Check for critical issues
jq '.summary.critical' health-check-report.json

# Extract only critical checks
jq '.checks[] | select(.status=="critical")' report.json
```

**Integration with CI/CD**

```bash
# Fail build if critical issues found
CRITICAL=$(jq '.summary.critical' report.json)
if [ "$CRITICAL" -gt 0 ]; then
  echo "Critical health issues found!"
  exit 1
fi
```

## HTML Export Format

### Features

The HTML export includes:

* **Styled report**: Matches the admin interface design
* **Complete results**: All checks with full descriptions
* **Summary statistics**: Count of critical/warning/good
* **Metadata**: Site info, Joomla version, generation date
* **Printable**: Optimized for printing on paper
* **Self-contained**: No external dependencies

### Opening HTML Reports

The HTML file can be:

* Opened directly in any web browser
* Shared via email (it's just one file)
* Printed to PDF for archiving
* Hosted on a web server for team access

### Example Use Cases

**Sharing with Hosting Provider**

```
Subject: Site Health Check Results - Action Needed

Hi Support Team,

I've run a health check on my Joomla site and found
some critical issues that may require server configuration
changes. Please see the attached HTML report.

The main concerns are:
- PHP memory limit too low (line 15)
- Missing required PHP extension (line 23)

Can you help resolve these?

Attached: health-check-report-2026-01-12.html
```

**Client Reporting**

```
Subject: Monthly Site Health Report - January 2026

Dear Client,

Please find attached this month's automated health
check report for your Joomla website.

Summary:
✓ 109 checks passed
⚠ 15 warnings (detailed in report)
✗ 2 critical issues (being addressed)

We're working on resolving the critical issues and
will update you by end of week.

Attached: health-check-report-2026-01-12.html
```

**Compliance Documentation**

```
# Create monthly archives
mkdir -p compliance/2026/01/
cp health-check-report-2026-01-12.html \
   compliance/2026/01/monthly-health-check.html
```

## Export Tips

### When to Export

Export reports:

* Before major updates or changes
* After fixing critical issues (to document resolution)
* Monthly for compliance/archiving
* When requesting support from hosting provider
* After site migrations

### Storage Recommendations

**For Version Control**

```bash
# Add to git (JSON format recommended)
git add reports/health-check-$(date +%Y-%m-%d).json
git commit -m "Health check: $(date +%Y-%m-%d)"
```

**For Long-Term Archives**

```bash
# Compress old reports
gzip reports/health-check-2025-*.{json,html}

# Organize by year/month
reports/
├── 2025/
│   ├── 01/
│   ├── 02/
│   └── ...
└── 2026/
    └── 01/
```

### Privacy Considerations

Health check reports may contain:

* Site URLs and paths
* PHP configuration details
* Database information (not passwords)
* Extension names and versions
* User account counts

**Before sharing externally:**

* Review the report for sensitive information
* Remove or redact anything confidential
* Share only with trusted parties
* Use secure channels (encrypted email, secure file sharing)

## Automated Export Workflows

While Health Checker doesn't include scheduling in the free version, you can create manual workflows:

### Weekly Review Process

1. **Monday Morning**: Run health check
2. **Export JSON**: Save to dated file
3. **Compare**: Check for new issues vs. last week
4. **Address**: Fix any new warnings/critical items
5. **Document**: Commit report to version control

### Pre-Deployment Checklist

1. Run health check on staging site
2. Export HTML report
3. Review and fix all critical issues
4. Re-run and export clean report
5. Deploy to production
6. Run health check on production
7. Compare staging vs. production reports

## Next Steps

* [Dashboard Widget](./dashboard-widget.md) - Monitor health at a glance
* [Understanding Checks](./understanding-checks.md) - Learn what each check does
* [Checks Reference](./checks/system.md) - Detailed check documentation
