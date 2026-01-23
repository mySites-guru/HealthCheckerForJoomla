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
    private SlowQueryCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        $this->check = new SlowQueryCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.slow_query', $this->check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenSlowQueryLogDisabled(): void
    {
        // Mock returns 0 for loadResult, meaning slow query log is disabled
        $database = MockDatabaseFactory::createWithResult('0');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsGoodWhenSlowQueryLogDisabledWithOffString(): void
    {
        $database = MockDatabaseFactory::createWithResult('OFF');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsWarningWhenEnabledWithDebugMode(): void
    {
        // Enable debug mode in the app
        $this->app->set('debug', true);

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
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
        $this->assertStringContainsString('10 seconds', $result->description);
        $this->assertStringContainsString('acceptable during active debugging', $result->description);
    }

    public function testRunReturnsCriticalWhenEnabledWithoutDebugMode(): void
    {
        // Debug mode off (production)
        $this->app->set('debug', false);

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
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
        $this->assertStringContainsString('2 seconds', $result->description);
        $this->assertStringContainsString('Disable slow query logging in production', $result->description);
    }

    public function testRunIncludesSlowQueryCountWhenAvailable(): void
    {
        $this->app->set('debug', false);

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
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('42 slow queries recorded', $result->description);
    }

    public function testRunHandlesZeroSlowQueryCount(): void
    {
        $this->app->set('debug', true);

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
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Zero slow queries should not be mentioned
        $this->assertStringNotContainsString('slow queries recorded', $result->description);
    }

    public function testRunHandlesExceptionGracefully(): void
    {
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Connection failed'));
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Unable to check slow query log status', $result->description);
        $this->assertStringContainsString('Connection failed', $result->description);
    }

    public function testRunContinuesWhenSlowQueryCountFails(): void
    {
        // Test that exceptions during SHOW GLOBAL STATUS are caught and the check continues
        $this->app->set('debug', true);

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
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Should still return Warning, just without the slow query count
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
        $this->assertStringContainsString('5 seconds', $result->description);
        // Should NOT contain slow query count since the query failed
        $this->assertStringNotContainsString('slow queries recorded', $result->description);
    }
}
