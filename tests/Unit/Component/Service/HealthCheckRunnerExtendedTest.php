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
        $testCheck = $this->createConcreteTestCheck('test.check1', 'system', HealthStatus::Good, 'All is good');

        $dispatcher = new class ($testCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $check,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();

        $results = $runner->getResults();
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
                private AbstractHealthCheck $check,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();

        $this->assertTrue($abstractCheck->databaseWasSet);
    }

    public function testRunSingleCheckWithExistingSlug(): void
    {
        $testCheck = $this->createConcreteTestCheck('test.single', 'system', HealthStatus::Warning, 'Warning detected');

        $dispatcher = new class ($testCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $check,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $result = $runner->runSingleCheck('test.single');

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
                private AbstractHealthCheck $check,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->runSingleCheck('test.abstract_single');

        $this->assertTrue($abstractCheck->databaseWasSet);
    }

    public function testRunCategoryExecutesOnlyMatchingChecks(): void
    {
        $systemCheck = $this->createConcreteTestCheck('test.system_check', 'system', HealthStatus::Good, 'System OK');
        $securityCheck = $this->createConcreteTestCheck(
            'test.security_check',
            'security',
            HealthStatus::Warning,
            'Security issue',
        );

        $dispatcher = new class ($systemCheck, $securityCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $systemCheck,
                private HealthCheckInterface $securityCheck,
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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $results = $runner->runCategory('system');

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
                private HealthCheckInterface $check,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $results = $runner->runCategory('system');

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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $this->expectException(\RuntimeException::class);

        $runner->getMetadata();
    }

    public function testGetMetadataReturnsStructuredData(): void
    {
        $testCheck = $this->createConcreteTestCheck('test.metadata', 'system', HealthStatus::Good, 'OK');
        $testProvider = new ProviderMetadata('test', 'Test Provider', 'Test description');
        $testCategory = new HealthCategory('system', 'System', 'fa-server', 10);

        $dispatcher = new class (
            $testCheck,
            $testProvider,
            $testCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $check,
                private ProviderMetadata $provider,
                private HealthCategory $category,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                } elseif ($name === HealthCheckerEvents::COLLECT_PROVIDERS->value && $event instanceof CollectProvidersEvent) {
                    $event->addResult($this->provider);
                } elseif ($name === HealthCheckerEvents::COLLECT_CATEGORIES->value && $event instanceof CollectCategoriesEvent) {
                    $event->addResult($this->category);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $metadata = $runner->getMetadata();

        $this->assertArrayHasKey('categories', $metadata);
        $this->assertArrayHasKey('providers', $metadata);
        $this->assertArrayHasKey('checks', $metadata);
        $this->assertCount(1, $metadata['checks']);
        $this->assertSame('test.metadata', $metadata['checks'][0]['slug']);
    }

    public function testSortResultsSortsByStatusThenCategory(): void
    {
        $criticalCheck = $this->createConcreteTestCheck(
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
            $criticalCheck,
            $warningCheck,
            $goodCheck,
            $goodCheck2,
            $systemCategory,
            $databaseCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $criticalCheck,
                private HealthCheckInterface $warningCheck,
                private HealthCheckInterface $goodCheck,
                private HealthCheckInterface $goodCheck2,
                private HealthCategory $systemCategory,
                private HealthCategory $databaseCategory,
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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();
        $results = $runner->getResults();

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
        $systemCheck1 = $this->createConcreteTestCheck('test.system1', 'system', HealthStatus::Good, 'OK1');
        $systemCheck2 = $this->createConcreteTestCheck('test.system2', 'system', HealthStatus::Warning, 'Warn');
        $dbCheck = $this->createConcreteTestCheck('test.db', 'database', HealthStatus::Good, 'DB OK');

        $systemCategory = new HealthCategory('system', 'System', 'fa-server', 10);
        $databaseCategory = new HealthCategory('database', 'Database', 'fa-database', 20);

        $dispatcher = new class (
            $systemCheck1,
            $systemCheck2,
            $dbCheck,
            $systemCategory,
            $databaseCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $systemCheck1,
                private HealthCheckInterface $systemCheck2,
                private HealthCheckInterface $dbCheck,
                private HealthCategory $systemCategory,
                private HealthCategory $databaseCategory,
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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();
        $resultsByCategory = $runner->getResultsByCategory();

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
        $unknownCategoryCheck = $this->createConcreteTestCheck(
            'test.unknown',
            'unknown_category',
            HealthStatus::Good,
            'OK',
        );

        $dispatcher = new class ($unknownCategoryCheck) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $check,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();
        $resultsByCategory = $runner->getResultsByCategory();

        $this->assertArrayHasKey('unknown_category', $resultsByCategory);
    }

    public function testGetResultsByStatusGroupsCorrectly(): void
    {
        $criticalCheck = $this->createConcreteTestCheck('test.critical', 'system', HealthStatus::Critical, 'Critical');
        $warningCheck = $this->createConcreteTestCheck('test.warning', 'system', HealthStatus::Warning, 'Warning');
        $goodCheck = $this->createConcreteTestCheck('test.good', 'system', HealthStatus::Good, 'Good');

        $dispatcher = new class (
            $criticalCheck,
            $warningCheck,
            $goodCheck,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $criticalCheck,
                private HealthCheckInterface $warningCheck,
                private HealthCheckInterface $goodCheck,
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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();
        $resultsByStatus = $runner->getResultsByStatus();

        $this->assertCount(1, $resultsByStatus['critical']);
        $this->assertCount(1, $resultsByStatus['warning']);
        $this->assertCount(1, $resultsByStatus['good']);
    }

    public function testCountMethodsReturnCorrectValues(): void
    {
        $criticalCheck1 = $this->createConcreteTestCheck(
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
            $criticalCheck1,
            $criticalCheck2,
            $warningCheck,
            $goodCheck,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $criticalCheck1,
                private HealthCheckInterface $criticalCheck2,
                private HealthCheckInterface $warningCheck,
                private HealthCheckInterface $goodCheck,
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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();

        $this->assertSame(2, $runner->getCriticalCount());
        $this->assertSame(1, $runner->getWarningCount());
        $this->assertSame(1, $runner->getGoodCount());
        $this->assertSame(4, $runner->getTotalCount());
    }

    public function testToArrayIncludesAllData(): void
    {
        $testCheck = $this->createConcreteTestCheck('test.toarray', 'system', HealthStatus::Good, 'OK');
        $testProvider = new ProviderMetadata('test', 'Test Provider');
        $testCategory = new HealthCategory('system', 'System', 'fa-server', 10);

        $dispatcher = new class (
            $testCheck,
            $testProvider,
            $testCategory,
        ) implements \Joomla\Event\DispatcherInterface {
            public function __construct(
                private HealthCheckInterface $check,
                private ProviderMetadata $provider,
                private HealthCategory $category,
            ) {}

            public function dispatch(
                string $name,
                ?\Joomla\Event\EventInterface $event = null,
            ): \Joomla\Event\EventInterface {
                if ($name === HealthCheckerEvents::COLLECT_CHECKS->value && $event instanceof CollectChecksEvent) {
                    $event->addResult($this->check);
                } elseif ($name === HealthCheckerEvents::COLLECT_PROVIDERS->value && $event instanceof CollectProvidersEvent) {
                    $event->addResult($this->provider);
                } elseif ($name === HealthCheckerEvents::COLLECT_CATEGORIES->value && $event instanceof CollectCategoriesEvent) {
                    $event->addResult($this->category);
                }

                return $event;
            }
        };

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $runner->run();
        $array = $runner->toArray();

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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $cacheFactory,
        );

        $runner->runWithCache(300);

        $this->assertInstanceOf(\DateTimeImmutable::class, $runner->getLastRun());
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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $stats = $runner->getStatsWithCache(null);

        $this->assertArrayHasKey('critical', $stats);
        $this->assertArrayHasKey('warning', $stats);
        $this->assertArrayHasKey('good', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('lastRun', $stats);
        $this->assertInstanceOf(\DateTimeImmutable::class, $runner->getLastRun());
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

        $runner = new HealthCheckRunner(
            $dispatcher,
            $this->categoryRegistry,
            $this->providerRegistry,
            $this->database,
            $this->cacheFactory,
        );

        $stats = $runner->getStatsWithCache(0);

        $this->assertArrayHasKey('critical', $stats);
        $this->assertInstanceOf(\DateTimeImmutable::class, $runner->getLastRun());
    }

    private function createConcreteTestCheck(
        string $slug,
        string $category,
        HealthStatus $status,
        string $description,
    ): HealthCheckInterface {
        return new class ($slug, $category, $status, $description) implements HealthCheckInterface {
            public function __construct(
                private string $slug,
                private string $category,
                private HealthStatus $status,
                private string $description,
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
                    healthStatus: $this->status,
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
