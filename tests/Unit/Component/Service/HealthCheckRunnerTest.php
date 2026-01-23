<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Service;

use Joomla\Database\DatabaseInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderRegistry;
use MySitesGuru\HealthChecker\Component\Administrator\Service\CategoryRegistry;
use MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthCheckRunner::class)]
class HealthCheckRunnerTest extends TestCase
{
    private HealthCheckRunner $runner;

    private object $dispatcher;

    private CategoryRegistry $categoryRegistry;

    private ProviderRegistry $providerRegistry;

    private DatabaseInterface $database;

    private object $cacheFactory;

    protected function setUp(): void
    {
        // Create a simple dispatcher that just returns events unchanged
        $this->dispatcher = new class implements \Joomla\Event\DispatcherInterface {
            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                return $event;
            }
        };

        // Create a simple cache factory
        $this->cacheFactory = new class implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public function createCacheController(string $type, array $options = []): mixed
            {
                return new class {
                    public function get(string $id): mixed
                    {
                        return false;
                    }

                    public function store(string $data, string $id): bool
                    {
                        return true;
                    }

                    public function clean(): bool
                    {
                        return true;
                    }
                };
            }
        };

        $this->categoryRegistry = new CategoryRegistry();
        $this->providerRegistry = new ProviderRegistry();
        $this->database = $this->createStub(DatabaseInterface::class);

        $this->runner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );
    }

    public function testGetResultsReturnsEmptyArrayBeforeRun(): void
    {
        $this->assertSame([], $this->runner->getResults());
    }

    public function testGetLastRunReturnsNullBeforeRun(): void
    {
        $this->assertNull($this->runner->getLastRun());
    }

    public function testGetCriticalCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->runner->getCriticalCount());
    }

    public function testGetWarningCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->runner->getWarningCount());
    }

    public function testGetGoodCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->runner->getGoodCount());
    }

    public function testGetTotalCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->runner->getTotalCount());
    }

    public function testGetCategoryRegistryReturnsInjectedRegistry(): void
    {
        $this->assertSame($this->categoryRegistry, $this->runner->getCategoryRegistry());
    }

    public function testGetProviderRegistryReturnsInjectedRegistry(): void
    {
        $this->assertSame($this->providerRegistry, $this->runner->getProviderRegistry());
    }

    public function testGetResultsByStatusReturnsStructuredArrayBeforeRun(): void
    {
        $results = $this->runner->getResultsByStatus();

        $this->assertArrayHasKey('critical', $results);
        $this->assertArrayHasKey('warning', $results);
        $this->assertArrayHasKey('good', $results);
        $this->assertSame([], $results['critical']);
        $this->assertSame([], $results['warning']);
        $this->assertSame([], $results['good']);
    }

    public function testGetResultsByCategoryReturnsEmptyArrayBeforeRun(): void
    {
        $this->assertSame([], $this->runner->getResultsByCategory());
    }

    public function testToArrayReturnsStructuredData(): void
    {
        $array = $this->runner->toArray();

        $this->assertArrayHasKey('lastRun', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('categories', $array);
        $this->assertArrayHasKey('providers', $array);
        $this->assertArrayHasKey('results', $array);
    }

    public function testToArraySummaryContainsAllCounts(): void
    {
        $array = $this->runner->toArray();

        $this->assertArrayHasKey('critical', $array['summary']);
        $this->assertArrayHasKey('warning', $array['summary']);
        $this->assertArrayHasKey('good', $array['summary']);
        $this->assertArrayHasKey('total', $array['summary']);
    }

    public function testRunSingleCheckReturnsNullForNonexistentSlug(): void
    {
        $result = $this->runner->runSingleCheck('nonexistent.check');

        $this->assertNull($result);
    }

    public function testRunCategoryReturnsEmptyArrayForNonexistentCategory(): void
    {
        $results = $this->runner->runCategory('nonexistent');

        $this->assertSame([], $results);
    }

    public function testCollectChecksReturnsEmptyArrayWhenNoPlugins(): void
    {
        $checks = $this->runner->collectChecks();

        $this->assertSame([], $checks);
    }

    public function testRunWithCacheCallsRunWhenCacheTtlIsNull(): void
    {
        // runWithCache with null TTL should behave like run()
        $this->runner->runWithCache(null);

        // After run, lastRun should be set
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->runner->getLastRun());
    }

    public function testRunWithCacheCallsRunWhenCacheTtlIsZero(): void
    {
        // runWithCache with 0 TTL should behave like run()
        $this->runner->runWithCache(0);

        // After run, lastRun should be set
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->runner->getLastRun());
    }

    public function testRunWithCacheCallsRunWhenCacheTtlIsNegative(): void
    {
        // runWithCache with negative TTL should behave like run()
        $this->runner->runWithCache(-1);

        // After run, lastRun should be set
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->runner->getLastRun());
    }

    public function testGetStatsWithCacheReturnsStructuredStats(): void
    {
        $stats = $this->runner->getStatsWithCache();

        $this->assertArrayHasKey('critical', $stats);
        $this->assertArrayHasKey('warning', $stats);
        $this->assertArrayHasKey('good', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('lastRun', $stats);
    }

    public function testInitializeDoesNotRunChecks(): void
    {
        $this->runner->initialize();

        // After initialize, results should still be empty (checks not run)
        $this->assertSame([], $this->runner->getResults());
        $this->assertNull($this->runner->getLastRun());
    }

    public function testClearCacheDoesNotThrow(): void
    {
        // Should not throw
        $this->runner->clearCache();

        $this->assertTrue(true);
    }

    public function testRunSetsLastRunTimestamp(): void
    {
        $before = new \DateTimeImmutable();
        $this->runner->run();
        $after = new \DateTimeImmutable();

        $lastRun = $this->runner->getLastRun();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lastRun);
        $this->assertGreaterThanOrEqual($before, $lastRun);
        $this->assertLessThanOrEqual($after, $lastRun);
    }

    public function testRunWithPositiveCacheTtl(): void
    {
        // Should run with cache enabled
        $this->runner->runWithCache(60);

        $this->assertInstanceOf(\DateTimeImmutable::class, $this->runner->getLastRun());
    }

    public function testGetStatsWithCacheWithPositiveTtl(): void
    {
        $stats = $this->runner->getStatsWithCache(60);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('critical', $stats);
    }
}
