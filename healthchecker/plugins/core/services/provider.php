<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

\defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use MySitesGuru\HealthChecker\Plugin\Core\Extension\CorePlugin;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container): \MySitesGuru\HealthChecker\Plugin\Core\Extension\CorePlugin {
                $corePlugin = new CorePlugin(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('healthchecker', 'core'),
                );
                $corePlugin->setApplication(Factory::getApplication());
                $corePlugin->setDatabase($container->get(DatabaseInterface::class));

                return $corePlugin;
            },
        );
    }
};
