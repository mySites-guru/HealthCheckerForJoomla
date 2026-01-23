<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Scheduled Content Health Check
 *
 * This check counts articles with future publish dates (publish_up > now),
 * providing visibility into your content publishing pipeline.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Scheduled publishing is a powerful feature for content planning, allowing
 * articles to be prepared in advance and automatically go live at specific
 * times. This check is purely informational, helping site administrators
 * understand their content pipeline and verify that scheduled content is
 * properly queued. It's useful for editorial workflow oversight.
 *
 * RESULT MEANINGS:
 *
 * GOOD: This check always returns GOOD status. It reports either the count
 * of articles scheduled for future publication (showing active content
 * planning), or confirms no articles are scheduled (which is also normal
 * for sites that publish immediately).
 *
 * WARNING: This check does not return warnings.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ScheduledContentCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'content.scheduled_content'
     */
    public function getSlug(): string
    {
        return 'content.scheduled_content';
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
     * Perform the health check to count articles scheduled for future publication.
     *
     * This is an informational check that counts unpublished articles with future
     * publish_up dates. These articles are genuinely scheduled and will automatically
     * become visible when their publish_up date is reached. This check helps
     * administrators monitor their content publishing pipeline.
     *
     * @return HealthCheckResult Always returns good status with scheduled article count
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check for unpublished articles with future publish_up dates (genuinely scheduled).
        // These articles are queued for automatic publication when their publish_up date arrives.
        // We only count state=0 (unpublished) to avoid counting published articles that happen
        // to have future dates.
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('state') . ' = 0')
            ->where($database->quoteName('publish_up') . ' > NOW()');

        $scheduledCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($scheduledCount > 0) {
            return $this->good(sprintf('%d article(s) scheduled for future publication.', $scheduledCount));
        }

        return $this->good('No articles scheduled for future publication.');
    }
}
