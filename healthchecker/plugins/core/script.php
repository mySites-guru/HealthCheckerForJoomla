<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

defined('_JEXEC') || die;

use Joomla\CMS\Installer\InstallerAdapter;

class PlgHealthcheckerCoreInstallerScript
{
    /**
     * Files to remove during upgrade. Add entries here when checks are
     * deleted so existing installations are cleaned up automatically.
     *
     * Paths are relative to the plugin directory:
     * plugins/healthchecker/core/
     *
     * @var list<string>
     */
    private const OBSOLETE_FILES = [
        // Removed in 3.0.38: BackupAgeCheck replaced by akeeba_backup.last_backup plugin check
        'src/Checks/Database/BackupAgeCheck.php',
        // Removed in 3.0.36: Phantom check for non-existent plg_user_userlog
        'src/Checks/Security/UserActionsLogCheck.php',
    ];

    public function postflight(string $type, InstallerAdapter $installerAdapter): void
    {
        if ($type === 'update') {
            $this->removeObsoleteFiles();
        }
    }

    private function removeObsoleteFiles(): void
    {
        $pluginDir = JPATH_PLUGINS . '/healthchecker/core/';

        foreach (self::OBSOLETE_FILES as $file) {
            $path = $pluginDir . $file;

            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
