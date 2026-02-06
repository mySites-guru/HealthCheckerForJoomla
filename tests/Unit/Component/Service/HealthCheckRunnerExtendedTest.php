<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Service;

use Joomla\Database\DatabaseInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderRegistry;
use MySitesGuru\HealthChecker\Component\Administrator\Service\CategoryRegistry;
use MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Extended tests for HealthCheckRunner to achieve 100% coverage
 */
#[CoversClass(HealthCheckRunner::class)]
class HealthCheckRunnerExtendedTest extends TestCase
{
    private CategoryRegistry $categoryRegistry;

    private ProviderRegistry $providerRegistry;

    private DatabaseInterface $database;

    private object $cacheFactory;

    protected function setUp(): void
    {
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
    }

    public function testRunWithChecksExecutesAndStoresResults(): void
    {
        $healthCheck = $this->createConcreteTestCheck('test.check1', 'system', HealthStatus::Good, 'All is good');

        $dispatcher = new class ($healthCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $healthCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $results = $healthCheckRunner->getResults();
        $this->assertCount(1, $results);
        $this->assertSame('test.check1', $results[0]->slug);
        $this->assertSame(HealthStatus::Good, $results[0]->healthStatus);
    }

    public function testRunInjectsDatabaseIntoAbstractHealthCheck(): void
    {
        $abstractCheck = new class extends AbstractHealthCheck {
            public bool $databaseWasSet = false;

            public function getSlug(): string
            {
                return 'test.abstract_check';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            public function setDatabase(DatabaseInterface $database): void
            {
                $this->databaseWasSet = true;
                parent::setDatabase($database);
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('Check passed');
            }
        };

        $dispatcher = new class ($abstractCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly AbstractHealthCheck $healthCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $this->assertTrue($abstractCheck->databaseWasSet);
    }

    public function testRunSingleCheckWithExistingSlug(): void
    {
        $healthCheck = $this->createConcreteTestCheck(
            'test.single',
            'system',
            HealthStatus::Warning,
            'Warning detected',
        );

        $dispatcher = new class ($healthCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $healthCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $result = $healthCheckRunner->runSingleCheck('test.single');

        $this->assertNotNull($result);
        $this->assertSame('test.single', $result->slug);
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunSingleCheckInjectsDatabaseIntoAbstractHealthCheck(): void
    {
        $abstractCheck = new class extends AbstractHealthCheck {
            public bool $databaseWasSet = false;

            public function getSlug(): string
            {
                return 'test.abstract_single';
            }

            public function getCategory(): string
            {
                return 'database';
            }

            public function setDatabase(DatabaseInterface $database): void
            {
                $this->databaseWasSet = true;
                parent::setDatabase($database);
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('Database check passed');
            }
        };

        $dispatcher = new class ($abstractCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly AbstractHealthCheck $healthCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->runSingleCheck('test.abstract_single');

        $this->assertTrue($abstractCheck->databaseWasSet);
    }

    public function testRunCategoryExecutesOnlyMatchingChecks(): void
    {
        $healthCheck = $this->createConcreteTestCheck('test.system_check', 'system', HealthStatus::Good, 'System OK');
        $securityCheck = $this->createConcreteTestCheck(
            'test.security_check',
            'security',
            HealthStatus::Warning,
            'Security issue',
        );

        $dispatcher = new class ($healthCheck, $securityCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $systemCheck,
                private readonly HealthCheckInterface $securityCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->systemCheck);
                    $event->addResult($this->securityCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $results = $healthCheckRunner->runCategory('system');

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('test.system_check', $results);
        $this->assertSame('good', $results['test.system_check']['status']);
    }

    public function testRunCategoryHandlesCheckException(): void
    {
        $failingCheck = new class implements HealthCheckInterface {
            public function getSlug(): string
            {
                return 'test.failing_check';
            }

            public function getTitle(): string
            {
                return 'Failing Check';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            public function getProvider(): string
            {
                return 'test';
            }

            public function run(): HealthCheckResult
            {
                throw new \RuntimeException('Check exploded!');
            }
        };

        $dispatcher = new class ($failingCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $healthCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $results = $healthCheckRunner->runCategory('system');

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('test.failing_check', $results);
        $this->assertSame('warning', $results['test.failing_check']['healthStatus']);
        $this->assertStringContainsString('Check exploded!', $results['test.failing_check']['description']);
    }

    public function testGetMetadataThrowsExceptionWhenNoChecksAvailable(): void
    {
        $dispatcher = new class implements \Joomla\Event\DispatcherInterface {
            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $this->expectException(\RuntimeException::class);

        $healthCheckRunner->getMetadata();
    }

    public function testGetMetadataReturnsStructuredData(): void
    {
        $healthCheck = $this->createConcreteTestCheck('test.metadata', 'system', HealthStatus::Good, 'OK');
        $providerMetadata = new ProviderMetadata('test', 'Test Provider', 'Test description');
        $healthCategory = new HealthCategory('system', 'System', 'fa-server', 10);

        $dispatcher = new class (
            $healthCheck,
            $providerMetadata,
            $healthCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $healthCheck,
                private readonly ProviderMetadata $providerMetadata,
                private readonly HealthCategory $healthCategory,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                } elseif ($name === HealthCheckerEvents::COLLECT_PROVIDERS->value && $event instanceof CollectProvidersEvent) {
                    $event->addResult($this->providerMetadata);
                } elseif ($name === HealthCheckerEvents::COLLECT_CATEGORIES->value && $event instanceof CollectCategoriesEvent) {
                    $event->addResult($this->healthCategory);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $metadata = $healthCheckRunner->getMetadata();

        $this->assertArrayHasKey('categories', $metadata);
        $this->assertArrayHasKey('providers', $metadata);
        $this->assertArrayHasKey('checks', $metadata);
        $this->assertCount(1, $metadata['checks']);
        $this->assertSame('test.metadata', $metadata['checks'][0]['slug']);
    }

    public function testSortResultsSortsByStatusThenCategory(): void
    {
        $healthCheck = $this->createConcreteTestCheck(
            'test.critical',
            'database',
            HealthStatus::Critical,
            'Critical',
        );
        $warningCheck = $this->createConcreteTestCheck('test.warning', 'system', HealthStatus::Warning, 'Warning');
        $goodCheck = $this->createConcreteTestCheck('test.good', 'system', HealthStatus::Good, 'Good');
        $goodCheck2 = $this->createConcreteTestCheck('test.good2', 'database', HealthStatus::Good, 'Good2');

        $systemCategory = new HealthCategory('system', 'System', 'fa-server', 10);
        $databaseCategory = new HealthCategory('database', 'Database', 'fa-database', 20);

        $dispatcher = new class (
            $healthCheck,
            $warningCheck,
            $goodCheck,
            $goodCheck2,
            $systemCategory,
            $databaseCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $criticalCheck,
                private readonly HealthCheckInterface $warningCheck,
                private readonly HealthCheckInterface $goodCheck,
                private readonly HealthCheckInterface $goodCheck2,
                private readonly HealthCategory $systemCategory,
                private readonly HealthCategory $databaseCategory,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->goodCheck);
                    $event->addResult($this->warningCheck);
                    $event->addResult($this->criticalCheck);
                    $event->addResult($this->goodCheck2);
                } elseif ($name === HealthCheckerEvents::COLLECT_CATEGORIES->value && $event instanceof CollectCategoriesEvent) {
                    $event->addResult($this->systemCategory);
                    $event->addResult($this->databaseCategory);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $results = $healthCheckRunner->getResults();

        $this->assertCount(4, $results);
        $this->assertSame(HealthStatus::Critical, $results[0]->healthStatus);
        $this->assertSame(HealthStatus::Warning, $results[1]->healthStatus);
        $this->assertSame(HealthStatus::Good, $results[2]->healthStatus);
        $this->assertSame(HealthStatus::Good, $results[3]->healthStatus);

        $this->assertSame('system', $results[2]->category);
        $this->assertSame('database', $results[3]->category);
    }

    public function testGetResultsByCategoryGroupsCorrectly(): void
    {
        $healthCheck = $this->createConcreteTestCheck('test.system1', 'system', HealthStatus::Good, 'OK1');
        $systemCheck2 = $this->createConcreteTestCheck('test.system2', 'system', HealthStatus::Warning, 'Warn');
        $dbCheck = $this->createConcreteTestCheck('test.db', 'database', HealthStatus::Good, 'DB OK');

        $systemCategory = new HealthCategory('system', 'System', 'fa-server', 10);
        $databaseCategory = new HealthCategory('database', 'Database', 'fa-database', 20);

        $dispatcher = new class (
            $healthCheck,
            $systemCheck2,
            $dbCheck,
            $systemCategory,
            $databaseCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $systemCheck1,
                private readonly HealthCheckInterface $systemCheck2,
                private readonly HealthCheckInterface $dbCheck,
                private readonly HealthCategory $systemCategory,
                private readonly HealthCategory $databaseCategory,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->systemCheck1);
                    $event->addResult($this->systemCheck2);
                    $event->addResult($this->dbCheck);
                } elseif ($name === HealthCheckerEvents::COLLECT_CATEGORIES->value && $event instanceof CollectCategoriesEvent) {
                    $event->addResult($this->systemCategory);
                    $event->addResult($this->databaseCategory);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $resultsByCategory = $healthCheckRunner->getResultsByCategory();

        $this->assertArrayHasKey('system', $resultsByCategory);
        $this->assertArrayHasKey('database', $resultsByCategory);
        $this->assertCount(2, $resultsByCategory['system']);
        $this->assertCount(1, $resultsByCategory['database']);

        $keys = array_keys($resultsByCategory);
        $this->assertSame('system', $keys[0]);
        $this->assertSame('database', $keys[1]);
    }

    public function testGetResultsByCategoryHandlesUnknownCategory(): void
    {
        $healthCheck = $this->createConcreteTestCheck(
            'test.unknown',
            'unknown_category',
            HealthStatus::Good,
            'OK',
        );

        $dispatcher = new class ($healthCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $healthCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $resultsByCategory = $healthCheckRunner->getResultsByCategory();

        $this->assertArrayHasKey('unknown_category', $resultsByCategory);
    }

    public function testGetResultsByStatusGroupsCorrectly(): void
    {
        $healthCheck = $this->createConcreteTestCheck('test.critical', 'system', HealthStatus::Critical, 'Critical');
        $warningCheck = $this->createConcreteTestCheck('test.warning', 'system', HealthStatus::Warning, 'Warning');
        $goodCheck = $this->createConcreteTestCheck('test.good', 'system', HealthStatus::Good, 'Good');

        $dispatcher = new class (
            $healthCheck,
            $warningCheck,
            $goodCheck,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $criticalCheck,
                private readonly HealthCheckInterface $warningCheck,
                private readonly HealthCheckInterface $goodCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->criticalCheck);
                    $event->addResult($this->warningCheck);
                    $event->addResult($this->goodCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $resultsByStatus = $healthCheckRunner->getResultsByStatus();

        $this->assertCount(1, $resultsByStatus['critical']);
        $this->assertCount(1, $resultsByStatus['warning']);
        $this->assertCount(1, $resultsByStatus['good']);
    }

    public function testCountMethodsReturnCorrectValues(): void
    {
        $healthCheck = $this->createConcreteTestCheck(
            'test.critical1',
            'system',
            HealthStatus::Critical,
            'Critical1',
        );
        $criticalCheck2 = $this->createConcreteTestCheck(
            'test.critical2',
            'system',
            HealthStatus::Critical,
            'Critical2',
        );
        $warningCheck = $this->createConcreteTestCheck('test.warning', 'system', HealthStatus::Warning, 'Warning');
        $goodCheck = $this->createConcreteTestCheck('test.good', 'system', HealthStatus::Good, 'Good');

        $dispatcher = new class (
            $healthCheck,
            $criticalCheck2,
            $warningCheck,
            $goodCheck,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $criticalCheck1,
                private readonly HealthCheckInterface $criticalCheck2,
                private readonly HealthCheckInterface $warningCheck,
                private readonly HealthCheckInterface $goodCheck,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->criticalCheck1);
                    $event->addResult($this->criticalCheck2);
                    $event->addResult($this->warningCheck);
                    $event->addResult($this->goodCheck);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $this->assertSame(2, $healthCheckRunner->getCriticalCount());
        $this->assertSame(1, $healthCheckRunner->getWarningCount());
        $this->assertSame(1, $healthCheckRunner->getGoodCount());
        $this->assertSame(4, $healthCheckRunner->getTotalCount());
    }

    public function testToArrayIncludesAllData(): void
    {
        $healthCheck = $this->createConcreteTestCheck('test.toarray', 'system', HealthStatus::Good, 'OK');
        $providerMetadata = new ProviderMetadata('test', 'Test Provider');
        $healthCategory = new HealthCategory('system', 'System', 'fa-server', 10);

        $dispatcher = new class (
            $healthCheck,
            $providerMetadata,
            $healthCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private readonly HealthCheckInterface $healthCheck,
                private readonly ProviderMetadata $providerMetadata,
                private readonly HealthCategory $healthCategory,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->healthCheck);
                } elseif ($name === HealthCheckerEvents::COLLECT_PROVIDERS->value && $event instanceof CollectProvidersEvent) {
                    $event->addResult($this->providerMetadata);
                } elseif ($name === HealthCheckerEvents::COLLECT_CATEGORIES->value && $event instanceof CollectCategoriesEvent) {
                    $event->addResult($this->healthCategory);
                }

                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $healthCheckRunner->run();

        $array = $healthCheckRunner->toArray();

        $this->assertNotNull($array['lastRun']);
        $this->assertSame(0, $array['summary']['critical']);
        $this->assertSame(0, $array['summary']['warning']);
        $this->assertSame(1, $array['summary']['good']);
        $this->assertSame(1, $array['summary']['total']);
        $this->assertCount(1, $array['categories']);
        // ProviderRegistry registers 'core' provider by default + our test provider = 2
        $this->assertCount(2, $array['providers']);
        $this->assertCount(1, $array['results']);
    }

    public function testRunWithCacheHandlesNullCacheData(): void
    {
        $dispatcher = new class implements \Joomla\Event\DispatcherInterface {
            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                return $event;
            }
        };

        $cacheFactory = new class implements \Joomla\CMS\Cache\CacheControllerFactoryInterface {
            public function createCacheController(string $type, array $options = []): mixed
            {
                return new class {
                    public function get(string $id): mixed
                    {
                        return null;
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
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        $healthCheckRunner->runWithCache(300);

        $this->assertInstanceOf(\DateTimeImmutable::class, $healthCheckRunner->getLastRun());
    }

    public function testGetStatsWithCacheWithNullTtl(): void
    {
        $dispatcher = new class implements \Joomla\Event\DispatcherInterface {
            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $stats = $healthCheckRunner->getStatsWithCache();

        $this->assertArrayHasKey('critical', $stats);
        $this->assertArrayHasKey('warning', $stats);
        $this->assertArrayHasKey('good', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('lastRun', $stats);
        $this->assertInstanceOf(\DateTimeImmutable::class, $healthCheckRunner->getLastRun());
    }

    public function testGetStatsWithCacheWithZeroTtl(): void
    {
        $dispatcher = new class implements \Joomla\Event\DispatcherInterface {
            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                return $event;
            }
        };

        $healthCheckRunner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $stats = $healthCheckRunner->getStatsWithCache(0);

        $this->assertArrayHasKey('critical', $stats);
        $this->assertInstanceOf(\DateTimeImmutable::class, $healthCheckRunner->getLastRun());
    }

    private function createConcreteTestCheck(
        string $slug,
        string $category,
        HealthStatus $healthStatus,
        string $description,
    ): HealthCheckInterface {
        return new class ($slug, $category, $healthStatus, $description) implements HealthCheckInterface {
            public function __construct(
                private readonly string $slug,
                private readonly string $category,
                private readonly HealthStatus $healthStatus,
                private readonly string $description,
            ) {}

            public function getSlug(): string
            {
                return $this->slug;
            }

            public function getTitle(): string
            {
                return 'Test Check: ' . $this->slug;
            }

            public function getCategory(): string
            {
                return $this->category;
            }

            public function getProvider(): string
            {
                return 'test';
            }

            public function run(): HealthCheckResult
            {
                return new HealthCheckResult(
                    healthStatus: $this->healthStatus,
                    title: $this->getTitle(),
                    description: $this->description,
                    slug: $this->slug,
                    category: $this->category,
                    provider: $this->getProvider(),
                );
            }
        };
    }
}
