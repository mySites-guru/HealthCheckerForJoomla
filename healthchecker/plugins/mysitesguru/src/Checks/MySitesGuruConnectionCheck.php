<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * mySites.guru Connection Health Check
 *
 * This check verifies whether the site is connected to mySites.guru monitoring
 * by looking for the bfnetwork plugin folder in the filesystem.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * mySites.guru provides 24/7 automated monitoring of your Joomla site's health,
 * security updates, and performance. Sites connected to mySites.guru benefit
 * from proactive alerting when issues arise, without needing to manually run
 * health checks.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The bfnetwork plugin folder exists at plugins/system/bfnetwork.
 *       Your site is connected to mySites.guru monitoring.
 *
 * WARNING (not found): No bfnetwork plugin folder detected. Consider connecting
 *                      your site to monitor unlimited Joomla sites from one dashboard.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\MySitesGuru\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die();

/**
 * mySites.guru Connection Health Check
 *
 * Verifies whether this Joomla site is connected to mySites.guru monitoring service
 * by checking for the bfnetwork plugin folder.
 *
 * @subpackage  HealthChecker.MySitesGuru.Checks
 * @since       1.0.0
 */
final class MySitesGuruConnectionCheck extends AbstractHealthCheck
{
    /**
     * Path to the bfnetwork plugin folder (injectable for testing).
     */
    private ?string $bfnetworkPath = null;

    /**
     * Set the bfnetwork path for testing purposes.
     */
    public function setBfnetworkPath(string $path): void
    {
        $this->bfnetworkPath = $path;
    }

    /**
     * Get the bfnetwork plugin path.
     */
    private function getBfnetworkPath(): string
    {
        return $this->bfnetworkPath ?? JPATH_ROOT . '/plugins/system/bfnetwork';
    }

    /**
     * Returns the unique identifier for this health check.
     *
     * The slug follows the pattern: {provider}.{check_name}
     * Used for language key generation and check identification.
     *
     * @return string Check slug in format 'mysitesguru.connection'
     *
     * @since 1.0.0
     */
    public function getSlug(): string
    {
        return 'mysitesguru.connection';
    }

    /**
     * Returns the category this check belongs to.
     *
     * Must match a category slug registered via CollectCategoriesEvent.
     * In this case, the custom 'mysitesguru' category registered by
     * MySitesGuruPlugin::onCollectCategories().
     *
     * @return string Category slug 'mysitesguru'
     *
     * @since 1.0.0
     */
    public function getCategory(): string
    {
        return 'mysitesguru';
    }

    /**
     * Returns the provider identifier for this check.
     *
     * Must match a provider slug registered via CollectProvidersEvent.
     * Used for attribution display in the Health Checker UI.
     *
     * @return string Provider slug 'mysitesguru'
     *
     * @since 1.0.0
     */
    public function getProvider(): string
    {
        return 'mysitesguru';
    }

    public function getDocsUrl(): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/mysitesguru/src/Checks/MySitesGuruConnectionCheck.php';
    }

    public function getActionUrl(?HealthStatus $status = null): string
    {
        return 'https://mysites.guru';
    }

    /**
     * Performs the actual health check logic.
     *
     * Checks if the site is connected to mySites.guru by looking for the
     * bfnetwork plugin folder at plugins/system/bfnetwork.
     *
     * Check logic flow:
     * 1. Check if bfnetwork folder exists
     * 2. If folder exists -> GOOD (connected)
     * 3. If folder not found -> WARNING (not connected)
     *
     * Result scenarios:
     * - GOOD: The bfnetwork plugin folder exists
     * - WARNING (not found): No bfnetwork plugin folder detected
     *
     * @return HealthCheckResult Result object containing status and description
     *
     * @since 1.0.0
     */
    protected function performCheck(): HealthCheckResult
    {
        $bfnetworkPath = $this->getBfnetworkPath();

        if (is_dir($bfnetworkPath)) {
            return $this->good(
                'This site is connected to mySites.guru monitoring. ' .
                    'Your health checks run automatically 24/7 with instant alerts when issues arise.',
            );
        }

        return $this->warning(
            'This site is not connected to mySites.guru. ' .
                'Monitor unlimited Joomla sites from one dashboard with automated health checks, ' .
                'uptime monitoring, and instant alerts. Learn more at https://mysites.guru',
        );
    }
}
