<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

\defined('_JEXEC') || die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Extension\HealthCheckerComponent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderRegistry;
use MySitesGuru\HealthChecker\Component\Administrator\Service\CategoryRegistry;
use MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\MySitesGuru\\HealthChecker\\Component'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\MySitesGuru\\HealthChecker\\Component'));

        $container->set(
            CategoryRegistry::class,
            fn(Container $container): \MySitesGuru\HealthChecker\Component\Administrator\Service\CategoryRegistry => new CategoryRegistry(),
        );

        $container->set(
            ProviderRegistry::class,
            fn(Container $container): \MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderRegistry => new ProviderRegistry(),
        );

        $container->set(
            HealthCheckRunner::class,
            fn(Container $container): \MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner => new HealthCheckRunner(
                $container->get(DispatcherInterface::class),
                $container->get(CategoryRegistry::class),
                $container->get(ProviderRegistry::class),
                $container->get(DatabaseInterface::class),
                $container->get(CacheControllerFactoryInterface::class),
            ),
        );

        $container->set(
            ComponentInterface::class,
            function (
                Container $container,
            ): \MySitesGuru\HealthChecker\Component\Administrator\Extension\HealthCheckerComponent {
                $healthCheckerComponent = new HealthCheckerComponent($container->get(
                    ComponentDispatcherFactoryInterface::class,
                ));
                $healthCheckerComponent->setMVCFactory($container->get(MVCFactoryInterface::class));
                $healthCheckerComponent->setHealthCheckRunner($container->get(HealthCheckRunner::class));

                return $healthCheckerComponent;
            },
        );
    }
};
