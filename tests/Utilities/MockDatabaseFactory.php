<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Utilities;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

/**
 * Factory for creating mock database objects for tests
 */
class MockDatabaseFactory
{
    /**
     * Create a mock database that returns a specific value from loadResult()
     */
    public static function createWithResult(mixed $result, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($result, $version) implements DatabaseInterface {
            public function __construct(
                private readonly mixed $result,
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
                return $this->result;
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
                return $this->buildMockQuery();
            }

            private function buildMockQuery(): QueryInterface
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
     * Create a mock database with only version for version checks
     */
    public static function createWithVersion(string $version): DatabaseInterface
    {
        return self::createWithResult(null, $version);
    }

    /**
     * Create a mock database that returns a specific array from loadColumn()
     */
    public static function createWithColumn(array $column, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($column, $version) implements DatabaseInterface {
            public function __construct(
                private readonly array $column,
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
                return $this->column;
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

    /**
     * Create a mock database that returns a specific array from loadObjectList()
     */
    public static function createWithObjectList(array $objectList, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($objectList, $version) implements DatabaseInterface {
            public function __construct(
                private readonly array $objectList,
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
                return $this->objectList;
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
     * Create a mock database that returns different values for sequential loadResult() calls
     *
     * @param array<mixed> $results
     */
    public static function createWithSequentialResults(array $results, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($results, $version) implements DatabaseInterface {
            private int $callCount = 0;

            /**
             * @param array<mixed> $results
             */
            public function __construct(
                private readonly array $results,
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
                return $this->results[$this->callCount++] ?? null;
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

    /**
     * Create a mock database that handles sequential queries with different method calls
     *
     * Each query in the array should have:
     * - 'method': The method name (loadResult, loadColumn, loadObject, loadObjectList, loadAssoc, loadAssocList)
     * - 'return': The value to return
     * - 'exception' (optional): Exception to throw instead
     *
     * @param array<array{method: string, return?: mixed, exception?: \Exception}> $queries
     */
    public static function createWithSequentialQueries(array $queries, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($queries, $version) implements DatabaseInterface {
            private int $callCount = 0;

            /**
             * @param array<array{method: string, return?: mixed, exception?: \Exception}> $queries
             */
            public function __construct(
                private readonly array $queries,
                private readonly string $version,
            ) {}

            private function getNextResult(string $method): mixed
            {
                if (! isset($this->queries[$this->callCount])) {
                    return match ($method) {
                        'loadColumn', 'loadObjectList', 'loadAssocList' => [],
                        default => null,
                    };
                }

                $query = $this->queries[$this->callCount];

                // Only consume if method matches
                if ($query['method'] === $method) {
                    $this->callCount++;

                    if (isset($query['exception'])) {
                        throw $query['exception'];
                    }

                    return $query['return'] ?? null;
                }

                // Method doesn't match, return default
                return match ($method) {
                    'loadColumn', 'loadObjectList', 'loadAssocList' => [],
                    default => null,
                };
            }

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
                return $this->getNextResult('loadResult');
            }

            public function loadColumn(): array
            {
                $result = $this->getNextResult('loadColumn');

                return is_array($result) ? $result : [];
            }

            public function loadAssoc(): ?array
            {
                $result = $this->getNextResult('loadAssoc');

                return is_array($result) ? $result : null;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                $result = $this->getNextResult('loadAssocList');

                return is_array($result) ? $result : [];
            }

            public function loadObject(): ?object
            {
                $result = $this->getNextResult('loadObject');

                return is_object($result) || $result === null ? $result : null;
            }

            public function loadObjectList(): array
            {
                $result = $this->getNextResult('loadObjectList');

                return is_array($result) ? $result : [];
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
     * Create a mock database that handles sequential queries with different method calls and a custom table list
     *
     * @param array<array{method: string, return?: mixed, exception?: \Exception}> $queries
     * @param array<string> $tableList
     */
    public static function createWithSequentialQueriesAndTableList(
        array $queries,
        array $tableList,
        string $version = '8.0.30',
    ): DatabaseInterface {
        return new class ($queries, $tableList, $version) implements DatabaseInterface {
            private int $callCount = 0;

            /**
             * @param array<array{method: string, return?: mixed, exception?: \Exception}> $queries
             * @param array<string> $tableList
             */
            public function __construct(
                private readonly array $queries,
                private readonly array $tableList,
                private readonly string $version,
            ) {}

            private function getNextResult(string $method): mixed
            {
                if (! isset($this->queries[$this->callCount])) {
                    return match ($method) {
                        'loadColumn', 'loadObjectList', 'loadAssocList' => [],
                        default => null,
                    };
                }

                $query = $this->queries[$this->callCount];

                // Only consume if method matches
                if ($query['method'] === $method) {
                    $this->callCount++;

                    if (isset($query['exception'])) {
                        throw $query['exception'];
                    }

                    return $query['return'] ?? null;
                }

                // Method doesn't match, return default
                return match ($method) {
                    'loadColumn', 'loadObjectList', 'loadAssocList' => [],
                    default => null,
                };
            }

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
                return $this->getNextResult('loadResult');
            }

            public function loadColumn(): array
            {
                $result = $this->getNextResult('loadColumn');

                return is_array($result) ? $result : [];
            }

            public function loadAssoc(): ?array
            {
                $result = $this->getNextResult('loadAssoc');

                return is_array($result) ? $result : null;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                $result = $this->getNextResult('loadAssocList');

                return is_array($result) ? $result : [];
            }

            public function loadObject(): ?object
            {
                $result = $this->getNextResult('loadObject');

                return is_object($result) || $result === null ? $result : null;
            }

            public function loadObjectList(): array
            {
                $result = $this->getNextResult('loadObjectList');

                return is_array($result) ? $result : [];
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
                return $this->tableList;
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
     * Create a mock database that throws an exception
     */
    public static function createWithException(\Exception $exception, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($exception, $version) implements DatabaseInterface {
            public function __construct(
                private readonly \Exception $exception,
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
                throw $this->exception;
            }

            public function loadColumn(): array
            {
                throw $this->exception;
            }

            public function loadAssoc(): ?array
            {
                throw $this->exception;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                throw $this->exception;
            }

            public function loadObject(): ?object
            {
                throw $this->exception;
            }

            public function loadObjectList(): array
            {
                throw $this->exception;
            }

            public function execute(): bool
            {
                throw $this->exception;
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
                throw $this->exception;
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
     * Create a mock database that returns a specific array from loadAssocList()
     */
    public static function createWithAssocList(array $assocList, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($assocList, $version) implements DatabaseInterface {
            public function __construct(
                private readonly array $assocList,
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
                return $this->assocList;
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
     * Create a mock database that returns a specific object from loadObject()
     */
    public static function createWithObject(?object $object, string $version = '8.0.30'): DatabaseInterface
    {
        return new class ($object, $version) implements DatabaseInterface {
            public function __construct(
                private readonly ?object $object,
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
                return $this->object;
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
