<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\SlowQueryCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SlowQueryCheck::class)]
class SlowQueryCheckTest extends TestCase
{
    private SlowQueryCheck $slowQueryCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->slowQueryCheck = new SlowQueryCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.slow_query', $this->slowQueryCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->slowQueryCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->slowQueryCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->slowQueryCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenSlowQueryLogDisabled(): void
    {
        // Mock returns 0 for loadResult, meaning slow query log is disabled
        $database = MockDatabaseFactory::createWithResult('0');
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSlowQueryLogDisabledWithOffString(): void
    {
        $database = MockDatabaseFactory::createWithResult('OFF');
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenEnabledWithDebugMode(): void
    {
        // Enable debug mode in the app
        $this->cmsApplication->set('debug', true);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => '1',
            ], // @@slow_query_log = enabled
            [
                'method' => 'loadResult',
                'return' => '10',
            ], // @@long_query_time = 10 seconds
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'Variable_name' => 'Slow_queries',
                    'Value' => '5',
                ],
            ],
        ]);
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
        $this->assertStringContainsString('10 seconds', $healthCheckResult->description);
        $this->assertStringContainsString('acceptable during active debugging', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenEnabledWithoutDebugMode(): void
    {
        // Debug mode off (production)
        $this->cmsApplication->set('debug', false);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => '1',
            ],
            [
                'method' => 'loadResult',
                'return' => '2',
            ], // 2 second threshold
            [
                'method' => 'loadObject',
                'return' => null,
            ], // No slow query count available
        ]);
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
        $this->assertStringContainsString('2 seconds', $healthCheckResult->description);
        $this->assertStringContainsString('Disable slow query logging in production', $healthCheckResult->description);
    }

    public function testRunIncludesSlowQueryCountWhenAvailable(): void
    {
        $this->cmsApplication->set('debug', false);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 'ON',
            ], // Can also be 'ON' string
            [
                'method' => 'loadResult',
                'return' => '5',
            ],
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'Variable_name' => 'Slow_queries',
                    'Value' => '42',
                ],
            ],
        ]);
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('42 slow queries recorded', $healthCheckResult->description);
    }

    public function testRunHandlesZeroSlowQueryCount(): void
    {
        $this->cmsApplication->set('debug', true);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 1,
            ], // Integer 1 also works
            [
                'method' => 'loadResult',
                'return' => '10',
            ],
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'Variable_name' => 'Slow_queries',
                    'Value' => '0',
                ],
            ],
        ]);
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // Zero slow queries should not be mentioned
        $this->assertStringNotContainsString('slow queries recorded', $healthCheckResult->description);
    }

    public function testRunHandlesExceptionGracefully(): void
    {
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Connection failed'));
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to check slow query log status', $healthCheckResult->description);
        $this->assertStringContainsString('Connection failed', $healthCheckResult->description);
    }

    public function testRunContinuesWhenSlowQueryCountFails(): void
    {
        // Test that exceptions during SHOW GLOBAL STATUS are caught and the check continues
        $this->cmsApplication->set('debug', true);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => '1',
            ], // @@slow_query_log = enabled
            [
                'method' => 'loadResult',
                'return' => '5',
            ], // @@long_query_time = 5 seconds
            [
                'method' => 'loadObject',
                'exception' => new \Exception('Access denied for SHOW GLOBAL STATUS'),
            ], // Exception when getting slow query count
        ]);
        $this->slowQueryCheck->setDatabase($database);

        $healthCheckResult = $this->slowQueryCheck->run();

        // Should still return Warning, just without the slow query count
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
        $this->assertStringContainsString('5 seconds', $healthCheckResult->description);
        // Should NOT contain slow query count since the query failed
        $this->assertStringNotContainsString('slow queries recorded', $healthCheckResult->description);
    }
}
