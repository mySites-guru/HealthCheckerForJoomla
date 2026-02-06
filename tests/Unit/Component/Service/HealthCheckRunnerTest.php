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
    private HealthCheckRunner $healthCheckRunner;

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

        $this->healthCheckRunner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );
    }

    public function testGetResultsReturnsEmptyArrayBeforeRun(): void
    {
        $this->assertSame([], $this->healthCheckRunner->getResults());
    }

    public function testGetLastRunReturnsNullBeforeRun(): void
    {
        $this->assertNull($this->healthCheckRunner->getLastRun());
    }

    public function testGetCriticalCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->healthCheckRunner->getCriticalCount());
    }

    public function testGetWarningCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->healthCheckRunner->getWarningCount());
    }

    public function testGetGoodCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->healthCheckRunner->getGoodCount());
    }

    public function testGetTotalCountReturnsZeroBeforeRun(): void
    {
        $this->assertSame(0, $this->healthCheckRunner->getTotalCount());
    }

    public function testGetCategoryRegistryReturnsInjectedRegistry(): void
    {
        $this->assertSame($this->categoryRegistry, $this->healthCheckRunner->getCategoryRegistry());
    }

    public function testGetProviderRegistryReturnsInjectedRegistry(): void
    {
        $this->assertSame($this->providerRegistry, $this->healthCheckRunner->getProviderRegistry());
    }

    public function testGetResultsByStatusReturnsStructuredArrayBeforeRun(): void
    {
        $results = $this->healthCheckRunner->getResultsByStatus();

        $this->assertArrayHasKey('critical', $results);
        $this->assertArrayHasKey('warning', $results);
        $this->assertArrayHasKey('good', $results);
        $this->assertSame([], $results['critical']);
        $this->assertSame([], $results['warning']);
        $this->assertSame([], $results['good']);
    }

    public function testGetResultsByCategoryReturnsEmptyArrayBeforeRun(): void
    {
        $this->assertSame([], $this->healthCheckRunner->getResultsByCategory());
    }

    public function testToArrayReturnsStructuredData(): void
    {
        $array = $this->healthCheckRunner->toArray();

        $this->assertArrayHasKey('lastRun', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('categories', $array);
        $this->assertArrayHasKey('providers', $array);
        $this->assertArrayHasKey('results', $array);
    }

    public function testToArraySummaryContainsAllCounts(): void
    {
        $array = $this->healthCheckRunner->toArray();

        $this->assertArrayHasKey('critical', $array['summary']);
        $this->assertArrayHasKey('warning', $array['summary']);
        $this->assertArrayHasKey('good', $array['summary']);
        $this->assertArrayHasKey('total', $array['summary']);
    }

    public function testRunSingleCheckReturnsNullForNonexistentSlug(): void
    {
        $result = $this->healthCheckRunner->runSingleCheck('nonexistent.check');

        $this->assertNull($result);
    }

    public function testRunCategoryReturnsEmptyArrayForNonexistentCategory(): void
    {
        $results = $this->healthCheckRunner->runCategory('nonexistent');

        $this->assertSame([], $results);
    }

    public function testCollectChecksReturnsEmptyArrayWhenNoPlugins(): void
    {
        $checks = $this->healthCheckRunner->collectChecks();

        $this->assertSame([], $checks);
    }

    public function testRunWithCacheCallsRunWhenCacheTtlIsNull(): void
    {
        // runWithCache with null TTL should behave like run()
        $this->healthCheckRunner->runWithCache();

        // After run, lastRun should be set
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->healthCheckRunner->getLastRun());
    }

    public function testRunWithCacheCallsRunWhenCacheTtlIsZero(): void
    {
        // runWithCache with 0 TTL should behave like run()
        $this->healthCheckRunner->runWithCache(0);

        // After run, lastRun should be set
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->healthCheckRunner->getLastRun());
    }

    public function testRunWithCacheCallsRunWhenCacheTtlIsNegative(): void
    {
        // runWithCache with negative TTL should behave like run()
        $this->healthCheckRunner->runWithCache(-1);

        // After run, lastRun should be set
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->healthCheckRunner->getLastRun());
    }

    public function testGetStatsWithCacheReturnsStructuredStats(): void
    {
        $stats = $this->healthCheckRunner->getStatsWithCache();

        $this->assertArrayHasKey('critical', $stats);
        $this->assertArrayHasKey('warning', $stats);
        $this->assertArrayHasKey('good', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('lastRun', $stats);
    }

    public function testInitializeDoesNotRunChecks(): void
    {
        $this->healthCheckRunner->initialize();

        // After initialize, results should still be empty (checks not run)
        $this->assertSame([], $this->healthCheckRunner->getResults());
        $this->assertNull($this->healthCheckRunner->getLastRun());
    }

    public function testClearCacheDoesNotThrow(): void
    {
        // Should not throw
        $this->healthCheckRunner->clearCache();

        $this->assertTrue(true);
    }

    public function testRunSetsLastRunTimestamp(): void
    {
        $before = new \DateTimeImmutable();
        $this->healthCheckRunner->run();
        $after = new \DateTimeImmutable();

        $lastRun = $this->healthCheckRunner->getLastRun();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lastRun);
        $this->assertGreaterThanOrEqual($before, $lastRun);
        $this->assertLessThanOrEqual($after, $lastRun);
    }

    public function testRunWithPositiveCacheTtl(): void
    {
        // Should run with cache enabled
        $this->healthCheckRunner->runWithCache(60);

        $this->assertInstanceOf(\DateTimeImmutable::class, $this->healthCheckRunner->getLastRun());
    }

    public function testGetStatsWithCacheWithPositiveTtl(): void
    {
        $stats = $this->healthCheckRunner->getStatsWithCache(60);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('critical', $stats);
    }

    public function testRunWithCacheReturnsCachedResultsOnCacheHit(): void
    {
        // Create a cache factory that returns cached data
        $cachedData = json_encode([
            'results' => [
                [
                    'slug' => 'test.cached_check',
                    'title' => 'Cached Check',
                    'category' => 'system',
                    'provider' => 'test',
                    'status' => 'good',
                    'description' => 'This is a cached result',
                ],
            ],
            'lastRun' => '2026-01-15T12:00:00+00:00',
        ]);

        $cacheFactory = new class ($cachedData) implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public function __construct(
                private readonly string $cachedData,
            ) {}

            public function createCacheController(string $type, array $options = []): mixed
            {
                $data = $this->cachedData;

                return new class ($data) {
                    public function __construct(
                        private readonly string $data,
                    ) {}

                    public function get(string $id): mixed
                    {
                        return $this->data;
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

        $healthCheckRunner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        $healthCheckRunner->runWithCache(300);

        // Verify results come from cache
        $results = $healthCheckRunner->getResults();
        $this->assertCount(1, $results);
        $this->assertSame('test.cached_check', $results[0]->slug);
        $this->assertSame('This is a cached result', $results[0]->description);

        // Verify lastRun comes from cache
        $lastRun = $healthCheckRunner->getLastRun();
        $this->assertInstanceOf(\DateTimeImmutable::class, $lastRun);
        $this->assertSame('2026-01-15', $lastRun->format('Y-m-d'));
    }

    public function testRunWithCacheRunsChecksOnCacheMiss(): void
    {
        // Create a cache factory that returns false (cache miss)
        $cacheFactory = new class implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public bool $storeWasCalled = false;

            public function createCacheController(string $type, array $options = []): mixed
            {
                $parent = $this;

                return new class ($parent) {
                    public function __construct(
                        private readonly object $parent,
                    ) {}

                    public function get(string $id): mixed
                    {
                        return false; // Cache miss
                    }

                    public function store(string $data, string $id): bool
                    {
                        $this->parent->storeWasCalled = true;

                        return true;
                    }

                    public function clean(): bool
                    {
                        return true;
                    }
                };
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        $healthCheckRunner->runWithCache(300);

        // Verify checks were run (indicated by lastRun being set)
        $this->assertInstanceOf(\DateTimeImmutable::class, $healthCheckRunner->getLastRun());
        // Verify store was called to cache the results
        $this->assertTrue($cacheFactory->storeWasCalled);
    }

    public function testRunWithCacheHandlesInvalidCacheData(): void
    {
        // Create a cache factory that returns invalid JSON
        $cacheFactory = new class implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public function createCacheController(string $type, array $options = []): mixed
            {
                return new class {
                    public function get(string $id): mixed
                    {
                        return 'invalid json{';
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

        $healthCheckRunner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        // Should not throw, should fall back to running checks
        $healthCheckRunner->runWithCache(300);

        $this->assertInstanceOf(\DateTimeImmutable::class, $healthCheckRunner->getLastRun());
    }

    public function testRunWithCacheHandlesMissingResultsKey(): void
    {
        // Create a cache factory that returns JSON without 'results' key
        $cachedData = json_encode([
            'lastRun' => '2026-01-15T12:00:00+00:00',
            // Missing 'results' key
        ]);

        $cacheFactory = new class ($cachedData) implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public function __construct(
                private readonly string $cachedData,
            ) {}

            public function createCacheController(string $type, array $options = []): mixed
            {
                $data = $this->cachedData;

                return new class ($data) {
                    public function __construct(
                        private readonly string $data,
                    ) {}

                    public function get(string $id): mixed
                    {
                        return $this->data;
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

        $healthCheckRunner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        // Should fall back to running checks when cached data is incomplete
        $healthCheckRunner->runWithCache(300);

        $this->assertInstanceOf(\DateTimeImmutable::class, $healthCheckRunner->getLastRun());
    }

    public function testGetStatsWithCacheWithPositiveTtlCallsRunWithCache(): void
    {
        // Create a cache factory that tracks whether cache methods are called
        $cacheFactory = new class implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public bool $getCalled = false;

            public function createCacheController(string $type, array $options = []): mixed
            {
                $parent = $this;

                return new class ($parent) {
                    public function __construct(
                        private readonly object $parent,
                    ) {}

                    public function get(string $id): mixed
                    {
                        $this->parent->getCalled = true;

                        return false; // Cache miss
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

        $healthCheckRunner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        $healthCheckRunner->getStatsWithCache(300);

        // Verify cache was checked (indicating runWithCache was called)
        $this->assertTrue($cacheFactory->getCalled);
    }

    public function testClearCacheCallsCacheClean(): void
    {
        // Create a cache factory that tracks whether clean was called
        $cacheFactory = new class implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public bool $cleanCalled = false;

            public function createCacheController(string $type, array $options = []): mixed
            {
                $parent = $this;

                return new class ($parent) {
                    public function __construct(
                        private readonly object $parent,
                    ) {}

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
                        $this->parent->cleanCalled = true;

                        return true;
                    }
                };
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $this->dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        $healthCheckRunner->clearCache();

        $this->assertTrue($cacheFactory->cleanCalled);
    }
}
