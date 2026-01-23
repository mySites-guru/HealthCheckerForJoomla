<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Expired Content Health Check
 *
 * This check identifies published articles that have passed their expiry date
 * (publish_down) but remain in a published state.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The publish_down field allows time-limited content like promotions, events, or
 * seasonal announcements to automatically expire. However, if Joomla's scheduler
 * or cron jobs are not running correctly, these articles may remain visible after
 * their intended end date. This can show outdated promotions, past events, or
 * obsolete information to visitors, damaging credibility.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No published articles have passed their expiry date, meaning content
 * expiration is working correctly or no time-limited content is in use.
 *
 * WARNING: One or more published articles have passed their publish_down date
 * but remain published. This may indicate a cron/scheduler issue. Review these
 * articles and either update their expiry dates, unpublish them manually, or
 * fix the site's scheduled task configuration.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ExpiredContentCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'content.expired_content'
     */
    public function getSlug(): string
    {
        return 'content.expired_content';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'content'
     */
    public function getCategory(): string
    {
        return 'content';
    }

    /**
     * Perform the Expired Content health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check for published articles past their publish_down date
        // Note: publish_down of NULL or '0000-00-00 00:00:00' means no expiry
        $nullDate = $database->quote($database->getNullDate());

        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('state') . ' = 1')
            ->where($database->quoteName('publish_down') . ' IS NOT NULL')
            ->where($database->quoteName('publish_down') . ' != ' . $nullDate)
            ->where($database->quoteName('publish_down') . ' < NOW()');

        $expiredCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($expiredCount > 0) {
            return $this->warning(
                sprintf(
                    '%d published article(s) have passed their expiry date but remain published. Review and update or unpublish.',
                    $expiredCount,
                ),
            );
        }

        return $this->good('No published articles have passed their expiry date.');
    }
}
