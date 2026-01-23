<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace Joomla\Database;

interface DatabaseInterface
{
    public function getQuery(bool $new = false): QueryInterface;

    public function setQuery(QueryInterface|string $query, int $offset = 0, int $limit = 0): self;

    public function loadResult(): mixed;

    public function loadColumn(): array;

    public function loadAssoc(): ?array;

    public function loadAssocList(string $key = '', string $column = ''): array;

    public function loadObject(): ?object;

    public function loadObjectList(): array;

    public function execute(): bool;

    public function quoteName(array|string $name, ?string $as = null): array|string;

    public function quote(array|string $text, bool $escape = true): array|string;

    public function getPrefix(): string;
}

interface QueryInterface
{
    public function select(array|string $columns): self;

    public function from(string $table, ?string $alias = null): self;

    public function where(array|string $conditions): self;

    public function join(string $type, string $table, string $condition = ''): self;

    public function leftJoin(string $table, string $condition = ''): self;

    public function innerJoin(string $table, string $condition = ''): self;

    public function order(array|string $columns): self;

    public function group(array|string $columns): self;

    public function having(array|string $conditions): self;

    public function __toString(): string;
}

trait DatabaseAwareTrait
{
    protected ?DatabaseInterface $db = null;

    public function setDatabase(DatabaseInterface $database): void
    {
        $this->db = $database;
    }

    /**
     * Get the database driver.
     *
     * @return DatabaseInterface Always returns non-null (throws if not set at runtime)
     *
     * @phpstan-return DatabaseInterface
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->db ?? new class implements DatabaseInterface {
            public function getQuery(bool $new = false): QueryInterface
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

            public function setQuery(QueryInterface|string $query): self
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

            public function loadAssocList(): array
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
                return false;
            }

            public function quoteName(array|string $name, ?string $as = null): array|string
            {
                return '';
            }

            public function quote(array|string $text, bool $escape = true): array|string
            {
                return '';
            }

            public function getPrefix(): string
            {
                return '';
            }
        };
    }
}
