<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Plugin\AkeebaAdminTools\Extension;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;

\defined('_JEXEC') || die;

/**
 * Akeeba Admin Tools Health Checker Plugin
 *
 * This plugin provides comprehensive health checks for the Akeeba Admin Tools security component.
 * It integrates with the Health Checker component through event-driven auto-discovery, registering
 * 15 security-focused health checks that monitor WAF status, file integrity scans, security events,
 * IP bans, and attack blocking.
 *
 * INTEGRATION:
 * - Subscribes to Health Checker events via SubscriberInterface
 * - Registers provider metadata for attribution in reports
 * - Creates custom "Akeeba Admin Tools" category for checks
 * - Provides 15 anonymous check classes that monitor Admin Tools features
 *
 * EVENT FLOW:
 * 1. onHealthCheckerCollectProviders - Registers provider metadata with logo
 * 2. onHealthCheckerCollectCategories - Creates custom category
 * 3. onHealthCheckerCollectChecks - Registers all 15 security checks
 *
 * CHECKS PROVIDED:
 * - Installation status
 * - WAF configuration and rules
 * - Security events and blocked attacks
 * - IP bans and whitelists
 * - File integrity scan status
 * - Login failures and geoblocking
 * - SQL injection and XSS blocking
 *
 * @subpackage  HealthChecker.AkeebaAdminTools
 * @since       1.0.0
 */
final class AkeebaAdminToolsPlugin extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Plugin parameters.
     *
     * @var mixed
     * @since 1.0.0
     */
    public $params;

    /**
     * Automatically load plugin language files.
     *
     * @since 1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * This plugin subscribes to three Health Checker events:
     * - onHealthCheckerCollectProviders: Registers provider metadata
     * - onHealthCheckerCollectCategories: Creates custom category
     * - onHealthCheckerCollectChecks: Registers all health checks
     *
     * @return array<string, string> Event names mapped to method names
     * @since 1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HealthCheckerEvents::COLLECT_CATEGORIES->value => HealthCheckerEvents::COLLECT_CATEGORIES->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_CHECKS->value => HealthCheckerEvents::COLLECT_CHECKS->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_PROVIDERS->value => HealthCheckerEvents::COLLECT_PROVIDERS->getHandlerMethod(),
        ];
    }

    /**
     * Register the Akeeba Admin Tools category.
     *
     * Creates a custom category specifically for Akeeba Admin Tools checks, displayed
     * with the shield icon and custom logo. This category appears in the Health Checker
     * UI alongside core categories.
     *
     * @param CollectCategoriesEvent $collectCategoriesEvent The event object for collecting categories
     *
     * @since 1.0.0
     */
    public function onCollectCategories(CollectCategoriesEvent $collectCategoriesEvent): void
    {
        $collectCategoriesEvent->addResult(new HealthCategory(
            slug: 'akeeba_admintools',
            label: 'PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CATEGORY',
            icon: 'fa-shield-alt',
            sortOrder: 86,
            logoUrl: '/media/plg_healthchecker_akeebaadmintools/logo.png',
        ));
    }

    /**
     * Register provider metadata for Akeeba Admin Tools.
     *
     * Registers this plugin as a health check provider with attribution metadata.
     * This information is displayed in reports to show which checks come from
     * third-party integrations. Note that this is an unofficial integration
     * provided as an example of the Health Checker extensibility.
     *
     * @param CollectProvidersEvent $collectProvidersEvent The event object for collecting provider metadata
     *
     * @since 1.0.0
     */
    public function onCollectProviders(CollectProvidersEvent $collectProvidersEvent): void
    {
        $collectProvidersEvent->addResult(new ProviderMetadata(
            slug: 'akeeba_admintools',
            name: 'Akeeba Admin Tools (Unofficial)',
            description: 'Checks provided unofficially for this plugin as an example of 3rd party integration',
            url: 'https://www.akeeba.com',
            logoUrl: '/media/plg_healthchecker_akeebaadmintools/logo.png',
        ));
    }

    /**
     * Register all Akeeba Admin Tools health checks.
     *
     * This method registers 15 anonymous health check classes that monitor various
     * aspects of Akeeba Admin Tools functionality. Each check is implemented as an
     * anonymous class extending AbstractHealthCheck for auto-discovery support.
     *
     * All checks verify Admin Tools installation by checking for required database tables
     * before performing their specific checks. If tables are missing, checks return
     * warning status.
     *
     * CHECKS REGISTERED:
     * 1. installed - Verifies Admin Tools is installed
     * 2. waf_enabled - Checks if WAF rules are active
     * 3. security_events - Counts security events (last 7 days)
     * 4. blocked_attacks - Counts blocked attacks (last 24 hours)
     * 5. active_bans - Lists active IP bans
     * 6. scan_age - Checks file integrity scan recency
     * 7. file_alerts - Monitors unacknowledged file alerts
     * 8. temp_superusers - Checks for expired temporary super users
     * 9. ip_whitelist - Counts whitelisted IPs
     * 10. waf_rules - Summarizes WAF rule configuration
     * 11. login_failures - Tracks login failures (last 24 hours)
     * 12. geoblocking - Counts geoblocking events (last 7 days)
     * 13. sqli_blocks - Tracks SQL injection blocks (last 7 days)
     * 14. xss_blocks - Tracks XSS attempt blocks (last 7 days)
     * 15. admin_access - Monitors admin directory access (last 7 days)
     *
     * @param CollectChecksEvent $collectChecksEvent The event object for collecting health checks
     *
     * @since 1.0.0
     */
    public function onCollectChecks(CollectChecksEvent $collectChecksEvent): void
    {
        $database = $this->getDatabase();

        // Collect all checks first, then filter based on configuration
        $allChecks = [];

        // 1. Check if Admin Tools is installed
        /**
         * Admin Tools Installation Check
         *
         * Verifies that Akeeba Admin Tools is properly installed by checking for
         * the presence of its core database table.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Without Admin Tools installed, all other security checks in this plugin
         * will fail. This check serves as a prerequisite for all other Admin Tools
         * monitoring and alerts users if the component is missing.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Admin Tools is installed and database tables are present.
         *
         * WARNING: Admin Tools is not installed (no database tables found).
         *
         * CRITICAL: This check does not return critical status.
         */
        $installedCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug in format: akeeba_admintools.installed
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.installed';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_INSTALLED_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the installation check.
             *
             * Queries the database for the admintools_log table to verify installation.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                return $this->good('Akeeba Admin Tools is installed.');
            }
        };
        $installedCheck->setDatabase($database);

        $allChecks[] = $installedCheck;

        // 2. WAF Enabled check
        /**
         * Web Application Firewall (WAF) Status Check
         *
         * Verifies that the Admin Tools WAF has active protection rules enabled.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * The WAF is Admin Tools' primary defense mechanism against web-based attacks.
         * Having active WAF rules is essential for protecting against SQL injection,
         * XSS, file inclusion attacks, and other common web vulnerabilities. Without
         * active rules, the site is vulnerable to automated attacks.
         *
         * RESULT MEANINGS:
         *
         * GOOD: One or more WAF rules are currently active and protecting the site.
         *
         * WARNING: Either Admin Tools is not installed, or no WAF rules are enabled,
         *          leaving the site unprotected. Enable WAF rules in Admin Tools configuration.
         *
         * CRITICAL: This check does not return critical status.
         */
        $wafEnabledCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.waf_enabled';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_WAF_ENABLED_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the WAF enabled check.
             *
             * Counts the number of enabled WAF blacklist rules.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_wafblacklists'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_wafblacklists'))
                    ->where($database->quoteName('enabled') . ' = 1');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                if ($count === 0) {
                    return $this->warning('No WAF rules are enabled.');
                }

                return $this->good(sprintf('%d WAF rules are active.', $count));
            }
        };
        $wafEnabledCheck->setDatabase($database);

        $allChecks[] = $wafEnabledCheck;

        // 3. Security events (last 7 days)
        /**
         * Security Events Monitoring Check
         *
         * Counts all security events logged by Admin Tools in the last 7 days.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Security events indicate attempted attacks, suspicious activity, and WAF
         * interventions. Monitoring the frequency of security events helps identify
         * attack patterns, verify WAF effectiveness, and detect potential security
         * issues before they escalate. A sudden spike may indicate targeted attacks.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of security events (including zero).
         *       This is always good status - the count itself is for awareness only.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $securityEventsCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.security_events';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_SECURITY_EVENTS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the security events check.
             *
             * Counts log entries from the last 7 days.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_log'))
                    ->where($database->quoteName('logdate') . ' >= DATE_SUB(NOW(), INTERVAL 7 DAY)');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d security events in the last 7 days.', $count));
            }
        };
        $securityEventsCheck->setDatabase($database);

        $allChecks[] = $securityEventsCheck;

        // 4. Blocked attacks (last 24 hours)
        /**
         * Recent Blocked Attacks Check
         *
         * Counts attacks blocked by Admin Tools in the last 24 hours.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Tracking recent blocked attacks provides real-time visibility into active
         * threats against your site. A high number of recent blocks may indicate
         * an ongoing attack campaign that requires immediate attention or additional
         * security measures like IP blocking or geoblocking.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of blocked attacks in last 24 hours.
         *       Always good status - used for monitoring only.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $blockedAttacksCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.blocked_attacks';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_BLOCKED_ATTACKS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the blocked attacks check.
             *
             * Counts security log entries from the last 24 hours.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_log'))
                    ->where($database->quoteName('logdate') . ' >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d attacks blocked in the last 24 hours.', $count));
            }
        };
        $blockedAttacksCheck->setDatabase($database);

        $allChecks[] = $blockedAttacksCheck;

        // 5. Active IP bans
        /**
         * Active IP Bans Check
         *
         * Counts currently active automatic IP bans enforced by Admin Tools.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * IP bans are Admin Tools' way of blocking repeat attackers at the network
         * level. Monitoring active bans helps track persistent threats and ensures
         * the automatic ban system is functioning. A growing number of bans indicates
         * active attacks, while too many permanent bans may affect performance.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of active IP bans (including zero).
         *       Always good status - used for monitoring purposes.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $activeBansCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.active_bans';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_ACTIVE_BANS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the active bans check.
             *
             * Counts IP bans where expiration is in the future.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_ipautoban'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_ipautoban'))
                    ->where($database->quoteName('until') . ' > NOW()');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d active IP bans.', $count));
            }
        };
        $activeBansCheck->setDatabase($database);

        $allChecks[] = $activeBansCheck;

        // 6. File integrity scan age
        /**
         * File Integrity Scan Age Check
         *
         * Monitors how recently a file integrity scan was completed.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * File integrity scanning detects unauthorized modifications, backdoors, and
         * malware by comparing current files against known-good baselines. Regular
         * scans are essential for detecting compromises early. Sites should run scans
         * weekly at minimum, and more frequently for high-security environments.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Last file integrity scan was within the last 7 days.
         *
         * WARNING: Last scan was 8-30 days ago. Schedule a scan soon to maintain
         *          security monitoring.
         *
         * CRITICAL: Either no scans have ever completed, or the last scan was over
         *           30 days ago. Run a file integrity scan immediately.
         */
        $scanAgeCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.scan_age';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_SCAN_AGE_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the scan age check.
             *
             * Finds the most recent completed scan and calculates days since completion.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_scans'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select($database->quoteName('scanstart'))
                    ->from($database->quoteName('#__admintools_scans'))
                    ->where($database->quoteName('status') . ' = ' . $database->quote('complete'))
                    ->order($database->quoteName('scanstart') . ' DESC')
                    ->setLimit(1);

                $lastScan = $database->setQuery($query)
                    ->loadResult();

                if (empty($lastScan)) {
                    return $this->critical('No file integrity scans have been completed.');
                }

                $lastScanTime = strtotime((string) $lastScan);
                $daysSinceScan = (time() - $lastScanTime) / 86400;

                if ($daysSinceScan > 30) {
                    return $this->critical(sprintf('Last file integrity scan was %d days ago.', (int) $daysSinceScan));
                }

                if ($daysSinceScan > 7) {
                    return $this->warning(sprintf('Last file integrity scan was %d days ago.', (int) $daysSinceScan));
                }

                return $this->good(sprintf('Last file integrity scan was %d days ago.', (int) $daysSinceScan));
            }
        };
        $scanAgeCheck->setDatabase($database);

        $allChecks[] = $scanAgeCheck;

        // 7. File integrity alerts
        /**
         * File Integrity Alerts Check
         *
         * Monitors unacknowledged file integrity scan alerts requiring review.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * File integrity alerts indicate modified, added, or suspicious files detected
         * during scans. High-threat alerts (score >= 50) may indicate active compromise,
         * backdoors, or malware. Unacknowledged alerts require investigation to determine
         * if changes are legitimate or malicious. Ignoring alerts defeats the purpose
         * of file integrity monitoring.
         *
         * RESULT MEANINGS:
         *
         * GOOD: No unacknowledged file alerts requiring review.
         *
         * WARNING: One or more unacknowledged alerts exist but none are high-threat.
         *          Review alerts to verify changes are legitimate.
         *
         * CRITICAL: One or more unacknowledged high-threat (score >= 50) alerts exist.
         *           Investigate immediately as this may indicate compromise.
         */
        $fileAlertsCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.file_alerts';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_FILE_ALERTS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the file alerts check.
             *
             * Counts unacknowledged alerts, prioritizing high-threat scores.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_scanalerts'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                // Count high-threat unacknowledged alerts
                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_scanalerts'))
                    ->where($database->quoteName('acknowledged') . ' = 0')
                    ->where($database->quoteName('threat_score') . ' >= 50');

                $highThreat = (int) $database->setQuery($query)
                    ->loadResult();

                if ($highThreat > 0) {
                    return $this->critical(sprintf('%d high-threat file alerts require attention.', $highThreat));
                }

                // Count any unacknowledged alerts
                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_scanalerts'))
                    ->where($database->quoteName('acknowledged') . ' = 0');

                $anyAlerts = (int) $database->setQuery($query)
                    ->loadResult();

                if ($anyAlerts > 0) {
                    return $this->warning(sprintf('%d file alerts require review.', $anyAlerts));
                }

                return $this->good('No unacknowledged file alerts.');
            }
        };
        $fileAlertsCheck->setDatabase($database);

        $allChecks[] = $fileAlertsCheck;

        // 8. Temporary super users
        /**
         * Temporary Super Users Check
         *
         * Identifies expired temporary super user records that should be cleaned up.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Admin Tools' temporary super user feature grants elevated privileges for a
         * limited time. Expired records should be automatically cleaned, but monitoring
         * ensures no expired privileges remain active. Stale records may indicate a
         * cleanup process failure or configuration issue that needs attention.
         *
         * RESULT MEANINGS:
         *
         * GOOD: No expired temporary super user records exist.
         *
         * WARNING: One or more expired temporary super user records found. Review and
         *          clean up these records through Admin Tools.
         *
         * CRITICAL: This check does not return critical status.
         */
        $tempSuperUsersCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.temp_superusers';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_TEMP_SUPERUSERS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the temporary super users check.
             *
             * Counts temporary super user records with past expiration dates.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_tempsupers'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_tempsupers'))
                    ->where($database->quoteName('expiration') . ' < NOW()');

                $expired = (int) $database->setQuery($query)
                    ->loadResult();

                if ($expired > 0) {
                    return $this->warning(sprintf('%d expired temporary super user records found.', $expired));
                }

                return $this->good('No expired temporary super user records.');
            }
        };
        $tempSuperUsersCheck->setDatabase($database);

        $allChecks[] = $tempSuperUsersCheck;

        // 9. IP whitelist count
        /**
         * IP Whitelist Count Check
         *
         * Reports the number of IP addresses in the Admin Tools whitelist.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Whitelisted IPs bypass WAF rules and other security checks. Monitoring the
         * whitelist ensures only trusted IPs have unrestricted access and helps detect
         * unauthorized whitelist additions. An unexpectedly large whitelist may indicate
         * over-permissive configuration or compromise. Review regularly to ensure entries
         * are still needed.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of whitelisted IP addresses (including zero).
         *       Always good status - used for monitoring purposes.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $ipWhitelistCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.ip_whitelist';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_IP_WHITELIST_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the IP whitelist check.
             *
             * Counts all entries in the IP allow table.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_ipallow'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_ipallow'));

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d IP addresses in whitelist.', $count));
            }
        };
        $ipWhitelistCheck->setDatabase($database);

        $allChecks[] = $ipWhitelistCheck;

        // 10. WAF rules summary
        /**
         * WAF Rules Summary Check
         *
         * Summarizes total and enabled WAF blacklist rules.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * WAF rules define which attack patterns and malicious requests are blocked.
         * Understanding the ratio of enabled vs. total rules helps assess protection
         * coverage. Having many rules but few enabled may indicate misconfiguration.
         * This check provides visibility into the WAF's defensive posture.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational summary of enabled and total WAF rules.
         *       Always good status - used for monitoring purposes.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $wafRulesCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.waf_rules';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_WAF_RULES_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the WAF rules summary check.
             *
             * Counts both total rules and enabled rules.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_wafblacklists'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                // Total rules
                $queryTotal = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_wafblacklists'));

                $total = (int) $database->setQuery($queryTotal)
                    ->loadResult();

                // Enabled rules
                $queryEnabled = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_wafblacklists'))
                    ->where($database->quoteName('enabled') . ' = 1');

                $enabled = (int) $database->setQuery($queryEnabled)
                    ->loadResult();

                return $this->good(sprintf('%d of %d WAF rules enabled.', $enabled, $total));
            }
        };
        $wafRulesCheck->setDatabase($database);

        $allChecks[] = $wafRulesCheck;

        // 11. Login failures (last 24 hours)
        /**
         * Login Failures Monitoring Check
         *
         * Tracks failed login attempts in the last 24 hours.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Failed login attempts often indicate brute-force attacks or credential
         * stuffing attempts. Monitoring login failures helps detect unauthorized
         * access attempts early. A high number of failures (>10 in 24 hours) suggests
         * an active attack that may require additional security measures like IP
         * blocking or stricter login policies.
         *
         * RESULT MEANINGS:
         *
         * GOOD: 10 or fewer login failures in the last 24 hours. Normal activity level.
         *
         * WARNING: More than 10 login failures detected, indicating possible brute-force
         *          attack. Review logs and consider blocking suspicious IPs.
         *
         * CRITICAL: This check does not return critical status.
         */
        $loginFailuresCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.login_failures';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_LOGIN_FAILURES_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the login failures check.
             *
             * Counts loginfailure events from the last 24 hours.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_log'))
                    ->where($database->quoteName('reason') . ' = ' . $database->quote('loginfailure'))
                    ->where($database->quoteName('logdate') . ' >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                if ($count > 10) {
                    return $this->warning(sprintf('%d login failures in the last 24 hours.', $count));
                }

                return $this->good(sprintf('%d login failures in the last 24 hours.', $count));
            }
        };
        $loginFailuresCheck->setDatabase($database);

        $allChecks[] = $loginFailuresCheck;

        // 12. Geoblocking events (last 7 days)
        /**
         * Geoblocking Events Check
         *
         * Counts access attempts blocked by geoblocking rules in the last 7 days.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Geoblocking restricts access based on visitor geographic location, useful for
         * sites targeting specific regions or blocking known attack sources. Monitoring
         * geoblocking events helps verify rules are working as expected and identifies
         * geographic attack patterns. High numbers may indicate targeted attacks from
         * specific countries.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of geoblocking events (including zero).
         *       Always good status - used for monitoring purposes.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $geoblockingCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.geoblocking';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_GEOBLOCKING_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the geoblocking events check.
             *
             * Counts geoblocking log entries from the last 7 days.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_log'))
                    ->where($database->quoteName('reason') . ' = ' . $database->quote('geoblocking'))
                    ->where($database->quoteName('logdate') . ' >= DATE_SUB(NOW(), INTERVAL 7 DAY)');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d geoblocking events in the last 7 days.', $count));
            }
        };
        $geoblockingCheck->setDatabase($database);

        $allChecks[] = $geoblockingCheck;

        // 13. SQL injection blocks (last 7 days)
        /**
         * SQL Injection Attack Blocking Check
         *
         * Tracks SQL injection attempts blocked by Admin Tools in the last 7 days.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * SQL injection attacks attempt to manipulate database queries to steal data,
         * bypass authentication, or damage the database. The Admin Tools SQLiShield
         * protects against these attacks. Monitoring blocked attempts helps identify
         * attack campaigns and verify protection is active. A high number indicates
         * active targeting that may require additional security measures.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of blocked SQL injection attempts.
         *       Always good status - successful blocking is positive.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $sqliBlocksCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.sqli_blocks';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_SQLI_BLOCKS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the SQL injection blocks check.
             *
             * Counts sqlishield log entries from the last 7 days.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_log'))
                    ->where($database->quoteName('reason') . ' = ' . $database->quote('sqlishield'))
                    ->where($database->quoteName('logdate') . ' >= DATE_SUB(NOW(), INTERVAL 7 DAY)');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d SQL injection attempts blocked in the last 7 days.', $count));
            }
        };
        $sqliBlocksCheck->setDatabase($database);

        $allChecks[] = $sqliBlocksCheck;

        // 14. XSS blocks (last 7 days)
        /**
         * Cross-Site Scripting (XSS) Attack Blocking Check
         *
         * Tracks XSS attempts blocked by Admin Tools in the last 7 days.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Cross-site scripting attacks inject malicious scripts into web pages to steal
         * user data, hijack sessions, or deface content. The Admin Tools XSSShield
         * protects against these attacks by filtering malicious input. Monitoring blocked
         * XSS attempts verifies protection is working and helps identify attack patterns.
         * A spike may indicate targeted attacks requiring investigation.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of blocked XSS attempts.
         *       Always good status - successful blocking is positive.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $xssBlocksCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.xss_blocks';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_XSS_BLOCKS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the XSS blocks check.
             *
             * Counts xssshield log entries from the last 7 days.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_log'))
                    ->where($database->quoteName('reason') . ' = ' . $database->quote('xssshield'))
                    ->where($database->quoteName('logdate') . ' >= DATE_SUB(NOW(), INTERVAL 7 DAY)');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d XSS attempts blocked in the last 7 days.', $count));
            }
        };
        $xssBlocksCheck->setDatabase($database);

        $allChecks[] = $xssBlocksCheck;

        // 15. Admin directory access log (last 7 days)
        /**
         * Admin Directory Access Monitoring Check
         *
         * Tracks attempts to access the admin directory in the last 7 days.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * If Admin Tools is configured to protect or rename the admin directory,
         * monitoring access attempts helps detect reconnaissance and brute-force
         * attacks against the administrator backend. Attackers often target /administrator
         * to find the login page. High numbers of blocked attempts indicate active
         * attack campaigns against your admin area.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Returns informational count of admin directory access attempts.
         *       Always good status - used for monitoring purposes.
         *
         * WARNING: Admin Tools is not installed.
         *
         * CRITICAL: This check does not return critical status.
         */
        $adminAccessCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug for this check.
             *
             * @return string The check slug
             */
            public function getSlug(): string
            {
                return 'akeeba_admintools.admin_access';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string The category slug
             */
            public function getCategory(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string The provider slug
             */
            public function getProvider(): string
            {
                return 'akeeba_admintools';
            }

            /**
             * Get the human-readable title for this check.
             *
             * @return string The translated check title
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBAADMINTOOLS_CHECK_ADMIN_ACCESS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebaadmintools/src/Extension/AkeebaAdminToolsPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_admintools';
            }

            /**
             * Perform the admin directory access check.
             *
             * Counts admindir log entries from the last 7 days.
             *
             * @return HealthCheckResult The result with status and description
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'admintools_log'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Admin Tools is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__admintools_log'))
                    ->where($database->quoteName('reason') . ' = ' . $database->quote('admindir'))
                    ->where($database->quoteName('logdate') . ' >= DATE_SUB(NOW(), INTERVAL 7 DAY)');

                $count = (int) $database->setQuery($query)
                    ->loadResult();

                return $this->good(sprintf('%d admin directory access attempts in the last 7 days.', $count));
            }
        };
        $adminAccessCheck->setDatabase($database);

        $allChecks[] = $adminAccessCheck;

        // Filter checks based on plugin configuration
        foreach ($allChecks as $allCheck) {
            if ($this->isCheckEnabled($allCheck->getSlug())) {
                $collectChecksEvent->addResult($allCheck);
            }
        }
    }

    /**
     * Check if a specific health check is enabled in the plugin configuration.
     *
     * @param string $slug The check slug
     *
     * @return bool True if enabled, false otherwise
     */
    private function isCheckEnabled(string $slug): bool
    {
        // Convert slug to param name (e.g., 'akeeba_admintools.installed' -> 'check_akeeba_admintools_installed')
        $paramName = 'check_' . str_replace('.', '_', $slug);

        // Get the toggle value (1 = enabled, 0 = disabled)
        // Default to 1 (enabled) if parameter not set
        return (bool) $this->params->get($paramName, 1);
    }
}
