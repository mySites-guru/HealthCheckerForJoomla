<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\AkeebaBackup\Extension;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Plugin\AkeebaBackup\Extension\AkeebaBackupPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AkeebaBackupPlugin::class)]
class AkeebaBackupPluginTest extends TestCase
{
    private AkeebaBackupPlugin $plugin;

    protected function setUp(): void
    {
        $this->plugin = new AkeebaBackupPlugin(new \stdClass());

        // Set up params as a Registry object (required for ->get() method)
        $this->plugin->params = new \Joomla\Registry\Registry();

        // Set up database
        $database = $this->createMockDatabase();
        $this->plugin->setDatabase($database);
    }

    public function testGetSubscribedEventsReturnsExpectedEvents(): void
    {
        $events = AkeebaBackupPlugin::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey('onHealthCheckerCollectCategories', $events);
        $this->assertArrayHasKey('onHealthCheckerCollectChecks', $events);
        $this->assertArrayHasKey('onHealthCheckerCollectProviders', $events);
        $this->assertSame('onCollectCategories', $events['onHealthCheckerCollectCategories']);
        $this->assertSame('onCollectChecks', $events['onHealthCheckerCollectChecks']);
        $this->assertSame('onCollectProviders', $events['onHealthCheckerCollectProviders']);
    }

    public function testOnCollectProvidersRegistersProviderMetadata(): void
    {
        $event = new CollectProvidersEvent();

        $this->plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertCount(1, $providers);
        $this->assertInstanceOf(ProviderMetadata::class, $providers[0]);
        $this->assertSame('akeeba_backup', $providers[0]->slug);
        $this->assertSame('Akeeba Backup (Unofficial)', $providers[0]->name);
        $this->assertSame('https://www.akeeba.com', $providers[0]->url);
        $this->assertStringContainsString('unofficial', strtolower($providers[0]->description));
    }

    public function testOnCollectCategoriesRegistersAkeebaBackupCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertCount(1, $categories);
        $this->assertInstanceOf(HealthCategory::class, $categories[0]);
        $this->assertSame('akeeba_backup', $categories[0]->slug);
        $this->assertSame('fa-archive', $categories[0]->icon);
        $this->assertSame(85, $categories[0]->sortOrder);
    }

    public function testOnCollectChecksRegistersBackupChecks(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $this->assertNotEmpty($checks);

        // Verify all checks implement HealthCheckInterface
        foreach ($checks as $check) {
            $this->assertInstanceOf(HealthCheckInterface::class, $check);
            $this->assertSame('akeeba_backup', $check->getProvider());
            $this->assertSame('akeeba_backup', $check->getCategory());
        }
    }

    public function testOnCollectChecksRegistersExpectedCheckSlugs(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $slugs = array_map(static fn(HealthCheckInterface $check) => $check->getSlug(), $checks);

        $expectedSlugs = [
            'akeeba_backup.installed',
            'akeeba_backup.last_backup',
            'akeeba_backup.success_rate',
            'akeeba_backup.stuck_backups',
            'akeeba_backup.files_exist',
            'akeeba_backup.backup_size',
            'akeeba_backup.profile_exists',
            'akeeba_backup.profile_configured',
            'akeeba_backup.failed_backups',
            'akeeba_backup.frequency',
        ];

        foreach ($expectedSlugs as $expectedSlug) {
            $this->assertContains($expectedSlug, $slugs, "Expected check slug '{$expectedSlug}' not found");
        }
    }

    public function testAllChecksHaveTitles(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        foreach ($event->getChecks() as $check) {
            $title = $check->getTitle();
            $this->assertIsString($title);
            $this->assertNotEmpty($title, "Check {$check->getSlug()} has empty title");
        }
    }

    public function testAllChecksCanRun(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        foreach ($event->getChecks() as $check) {
            // Each check should run without throwing exceptions
            $result = $check->run();
            $this->assertNotNull($result, "Check {$check->getSlug()} returned null result");
            $this->assertNotEmpty($result->description, "Check {$check->getSlug()} has empty description");
        }
    }

    public function testInstalledCheckReturnsWarningWhenTablesNotFound(): void
    {
        // Create database that returns empty array for SHOW TABLES query
        $database = $this->createMockDatabaseWithEmptyTables();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $installedCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_backup.installed');
        $this->assertNotNull($installedCheck);

        $result = $installedCheck->run();
        $this->assertSame('warning', $result->healthStatus->value);
        $this->assertStringContainsString('not installed', $result->description);
    }

    public function testProviderMetadataHasLogoUrl(): void
    {
        $event = new CollectProvidersEvent();

        $this->plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertNotNull($providers[0]->logoUrl);
        $this->assertStringContainsString('plg_healthchecker_akeebabackup', $providers[0]->logoUrl);
    }

    public function testCategoryHasLogoUrl(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertNotNull($categories[0]->logoUrl);
        $this->assertStringContainsString('plg_healthchecker_akeebabackup', $categories[0]->logoUrl);
    }

    public function testAllChecksReturnWarningWhenAkeebaBackupNotInstalled(): void
    {
        // Create database that returns empty array for SHOW TABLES query (Akeeba Backup not installed)
        $database = $this->createMockDatabaseWithEmptyTables();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();

        // Test each check returns warning when Akeeba Backup is not installed
        foreach ($checks as $check) {
            $result = $check->run();
            $this->assertSame(
                'warning',
                $result->healthStatus->value,
                "Check {$check->getSlug()} should return warning when Akeeba Backup not installed",
            );
            $this->assertStringContainsString(
                'not installed',
                $result->description,
                "Check {$check->getSlug()} should mention 'not installed'",
            );
        }
    }

    public function testDisabledCheckNotRegistered(): void
    {
        // Create params with a disabled check
        // Slug is 'akeeba_backup.installed' so param is 'check_akeeba_backup_installed'
        $params = new \Joomla\Registry\Registry();
        $params->set('check_akeeba_backup_installed', 0);
        $this->plugin->params = $params;

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $slugs = array_map(static fn(HealthCheckInterface $check) => $check->getSlug(), $checks);

        $this->assertNotContains('akeeba_backup.installed', $slugs);
    }

    public function testRegisters10BackupChecks(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $this->assertCount(10, $checks);
    }

    /**
     * Find a check by its slug from a list of checks
     *
     * @param array<HealthCheckInterface> $checks
     */
    private function findCheckBySlug(array $checks, string $slug): ?HealthCheckInterface
    {
        foreach ($checks as $check) {
            if ($check->getSlug() === $slug) {
                return $check;
            }
        }

        return null;
    }

    /**
     * Create a mock database that simulates Akeeba Backup being installed
     */
    private function createMockDatabase(): DatabaseInterface
    {
        return new class implements DatabaseInterface {
            private int $queryCount = 0;

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
                $this->queryCount++;

                // Different responses for sequential queries
                return match ($this->queryCount) {
                    // First call usually checks for last backup (backupstart)
                    default => date('Y-m-d H:i:s', strtotime('-1 day')),
                };
            }

            public function loadColumn(): array
            {
                // Return table name to indicate Akeeba Backup is installed
                return ['#__akeebabackup_backups'];
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
                return ['#__akeebabackup_backups', '#__akeebabackup_profiles'];
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

                    public function setLimit(int $limit = 0, int $offset = 0): self
                    {
                        return $this;
                    }
                };
            }
        };
    }

    /**
     * Create a mock database that simulates Akeeba Backup NOT being installed
     */
    private function createMockDatabaseWithEmptyTables(): DatabaseInterface
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
                return null;
            }

            public function loadColumn(): array
            {
                // Return empty array to indicate tables don't exist
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

                    public function setLimit(int $limit = 0, int $offset = 0): self
                    {
                        return $this;
                    }
                };
            }
        };
    }
}
