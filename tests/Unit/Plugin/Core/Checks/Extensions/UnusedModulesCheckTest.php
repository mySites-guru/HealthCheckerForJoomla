<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\UnusedModulesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnusedModulesCheck::class)]
class UnusedModulesCheckTest extends TestCase
{
    private UnusedModulesCheck $check;

    protected function setUp(): void
    {
        $this->check = new UnusedModulesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.unused_modules', $this->check->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->check->getCategory());
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
        $this->assertStringContainsString('database', strtolower($result->description));
    }

    public function testRunWithNoUnusedModulesReturnsGood(): void
    {
        $database = $this->createDatabaseWithUnusedModules([], []);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('menu assignments', strtolower($result->description));
    }

    public function testRunWithFewUnusedModulesReturnsWarning(): void
    {
        $unusedModules = [
            (object) [
                'id' => 1,
                'title' => 'Unused Module 1',
            ],
            (object) [
                'id' => 2,
                'title' => 'Unused Module 2',
            ],
        ];
        $database = $this->createDatabaseWithUnusedModules($unusedModules, []);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2', $result->description);
    }

    public function testRunWithManyUnusedModulesReturnsWarningWithNames(): void
    {
        $unusedModules = [];

        for ($i = 1; $i <= 8; $i++) {
            $unusedModules[] = (object) [
                'id' => $i,
                'title' => "Unused Module {$i}",
            ];
        }

        $database = $this->createDatabaseWithUnusedModules($unusedModules, []);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Verify module names are included in warning message
        $this->assertStringContainsString('Unused Module 1', $result->description);
        $this->assertStringContainsString('Unused Module 8', $result->description);
    }

    public function testRunWithNoPageModulesReturnsWarning(): void
    {
        // Modules with no entries in modules_menu at all
        $noPageModules = [
            (object) [
                'id' => 1,
                'title' => 'No Page Module',
            ],
        ];
        $database = $this->createDatabaseWithUnusedModules([], $noPageModules);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithBothTypesOfUnusedModulesReturnsWarning(): void
    {
        $unusedModules = [
            (object) [
                'id' => 1,
                'title' => 'Menuid 0 Module',
            ],
        ];
        $noPageModules = [
            (object) [
                'id' => 2,
                'title' => 'No Page Module',
            ],
        ];
        $database = $this->createDatabaseWithUnusedModules($unusedModules, $noPageModules);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2', $result->description); // Total = 2
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testWarningMessageSuggestsUnpublishing(): void
    {
        $unusedModules = [
            (object) [
                'id' => 1,
                'title' => 'Unused Module',
            ],
        ];
        $database = $this->createDatabaseWithUnusedModules($unusedModules, []);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertStringContainsString('no menu assignment', strtolower($result->description));
    }

    /**
     * Create a mock database that returns unused modules
     */
    private function createDatabaseWithUnusedModules(array $unusedModules, array $noPageModules): DatabaseInterface
    {
        return new class ($unusedModules, $noPageModules) implements DatabaseInterface {
            private int $queryIndex = 0;

            public function __construct(
                private readonly array $unusedModules,
                private readonly array $noPageModules,
            ) {}

            public function getVersion(): string
            {
                return '8.0.30';
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
                // First call returns modules with menuid = 0
                // Second call returns modules with no entries in modules_menu
                $result = $this->queryIndex === 0 ? $this->unusedModules : $this->noPageModules;
                $this->queryIndex++;

                return $result;
            }

            public function execute(): bool
            {
                return true;
            }

            public function quoteName(array|string $name, ?string $as = null): array|string
            {
                return is_array($name) ? '' : $name;
            }

            public function quote(array|string $text, bool $escape = true): array|string
            {
                return is_string($text) ? "'{$text}'" : '';
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
