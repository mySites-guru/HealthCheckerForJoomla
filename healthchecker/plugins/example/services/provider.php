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
use MySitesGuru\HealthChecker\Plugin\Example\Extension\ExamplePlugin;

\defined('_JEXEC') || die;

/**
 * Anonymous class implementing the service provider interface.
 *
 * DEVELOPER NOTES:
 * - This is an anonymous class (no name) that implements ServiceProviderInterface
 * - It's instantiated and returned immediately when this file is included
 * - Joomla calls register() during plugin loading
 * - The Container parameter provides access to all registered services
 */
return new class implements ServiceProviderInterface {
    /**
     * Registers the plugin with the DI container.
     *
     * This method is called by Joomla during plugin loading. It defines how to
     * create an instance of your plugin and what dependencies to inject.
     *
     * DEPENDENCY INJECTION EXPLAINED:
     * - DispatcherInterface: Symfony Event Dispatcher for event subscription
     * - DatabaseInterface: Database connection for database-driven checks
     * - Factory::getApplication(): Joomla application instance
     * - PluginHelper::getPlugin(): Plugin configuration from database
     *
     * REQUIRED DEPENDENCIES:
     * - $dispatcher: ALWAYS needed (event system)
     * - $config: ALWAYS needed (plugin settings)
     *
     * OPTIONAL DEPENDENCIES:
     * - Database: Only if your checks query the database
     * - Application: If you need CMS features like routing, session, etc.
     *
     * TO CUSTOMIZE:
     * 1. Replace 'ExamplePlugin' with your plugin class name
     * 2. Update 'healthchecker' and 'example' in getPlugin() call
     * 3. Add additional dependency injections if needed:
     *    $examplePlugin->setSomeService($container->get(SomeServiceInterface::class));
     *
     * @param   Container  $container  The DI container instance
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        // Register the plugin in the container under the PluginInterface key
        $container->set(
            PluginInterface::class,
            /**
             * Factory function that creates and configures the plugin instance.
             *
             * @param   Container  $container  The DI container
             *
             * @return  ExamplePlugin  Fully configured plugin instance
             */
            function (Container $container): \MySitesGuru\HealthChecker\Plugin\Example\Extension\ExamplePlugin {
                // Get the event dispatcher from the container
                // This is required for the event subscription system to work
                $dispatcher = $container->get(DispatcherInterface::class);

                // Create the plugin instance with required dependencies
                // - $dispatcher: Event dispatcher for SubscriberInterface
                // - $config: Plugin configuration from #__extensions table
                $examplePlugin = new ExamplePlugin(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('healthchecker', 'example'),
                );

                // Inject the Joomla application instance
                // Provides access to: input, session, language, user, etc.
                $examplePlugin->setApplication(Factory::getApplication());

                // Inject the database connection
                // Required if your health checks perform database queries
                // Uses DatabaseAwareTrait in the plugin class
                $examplePlugin->setDatabase($container->get(DatabaseInterface::class));

                // Return the fully configured plugin instance to Joomla
                return $examplePlugin;
            },
        );
    }
};
