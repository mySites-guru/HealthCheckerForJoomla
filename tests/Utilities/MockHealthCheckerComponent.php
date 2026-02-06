<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Utilities;

use Psr\Container\ContainerInterface;

/**
 * Mock HealthCheckerComponent for testing AjaxController
 *
 * This is a manual test double because HealthCheckerComponent::getHealthCheckRunner()
 * has a return type of HealthCheckRunner (final class), and PHPUnit cannot mock it.
 * This class bypasses the type system for testing purposes.
 */
class MockHealthCheckerComponent
{
    private ?MockHealthCheckRunner $mockHealthCheckRunner = null;

    public function setHealthCheckRunner(MockHealthCheckRunner $mockHealthCheckRunner): void
    {
        $this->mockHealthCheckRunner = $mockHealthCheckRunner;
    }

    /**
     * Get the health check runner - returns our mock instead of real one
     *
     * Note: No return type hint to allow returning MockHealthCheckRunner
     */
    public function getHealthCheckRunner(): \HealthChecker\Tests\Utilities\MockHealthCheckRunner
    {
        if (! $this->mockHealthCheckRunner instanceof \HealthChecker\Tests\Utilities\MockHealthCheckRunner) {
            throw new \RuntimeException('Health check runner has not been initialized');
        }

        return $this->mockHealthCheckRunner;
    }

    public function boot(ContainerInterface $container): void
    {
        // No-op in tests
    }
}
