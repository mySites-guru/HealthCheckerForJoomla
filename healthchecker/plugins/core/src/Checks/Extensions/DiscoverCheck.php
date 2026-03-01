<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Discovered Extensions Health Check
 *
 * This check runs a fresh Joomla Discover scan and reports any extensions
 * found on the filesystem that have not been formally installed.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Extensions sitting on the filesystem without being installed can pose
 * security risks. They may contain vulnerabilities that cannot be patched
 * through Joomla's update system since they are not registered. They also
 * indicate incomplete installations or leftover files from manual deployments
 * that should be cleaned up.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No undiscovered extensions found. All extensions on the filesystem
 * are properly installed.
 *
 * WARNING: One or more extensions were found on the filesystem that are not
 * installed. Visit the Discover page to install or remove them.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class DiscoverCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'extensions.discover';
    }

    public function getCategory(): string
    {
        return 'extensions';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/DiscoverCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_installer&view=discover';
        }

        return null;
    }

    /**
     * Performs the discovered extensions health check.
     *
     * Triggers a fresh Joomla Discover scan via the com_installer MVC factory
     * to ensure results are current, then queries the database for extensions
     * with state = -1 (discovered but not installed).
     *
     * If the Discover scan cannot be triggered (e.g. com_installer unavailable),
     * the check falls back to reading existing state from the database.
     *
     * @return HealthCheckResult WARNING if undiscovered extensions exist, GOOD otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        $this->runDiscoverScan();

        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('state') . ' = -1');

        $discoveredCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($discoveredCount > 0) {
            return $this->warning(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_DISCOVER_WARNING', $discoveredCount),
            );
        }

        return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_DISCOVER_GOOD'));
    }

    /**
     * Trigger a fresh Discover scan via Joomla's com_installer.
     *
     * This purges stale discovered entries and re-scans the filesystem
     * for extensions that exist but are not installed. Silently fails
     * if com_installer is unavailable.
     */
    private function runDiscoverScan(): void
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_installer');
            $model = $component->getMVCFactory()
                ->createModel('Discover', 'Administrator', [
                    'ignore_request' => true,
                ]);
            $model->discover();
        } catch (\Throwable) {
            // Fall back to reading existing state from the database
        }
    }
}
