<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Draft/Unpublished Articles Health Check
 *
 * This check counts articles in unpublished/draft state (state = 0), helping
 * identify forgotten work-in-progress content.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Draft articles represent incomplete work that may be forgotten over time.
 * A large number of drafts often indicates stalled content initiatives, abandoned
 * ideas, or workflow bottlenecks. They clutter the article manager and can
 * confuse editors about what content needs attention. Old drafts should either
 * be completed and published, or deleted if no longer relevant.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A reasonable number of draft articles exist (20 or fewer), which is
 * normal for sites with active content creation workflows. Drafts provide a
 * space for work-in-progress.
 *
 * WARNING: More than 20 unpublished/draft articles exist. Review these drafts
 * and either complete and publish them, or delete old drafts that are no longer
 * relevant to reduce clutter and management overhead.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DraftArticlesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'content.draft_articles'
     */
    public function getSlug(): string
    {
        return 'content.draft_articles';
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
     * Perform the health check to count unpublished/draft articles.
     *
     * This check counts articles in unpublished state (state = 0), which includes
     * both true drafts and intentionally unpublished content. A high count (>20)
     * may indicate stalled content initiatives, abandoned drafts, or workflow
     * bottlenecks. The threshold of 20 allows for normal work-in-progress while
     * flagging potential clutter.
     *
     * @return HealthCheckResult Warning if >20 drafts exist, good otherwise with count
     */
    /**
     * Perform the Draft Articles health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count all unpublished articles (state = 0).
        // This includes both true drafts and intentionally unpublished content.
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('state') . ' = 0');

        $draftCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($draftCount > 20) {
            return $this->warning(
                sprintf('%d unpublished/draft articles. Review and publish or delete old drafts.', $draftCount),
            );
        }

        return $this->good(sprintf('%d unpublished/draft article(s).', $draftCount));
    }
}
