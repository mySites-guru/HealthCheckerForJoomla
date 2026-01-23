<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Uncategorized Content Health Check
 *
 * This check counts published articles in the default "Uncategorized" category,
 * which often indicates poor content organization.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The "Uncategorized" category is Joomla's default holding area for content that
 * hasn't been properly organized. While convenient during initial content creation,
 * leaving articles uncategorized makes content harder to find, manage, and display
 * in category-based layouts. It also suggests incomplete editorial workflow and
 * can result in confusing URL structures.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either no articles are in "Uncategorized", only a small number exist
 * (10 or fewer), or the Uncategorized category has been removed entirely.
 * Small numbers may be temporary during content creation.
 *
 * WARNING: More than 10 published articles remain in "Uncategorized". Review
 * these articles and move them to appropriate categories to improve content
 * organization, navigation, and URL structure.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UncategorizedContentCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'content.uncategorized_content'
     */
    public function getSlug(): string
    {
        return 'content.uncategorized_content';
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
     * Perform the Uncategorized Content health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Find uncategorized category ID
        $query = $database->getQuery(true)
            ->select($database->quoteName('id'))
            ->from($database->quoteName('#__categories'))
            ->where($database->quoteName('extension') . ' = ' . $database->quote('com_content'))
            ->where($database->quoteName('alias') . ' = ' . $database->quote('uncategorised'));

        $uncategorizedId = (int) $database->setQuery($query)
            ->loadResult();

        if ($uncategorizedId === 0) {
            return $this->good('Uncategorized category not found or has been removed.');
        }

        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('catid') . ' = ' . $uncategorizedId)
            ->where($database->quoteName('state') . ' = 1');

        $uncategorizedCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($uncategorizedCount > 10) {
            return $this->warning(
                sprintf('%d published articles are in "Uncategorized". Consider organizing them.', $uncategorizedCount),
            );
        }

        if ($uncategorizedCount > 0) {
            return $this->good(sprintf('%d article(s) in "Uncategorized".', $uncategorizedCount));
        }

        return $this->good('No articles in "Uncategorized".');
    }
}
