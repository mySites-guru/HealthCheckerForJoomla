<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Empty/Minimal Articles Health Check
 *
 * This check identifies published articles with very little or no content
 * (less than 50 characters combined in introtext and fulltext).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Published articles with little to no content create poor user experiences.
 * Visitors who click through to empty pages lose trust in the site. Search
 * engines may penalize pages with thin content, and empty pages waste crawl
 * budget. Empty articles are often placeholders that were accidentally published
 * or content that was deleted without unpublishing the article.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either no published articles have minimal content, or only a small
 * number exist (5 or fewer). Some minimal articles may be intentional for
 * special purposes like module containers or redirect targets.
 *
 * WARNING: More than 5 published articles have empty or very short content.
 * Review these articles and either add meaningful content, or unpublish them
 * to prevent visitors from seeing empty pages.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class EmptyArticlesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'content.empty_articles'
     */
    public function getSlug(): string
    {
        return 'content.empty_articles';
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
     * Perform the Empty Articles health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check for published articles with empty or very short content (< 50 chars combined)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('state') . ' = 1')
            ->where(
                '(CHAR_LENGTH(COALESCE(' . $database->quoteName('introtext') . ', ' . $database->quote('') . ')) + ' .
                'CHAR_LENGTH(COALESCE(' . $database->quoteName('fulltext') . ', ' . $database->quote('') . '))) < 50',
            );

        $emptyCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($emptyCount > 5) {
            return $this->warning(
                sprintf(
                    '%d published articles have empty or very short content (less than 50 characters). Review and add content or unpublish.',
                    $emptyCount,
                ),
            );
        }

        if ($emptyCount > 0) {
            return $this->good(sprintf('%d published article(s) with minimal content.', $emptyCount));
        }

        return $this->good('All published articles have substantial content.');
    }
}
