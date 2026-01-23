<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Extension;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner;
use Psr\Container\ContainerInterface;

\defined('_JEXEC') || die;

/**
 * Health Checker Component Extension Class
 *
 * Main extension class for the Health Checker component. This class handles:
 * - Component bootstrapping and initialization
 * - Health check plugin discovery and loading
 * - Dependency injection container integration
 * - Service provider registration
 *
 * The component implements BootableExtensionInterface to ensure health checker
 * plugins are loaded early in the Joomla application lifecycle.
 *
 * @since 1.0.0
 */
class HealthCheckerComponent extends MVCComponent implements BootableExtensionInterface
{
    /**
     * The health check runner service instance
     *
     * Injected via the service provider during component initialization.
     * This instance is shared across all requests to the component.
     *
     * @since 1.0.0
     */
    private ?HealthCheckRunner $healthCheckRunner = null;

    /**
     * Boot the component
     *
     * Called by Joomla during component initialization. This method imports all
     * healthchecker plugins, allowing them to register their checks, categories,
     * and providers via event listeners.
     *
     * This is executed before any controller methods run, ensuring all health
     * checks are available when needed.
     *
     * @param   ContainerInterface  $container  The DI container
     *
     * @since   1.0.0
     */
    public function boot(ContainerInterface $container): void
    {
        PluginHelper::importPlugin('healthchecker');
    }

    /**
     * Set the health check runner instance
     *
     * Dependency injection method called by the service provider to inject the
     * configured HealthCheckRunner instance. This allows the runner to be properly
     * initialized with all required dependencies before use.
     *
     * @param   HealthCheckRunner  $healthCheckRunner  The configured runner instance
     *
     * @since   1.0.0
     */
    public function setHealthCheckRunner(HealthCheckRunner $healthCheckRunner): void
    {
        $this->healthCheckRunner = $healthCheckRunner;
    }

    /**
     * Get the health check runner instance
     *
     * Returns the injected HealthCheckRunner service. This instance is used by
     * controllers and models to execute health checks and retrieve results.
     *
     * @return  HealthCheckRunner  The health check runner service
     *
     * @since   1.0.0
     */
    public function getHealthCheckRunner(): HealthCheckRunner
    {
        if (! $this->healthCheckRunner instanceof \MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner) {
            throw new \RuntimeException('Health check runner has not been initialized');
        }

        return $this->healthCheckRunner;
    }
}
