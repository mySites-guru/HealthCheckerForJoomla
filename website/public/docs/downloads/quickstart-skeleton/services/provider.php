<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  HealthChecker.MyPlugin
 *
 * @copyright   (C) 2026 Your Company
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use YourCompany\Plugin\HealthChecker\MyPlugin\Extension\MyPluginPlugin;

return new class implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new MyPluginPlugin(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('healthchecker', 'myplugin')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase(Factory::getContainer()->get('DatabaseDriver'));

                return $plugin;
            }
        );
    }
};
