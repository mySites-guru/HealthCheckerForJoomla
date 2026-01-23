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
use MySitesGuru\HealthChecker\Plugin\AkeebaBackup\Extension\AkeebaBackupPlugin;

\defined('_JEXEC') || die;

/**
 * Anonymous Service Provider Class
 *
 * Implements Joomla's ServiceProviderInterface to register the AkeebaBackupPlugin
 * with the dependency injection container. This anonymous class is returned from
 * this file and used by Joomla's plugin loader.
 *
 * @since 1.0.0
 */
return new class implements ServiceProviderInterface {
    /**
     * Register the plugin service with the DI container.
     *
     * Defines a factory closure that constructs the AkeebaBackupPlugin instance
     * with all required dependencies. The closure is called by the container
     * when the PluginInterface is requested for this plugin.
     *
     * REGISTRATION STEPS:
     * 1. Resolve DispatcherInterface from container
     * 2. Get plugin configuration from database via PluginHelper
     * 3. Instantiate AkeebaBackupPlugin with dispatcher and config
     * 4. Inject application instance via setApplication()
     * 5. Inject database connection via setDatabase()
     * 6. Return configured plugin instance
     *
     * The returned plugin instance automatically subscribes to health checker events
     * via its SubscriberInterface implementation.
     *
     * @param Container $container The DI container instance
     *
     * @since  1.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            /**
             * Factory closure for creating AkeebaBackupPlugin instances.
             *
             * This closure is executed by the DI container when the plugin is loaded.
             * It constructs the plugin with all dependencies properly injected.
             *
             * @param Container $container The DI container for resolving dependencies
             *
             * @return AkeebaBackupPlugin Fully configured plugin instance
             * @since  1.0.0
             */
            function (
                Container $container,
            ): \MySitesGuru\HealthChecker\Plugin\AkeebaBackup\Extension\AkeebaBackupPlugin {
                // Resolve event dispatcher from container
                $dispatcher = $container->get(DispatcherInterface::class);

                // Create plugin instance with dispatcher and configuration
                $akeebaBackupPlugin = new AkeebaBackupPlugin(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('healthchecker', 'akeebabackup'),
                );

                // Inject application dependency (for language, user access, etc.)
                $akeebaBackupPlugin->setApplication(Factory::getApplication());

                // Inject database dependency (for querying Akeeba tables)
                $akeebaBackupPlugin->setDatabase($container->get(DatabaseInterface::class));

                return $akeebaBackupPlugin;
            },
        );
    }
};
