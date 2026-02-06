<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\DatabaseQueryCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseQueryCacheCheck::class)]
class DatabaseQueryCacheCheckTest extends TestCase
{
    private DatabaseQueryCacheCheck $databaseQueryCacheCheck;

    protected function setUp(): void
    {
        $this->databaseQueryCacheCheck = new DatabaseQueryCacheCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.database_query_cache', $this->databaseQueryCacheCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->databaseQueryCacheCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->databaseQueryCacheCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->databaseQueryCacheCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('database', strtolower($healthCheckResult->description));
    }

    public function testRunWithMysql8ReturnsGoodQueryCacheNotAvailable(): void
    {
        $database = MockDatabaseFactory::createWithResult(null, '8.0.30');
        $this->databaseQueryCacheCheck->setDatabase($database);

        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('MySQL 8.0+', $healthCheckResult->description);
    }

    public function testRunWithMariaDbAndCacheDisabledReturnsWarning(): void
    {
        $database = $this->createMariaDbWithCacheStatus('OFF', 0);
        $this->databaseQueryCacheCheck->setDatabase($database);

        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', strtolower($healthCheckResult->description));
    }

    public function testRunWithMariaDbAndCacheEnabledWithSizeReturnsGood(): void
    {
        $cacheSize = 16 * 1024 * 1024; // 16 MB
        $database = $this->createMariaDbWithCacheStatus('ON', $cacheSize);
        $this->databaseQueryCacheCheck->setDatabase($database);

        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('16', $healthCheckResult->description);
    }

    public function testRunWithCacheTypeOnButSizeZeroReturnsWarning(): void
    {
        $database = $this->createMariaDbWithCacheStatus('ON', 0);
        $this->databaseQueryCacheCheck->setDatabase($database);

        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('size is 0', $healthCheckResult->description);
    }

    public function testRunWithMysql57AndCacheDisabledReturnsGood(): void
    {
        $database = $this->createMysqlWithCacheStatus('5.7.40', 'OFF', 0);
        $this->databaseQueryCacheCheck->setDatabase($database);

        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('MySQL 5.7', $healthCheckResult->description);
    }

    public function testRunWithEmptyVersionReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(null, '');
        $this->databaseQueryCacheCheck->setDatabase($database);

        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('version', strtolower($healthCheckResult->description));
    }

    public function testRunWithEmptyQueryCacheVariablesReturnsGood(): void
    {
        $database = $this->createDatabaseWithEmptyCacheVariables('5.6.50');
        $this->databaseQueryCacheCheck->setDatabase($database);

        $healthCheckResult = $this->databaseQueryCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not available', strtolower($healthCheckResult->description));
    }

    /**
     * Create a mock database that simulates MariaDB with query cache settings
     */
    private function createMariaDbWithCacheStatus(string $cacheType, int $cacheSize): DatabaseInterface
    {
        return new class ($cacheType, $cacheSize) implements DatabaseInterface {
            public function __construct(
                private readonly string $cacheType,
                private readonly int $cacheSize,
            ) {}

            public function getVersion(): string
            {
                return '10.6.12-MariaDB';
            }

            public function getQuery(bool $new = false): QueryInterface
            {
                return $this->createMockQuery();
            }

            public function setQuery(QueryInterface|string $query, int $offset = 0, int $limit = 0): self
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                return null;
            }

            public function loadColumn(): array
            {
                return [];
            }

            public function loadAssoc(): ?array
            {
                return null;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                return [
                    'query_cache_type' => $this->cacheType,
                    'query_cache_size' => (string) $this->cacheSize,
                ];
            }

            public function loadObject(): ?object
            {
                return null;
            }

            public function loadObjectList(): array
            {
                return [];
            }

            public function execute(): bool
            {
                return true;
            }

            public function quoteName(array|string $name, ?string $as = null): string
            {
                return is_array($name) ? '' : $name;
            }

            public function quote(array|string $text, bool $escape = true): string
            {
                return is_string($text) ? sprintf("'%s'", $text) : '';
            }

            public function getPrefix(): string
            {
                return '#__';
            }

            public function getNullDate(): string
            {
                return '0000-00-00 00:00:00';
            }

            public function getTableList(): array
            {
                return [];
            }

            private function createMockQuery(): QueryInterface
            {
                return new class implements QueryInterface {
                    public function select(array|string $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table, ?string $alias = null): self
                    {
                        return $this;
                    }

                    public function where(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function join(string $type, string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function leftJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function innerJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function order(array|string $columns): self
                    {
                        return $this;
                    }

                    public function group(array|string $columns): self
                    {
                        return $this;
                    }

                    public function having(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }
                };
            }
        };
    }

    /**
     * Create a mock database that simulates MySQL with query cache settings
     */
    private function createMysqlWithCacheStatus(string $version, string $cacheType, int $cacheSize): DatabaseInterface
    {
        return new class ($version, $cacheType, $cacheSize) implements DatabaseInterface {
            public function __construct(
                private readonly string $version,
                private readonly string $cacheType,
                private readonly int $cacheSize,
            ) {}

            public function getVersion(): string
            {
                return $this->version;
            }

            public function getQuery(bool $new = false): QueryInterface
            {
                return $this->createMockQuery();
            }

            public function setQuery(QueryInterface|string $query, int $offset = 0, int $limit = 0): self
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                return null;
            }

            public function loadColumn(): array
            {
                return [];
            }

            public function loadAssoc(): ?array
            {
                return null;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                return [
                    'query_cache_type' => $this->cacheType,
                    'query_cache_size' => (string) $this->cacheSize,
                ];
            }

            public function loadObject(): ?object
            {
                return null;
            }

            public function loadObjectList(): array
            {
                return [];
            }

            public function execute(): bool
            {
                return true;
            }

            public function quoteName(array|string $name, ?string $as = null): string
            {
                return is_array($name) ? '' : $name;
            }

            public function quote(array|string $text, bool $escape = true): string
            {
                return is_string($text) ? sprintf("'%s'", $text) : '';
            }

            public function getPrefix(): string
            {
                return '#__';
            }

            public function getNullDate(): string
            {
                return '0000-00-00 00:00:00';
            }

            public function getTableList(): array
            {
                return [];
            }

            private function createMockQuery(): QueryInterface
            {
                return new class implements QueryInterface {
                    public function select(array|string $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table, ?string $alias = null): self
                    {
                        return $this;
                    }

                    public function where(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function join(string $type, string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function leftJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function innerJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function order(array|string $columns): self
                    {
                        return $this;
                    }

                    public function group(array|string $columns): self
                    {
                        return $this;
                    }

                    public function having(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }
                };
            }
        };
    }

    /**
     * Create a mock database that returns empty query cache variables
     */
    private function createDatabaseWithEmptyCacheVariables(string $version): DatabaseInterface
    {
        return new class ($version) implements DatabaseInterface {
            public function __construct(
                private readonly string $version,
            ) {}

            public function getVersion(): string
            {
                return $this->version;
            }

            public function getQuery(bool $new = false): QueryInterface
            {
                return $this->createMockQuery();
            }

            public function setQuery(QueryInterface|string $query, int $offset = 0, int $limit = 0): self
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                return null;
            }

            public function loadColumn(): array
            {
                return [];
            }

            public function loadAssoc(): ?array
            {
                return null;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                return [];
            }

            public function loadObject(): ?object
            {
                return null;
            }

            public function loadObjectList(): array
            {
                return [];
            }

            public function execute(): bool
            {
                return true;
            }

            public function quoteName(array|string $name, ?string $as = null): string
            {
                return is_array($name) ? '' : $name;
            }

            public function quote(array|string $text, bool $escape = true): string
            {
                return is_string($text) ? sprintf("'%s'", $text) : '';
            }

            public function getPrefix(): string
            {
                return '#__';
            }

            public function getNullDate(): string
            {
                return '0000-00-00 00:00:00';
            }

            public function getTableList(): array
            {
                return [];
            }

            private function createMockQuery(): QueryInterface
            {
                return new class implements QueryInterface {
                    public function select(array|string $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table, ?string $alias = null): self
                    {
                        return $this;
                    }

                    public function where(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function join(string $type, string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function leftJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function innerJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function order(array|string $columns): self
                    {
                        return $this;
                    }

                    public function group(array|string $columns): self
                    {
                        return $this;
                    }

                    public function having(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }
                };
            }
        };
    }
}
