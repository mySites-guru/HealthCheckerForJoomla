<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Module\Administrator\Helper;

use Joomla\CMS\Application\CMSApplication;
use Joomla\Registry\Registry;

\defined('_JEXEC') || die;

/**
 * Helper for mod_healthchecker
 *
 * @since  1.0.0
 */
class HealthCheckerHelper
{
    /**
     * Get module configuration for the template.
     * Actual health check data is loaded via AJAX.
     *
     * @param Registry $registry Module parameters
     * @param CMSApplication $cmsApplication Application instance
     *
     * @return  array  Module configuration
     *
     * @since   1.0.0
     */
    public function getHealthStats(Registry $registry, CMSApplication $cmsApplication): array
    {
        return [
            'showCritical' => $registry->get('show_critical', '1') !== '0',
            'showWarning' => $registry->get('show_warning', '1') !== '0',
            'showGood' => $registry->get('show_good', '1') !== '0',
            'enableCache' => $registry->get('enable_cache', '1') === '1',
            'cacheDuration' => (int) $registry->get('cache_duration', 900),
        ];
    }
}
