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
use MySitesGuru\HealthChecker\Plugin\AkeebaAdminTools\Extension\AkeebaAdminToolsPlugin;

\defined('_JEXEC') || die;

/**
 * Service Provider for Akeeba Admin Tools Health Checker Plugin
 *
 * This anonymous class implements the Joomla Dependency Injection Container service
 * provider pattern to bootstrap the Akeeba Admin Tools health checker plugin. It
 * registers the plugin with the DI container and configures its dependencies.
 *
 * RESPONSIBILITIES:
 * - Creates and configures the AkeebaAdminToolsPlugin instance
 * - Injects required dependencies (dispatcher, database, application)
 * - Registers the plugin interface for the Joomla plugin system
 *
 * DEPENDENCY INJECTION:
 * The provider injects three core dependencies into the plugin:
 * 1. DispatcherInterface - For event subscription and dispatching
 * 2. DatabaseInterface - For querying Admin Tools tables
 * 3. CMSApplication - For accessing application context
 *
 * This follows Joomla 5's modern plugin architecture where plugins are registered
 * as services in the DI container rather than being instantiated directly.
 *
 * @subpackage  HealthChecker.AkeebaAdminTools
 * @since       1.0.0
 */
return new class implements ServiceProviderInterface {
    /**
     * Registers the Akeeba Admin Tools plugin service with the DI container.
     *
     * This method creates a factory closure that instantiates the plugin with all
     * required dependencies. The container calls this factory when the plugin is
     * needed, enabling lazy loading and dependency injection.
     *
     * SETUP PROCESS:
     * 1. Retrieve event dispatcher from container
     * 2. Load plugin configuration from database
     * 3. Create plugin instance with dispatcher and config
     * 4. Inject application instance
     * 5. Inject database connection
     * 6. Return fully configured plugin
     *
     * @param Container $container The Joomla DI container
     *
     * @since 1.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            /**
             * Factory closure for creating the plugin instance.
             *
             * This closure is called by the DI container when the plugin needs to be
             * instantiated. It retrieves all dependencies from the container and
             * configures the plugin instance.
             *
             * @param Container $container The DI container for dependency retrieval
             *
             * @return AkeebaAdminToolsPlugin Fully configured plugin instance
             */
            function (
                Container $container,
            ): \MySitesGuru\HealthChecker\Plugin\AkeebaAdminTools\Extension\AkeebaAdminToolsPlugin {
                $dispatcher = $container->get(DispatcherInterface::class);
                $akeebaAdminToolsPlugin = new AkeebaAdminToolsPlugin(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('healthchecker', 'akeebaadmintools'),
                );
                $akeebaAdminToolsPlugin->setApplication(Factory::getApplication());
                $akeebaAdminToolsPlugin->setDatabase($container->get(DatabaseInterface::class));

                return $akeebaAdminToolsPlugin;
            },
        );
    }
};
