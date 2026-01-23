<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Archived Content Health Check
 *
 * This check counts articles in archived state (state = 2), providing visibility
 * into how much historical content the site maintains.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Archiving is Joomla's way of preserving old content while removing it from
 * normal listings. Archived articles can still be accessed via archive menu items
 * or direct links, but don't clutter regular category views. This check is
 * informational, helping administrators understand their content lifecycle and
 * prompting periodic review of archived content for relevance.
 *
 * RESULT MEANINGS:
 *
 * GOOD: This check always returns GOOD status. It reports the count of archived
 * articles, with a suggestion to periodically review archived content if the
 * count is large (over 100). Having archived content is healthy for sites with
 * long histories, news sites, or blogs.
 *
 * WARNING: This check does not return warnings.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ArchivedContentCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'content.archived_content'
     */
    public function getSlug(): string
    {
        return 'content.archived_content';
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
     * Perform the health check to count archived articles.
     *
     * This is an informational check that counts articles in archived state (state = 2).
     * Archived articles are preserved historical content that can still be accessed via
     * archive menu items or direct links, but don't appear in normal category listings.
     * The check suggests periodic review when the count exceeds 100 articles.
     *
     * @return HealthCheckResult Always returns good status with archived article count
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check for archived articles (state = 2).
        // Archived articles are historical content that don't appear in normal listings
        // but can still be accessed via archive menu items or direct URLs.
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('state') . ' = 2');

        $archivedCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($archivedCount > 100) {
            return $this->good(
                sprintf('%d articles are archived. Consider periodically reviewing archived content.', $archivedCount),
            );
        }

        if ($archivedCount > 0) {
            return $this->good(sprintf('%d article(s) are archived.', $archivedCount));
        }

        return $this->good('No archived articles.');
    }
}
