<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Trashed Content Health Check
 *
 * This check counts articles in the trash (state = -2) that should be
 * periodically emptied to keep the database clean.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Trashed articles remain in the database taking up space and potentially slowing
 * queries. Unlike permanent deletion, trashing is a soft-delete that keeps content
 * recoverable. However, accumulating trashed content over time wastes database
 * resources and makes backups larger than necessary. Regular trash maintenance
 * is part of good site hygiene.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A reasonable amount of trashed content exists (50 or fewer articles),
 * which is normal for sites with active content management. Items may have been
 * recently trashed and could still be needed for recovery.
 *
 * WARNING: More than 50 articles are in the trash. Empty the trash periodically
 * via the Article Manager to permanently delete old content and free up database
 * space. Ensure trashed items are truly no longer needed before emptying.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TrashedContentCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'content.trashed_content'
     */
    public function getSlug(): string
    {
        return 'content.trashed_content';
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
     * Perform the Trashed Content health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('state') . ' = -2');

        $trashedCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($trashedCount > 50) {
            return $this->warning(
                sprintf('%d articles in trash. Consider emptying the trash to clean up the database.', $trashedCount),
            );
        }

        return $this->good(sprintf('%d article(s) in trash.', $trashedCount));
    }
}
