<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Custom Configuration Health Check (Example)
 *
 * EXAMPLE TEMPLATE FOR THIRD-PARTY DEVELOPERS
 *
 * This example check demonstrates how to create a health check that:
 * - Uses database access via $this->requireDatabase()
 * - Belongs to an existing category ('extensions')
 * - Returns different statuses based on dynamic conditions
 * - Follows Health Checker coding conventions and best practices
 *
 * DEVELOPER NOTES:
 * - This is a reference implementation for third-party plugin developers
 * - Copy this pattern when creating your own database-driven health checks
 * - The AbstractHealthCheck base class handles error wrapping automatically
 * - Use requireDatabase() for PHPStan level 8 null safety compliance
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Having too many extensions installed can impact site performance, increase
 * security surface area, and make maintenance more complex. This check monitors
 * the total extension count and warns administrators when the number becomes
 * excessive, helping maintain a lean and manageable Joomla installation.
 *
 * RESULT MEANINGS:
 *
 * GOOD: 100 or fewer extensions are installed. This is a reasonable number
 *       that won't significantly impact performance or maintainability.
 *
 * WARNING: More than 100 extensions are installed. The site may be running
 *          unnecessary extensions that impact performance. Review installed
 *          extensions and disable or uninstall those not in use.
 *
 * CRITICAL: This check does not return critical status.
 *
 * @subpackage  HealthChecker.Example
 * @since       1.0.0
 */

namespace MySitesGuru\HealthChecker\Plugin\Example\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

/**
 * Example health check demonstrating database access patterns.
 *
 * This check extends AbstractHealthCheck which provides:
 * - Automatic error handling and wrapping
 * - Helper methods: good(), warning(), critical()
 * - Database access via requireDatabase()
 * - Automatic language key translation for titles
 *
 * @since  1.0.0
 */
final class CustomConfigCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * Format: {provider_slug}.{check_name}
     * - Must be lowercase
     * - Use underscores for spaces
     * - Must be unique across all plugins
     * - The provider slug ('example') must match what your plugin registers
     *
     * @return string The check slug in format 'provider.check_name'
     *
     * @since  1.0.0
     */
    public function getSlug(): string
    {
        return 'example.custom_config';
    }

    /**
     * Returns the category this check belongs to.
     *
     * Categories group related checks in the UI. You can:
     * - Use existing categories: 'system', 'database', 'security', 'users',
     *   'extensions', 'performance', 'seo', 'content'
     * - Create custom categories via onCollectCategories event
     *
     * @return string The category slug (e.g., 'extensions', 'security')
     *
     * @since  1.0.0
     */
    public function getCategory(): string
    {
        // Using an existing core category
        return 'extensions';
    }

    /**
     * Returns the provider slug that owns this check.
     *
     * The provider identifies which plugin/extension created this check.
     * This must match the slug you register in your plugin's
     * onCollectProviders() event handler.
     *
     * @return string The provider slug (e.g., 'example', 'mycompany')
     *
     * @since  1.0.0
     */
    public function getProvider(): string
    {
        return 'example';
    }

    /**
     * Performs the actual health check logic.
     *
     * This method is called by the framework when executing the check.
     * The AbstractHealthCheck base class wraps this in try/catch and
     * automatically converts exceptions to WARNING results.
     *
     * BEST PRACTICES:
     * - Use requireDatabase() to get a non-null database instance
     * - Use helper methods: $this->good(), $this->warning(), $this->critical()
     * - Include helpful descriptions in results
     * - Keep checks fast (< 1 second if possible)
     * - Extract complex logic to private methods for readability
     *
     * @return HealthCheckResult Result object with status and description
     *
     * @since  1.0.0
     */
    protected function performCheck(): HealthCheckResult
    {
        // PATTERN: Use requireDatabase() to ensure non-null database instance
        // This throws an exception if database is unavailable, which gets caught
        // by AbstractHealthCheck and converted to a WARNING result automatically
        $this->requireDatabase();

        // PATTERN: Extract business logic to separate methods for clarity
        // In a real plugin, you'd check your extension's actual configuration
        $extensionCount = $this->countExtensions();

        // PATTERN: Use meaningful thresholds and provide actionable messages
        if ($extensionCount > 100) {
            return $this->warning(
                sprintf(
                    '[EXAMPLE CHECK] You have %d extensions installed. This is a demonstration warning from the Example Provider plugin. To hide this, disable the "Health Checker - Example Provider" plugin in Extensions → Plugins.',
                    $extensionCount,
                ),
            );
        }

        // PATTERN: Good status should confirm what was checked
        return $this->good(
            sprintf(
                '[EXAMPLE CHECK] %d extensions installed. This is a demonstration from the Example Provider plugin. To hide this, disable the "Health Checker - Example Provider" plugin in Extensions → Plugins.',
                $extensionCount,
            ),
        );
    }

    /**
     * Counts installed extensions from the database.
     *
     * This is a helper method demonstrating how to:
     * - Build Joomla database queries safely
     * - Use quoteName() to prevent SQL injection
     * - Use quote() for values
     * - Cast results to appropriate types
     *
     * DEVELOPER NOTES:
     * - Always use quoteName() for table/column names
     * - Always use quote() for string values
     * - Cast database results to expected types
     * - Consider caching expensive queries if checks run frequently
     *
     * @return int Number of installed components, modules, and plugins
     *
     * @since  1.0.0
     */
    private function countExtensions(): int
    {
        $database = $this->requireDatabase();

        // PATTERN: Use Joomla's query builder for database safety
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' IN (' . implode(',', [
                $database->quote('component'),
                $database->quote('module'),
                $database->quote('plugin'),
            ]) . ')');

        // PATTERN: Always cast database results to expected types
        return (int) $database->setQuery($query)
            ->loadResult();
    }
}
