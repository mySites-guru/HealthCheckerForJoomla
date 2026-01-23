<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Extension\MySitesGuruPlugin;

\defined('_JEXEC') || die;

/**
 * Dependency Injection Service Provider for mySites.guru Health Checker Plugin
 *
 * This service provider registers the MySitesGuruPlugin with Joomla's DI container.
 * It handles dependency injection for the plugin, ensuring all required services
 * (dispatcher, application, database) are properly injected before the plugin
 * is instantiated.
 *
 * The provider follows Joomla 4+ plugin architecture patterns:
 * - Returns anonymous class implementing ServiceProviderInterface
 * - Registers PluginInterface binding in container
 * - Injects dispatcher, application, and database dependencies
 * - Loads plugin configuration from database via PluginHelper
 *
 * This file is automatically loaded by Joomla's plugin system when the
 * mySites.guru Health Checker plugin is enabled.
 *
 * @subpackage  HealthChecker.MySitesGuru
 * @since       1.0.0
 *
 * @return ServiceProviderInterface Anonymous service provider instance
 */
return new class implements ServiceProviderInterface {
    /**
     * Registers the plugin service with the DI container.
     *
     * This method is called by Joomla's DI container system during plugin
     * initialization. It registers a factory closure that creates and configures
     * a MySitesGuruPlugin instance with all required dependencies.
     *
     * Dependency injection flow:
     * 1. Retrieve event dispatcher from container
     * 2. Load plugin configuration from database
     * 3. Instantiate MySitesGuruPlugin with dispatcher and config
     * 4. Inject Joomla application instance
     * 5. Inject database connection
     * 6. Return fully configured plugin instance
     *
     * @param Container $container The Joomla DI container instance
     *
     * @since 1.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container): \MySitesGuru\HealthChecker\Plugin\MySitesGuru\Extension\MySitesGuruPlugin {
                // Get the event dispatcher for subscribing to Health Checker events
                $dispatcher = $container->get(DispatcherInterface::class);

                // Load plugin configuration (params) from database
                // PluginHelper::getPlugin returns object with: name, type, element, folder, params
                $mySitesGuruPlugin = new MySitesGuruPlugin(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('healthchecker', 'mysitesguru'),
                );

                // Inject the Joomla application instance for accessing session, input, etc.
                $mySitesGuruPlugin->setApplication(Factory::getApplication());

                // Inject database connection for health checks that query database
                $mySitesGuruPlugin->setDatabase($container->get(DatabaseInterface::class));

                return $mySitesGuruPlugin;
            },
        );
    }
};
