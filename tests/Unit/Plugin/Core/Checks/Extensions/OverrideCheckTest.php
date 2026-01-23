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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\OverrideCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OverrideCheck::class)]
class OverrideCheckTest extends TestCase
{
    private OverrideCheck $check;

    protected function setUp(): void
    {
        $this->check = new OverrideCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.overrides', $this->check->getSlug());
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

    public function testRunWithNoOverridesTableReturnsGood(): void
    {
        $database = $this->createDatabaseWithoutOverridesTable();
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not available', strtolower($result->description));
    }

    public function testRunWithNoOutdatedOverridesReturnsGood(): void
    {
        $database = $this->createDatabaseWithOverrides([], 5);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('up to date', strtolower($result->description));
    }

    public function testRunWithOutdatedOverridesReturnsWarning(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('com_content/article/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('cassiopeia', strtolower($result->description));
    }

    public function testRunWithMultipleOutdatedOverridesReturnsWarning(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('com_content/article/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ],
            (object) [
                'template' => 'atum',
                'hash_id' => base64_encode('mod_menu/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-02 12:00:00',
                'client_id' => 1,
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2', $result->description);
    }

    public function testRunWithAdminTemplateOverridesShowsCorrectLabel(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'atum',
                'hash_id' => base64_encode('mod_menu/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 1, // Admin
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('admin', strtolower($result->description));
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testWarningMessageContainsInstructions(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('com_content/article/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertStringContainsString('templates', strtolower($result->description));
    }

    public function testRunWithSiteTemplateOverridesShowsSiteLabel(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('mod_menu/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0, // Site (frontend)
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('site', strtolower($result->description));
    }

    public function testRunWithMoreThan10OverridesShowsTruncatedMessage(): void
    {
        // Create more than MAX_DETAILS_TO_SHOW (10) overrides
        $outdatedOverrides = [];

        for ($i = 1; $i <= 15; $i++) {
            $outdatedOverrides[] = (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode("mod_file{$i}/default.php"),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ];
        }
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 20);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('15', $result->description);
        // Should show "and X more" for truncated output
        $this->assertStringContainsString('and 5 more', $result->description);
    }

    public function testRunWithInvalidBase64HashIdSkipsEntry(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => 'not-valid-base64!!!',
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ],
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('valid/path.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Should still mention 2 overrides but only show 1 valid one in details
        $this->assertStringContainsString('2 template override', $result->description);
        $this->assertStringContainsString('valid/path.php', $result->description);
    }

    public function testRunWithMultipleTemplatesGroupsByTemplate(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('com_content/article/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ],
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('mod_menu/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-02 12:00:00',
                'client_id' => 0,
            ],
            (object) [
                'template' => 'atum',
                'hash_id' => base64_encode('mod_login/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-03 12:00:00',
                'client_id' => 1,
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('3', $result->description);
        // Check templates are mentioned
        $this->assertStringContainsString('cassiopeia', strtolower($result->description));
        $this->assertStringContainsString('atum', strtolower($result->description));
    }

    public function testRunWithLeadingSlashInPathRemovesIt(): void
    {
        $outdatedOverrides = [
            (object) [
                'template' => 'cassiopeia',
                'hash_id' => base64_encode('/com_content/article/default.php'),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ],
        ];
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Should show path without leading slash
        $this->assertStringContainsString('com_content/article/default.php', $result->description);
        // Should NOT have double slashes
        $this->assertStringNotContainsString('//com_content', $result->description);
    }

    public function testRunWithZeroTotalOverridesReturnsGoodWithZeroCount(): void
    {
        $database = $this->createDatabaseWithOverrides([], 0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('0 template override(s) tracked', $result->description);
    }

    public function testRunWithMoreThan10TemplatesBreaksOuterLoop(): void
    {
        // Create more than MAX_DETAILS_TO_SHOW (10) overrides, each from a different template
        // This tests the outer loop break (line 184) rather than the inner loop break
        $outdatedOverrides = [];

        for ($i = 1; $i <= 15; $i++) {
            $outdatedOverrides[] = (object) [
                'template' => "template{$i}",
                'hash_id' => base64_encode("mod_file{$i}/default.php"),
                'action' => 'changed',
                'modified_date' => '2025-01-01 12:00:00',
                'client_id' => 0,
            ];
        }
        $database = $this->createDatabaseWithOverrides($outdatedOverrides, 20);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('15', $result->description);
        // Should show "and 5 more" for truncated output
        $this->assertStringContainsString('and 5 more', $result->description);
    }

    /**
     * Create a mock database without the template_overrides table
     */
    private function createDatabaseWithoutOverridesTable(): DatabaseInterface
    {
        return new class implements DatabaseInterface {
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
                return 0;
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
                return ['#__extensions', '#__users']; // No #__template_overrides
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
     * Create a mock database with template_overrides table and specified overrides
     */
    private function createDatabaseWithOverrides(array $outdatedOverrides, int $totalOverrides): DatabaseInterface
    {
        return new class ($outdatedOverrides, $totalOverrides) implements DatabaseInterface {
            private int $queryIndex = 0;

            public function __construct(
                private readonly array $outdatedOverrides,
                private readonly int $totalOverrides,
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
                return $this->totalOverrides;
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
                $result = $this->queryIndex === 0 ? $this->outdatedOverrides : [];
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
                return ['#__extensions', '#__users', '#__template_overrides'];
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
