<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\AkeebaAdminTools\Extension;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Plugin\AkeebaAdminTools\Extension\AkeebaAdminToolsPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AkeebaAdminToolsPlugin::class)]
class AkeebaAdminToolsPluginTest extends TestCase
{
    private AkeebaAdminToolsPlugin $plugin;

    protected function setUp(): void
    {
        $this->plugin = new AkeebaAdminToolsPlugin(new \stdClass());

        // Set up params as a Registry object (required for ->get() method)
        $this->plugin->params = new \Joomla\Registry\Registry();

        // Set up database
        $database = $this->createMockDatabase();
        $this->plugin->setDatabase($database);
    }

    public function testGetSubscribedEventsReturnsExpectedEvents(): void
    {
        $events = AkeebaAdminToolsPlugin::getSubscribedEvents();

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
        $this->assertSame('akeeba_admintools', $providers[0]->slug);
        $this->assertSame('Akeeba Admin Tools (Unofficial)', $providers[0]->name);
        $this->assertSame('https://www.akeeba.com', $providers[0]->url);
        $this->assertStringContainsString('unofficial', strtolower($providers[0]->description));
    }

    public function testOnCollectCategoriesRegistersAkeebaAdminToolsCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertCount(1, $categories);
        $this->assertInstanceOf(HealthCategory::class, $categories[0]);
        $this->assertSame('akeeba_admintools', $categories[0]->slug);
        $this->assertSame('fa-shield-alt', $categories[0]->icon);
        $this->assertSame(86, $categories[0]->sortOrder);
    }

    public function testOnCollectChecksRegistersSecurityChecks(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $this->assertNotEmpty($checks);

        // Verify all checks implement HealthCheckInterface
        foreach ($checks as $check) {
            $this->assertInstanceOf(HealthCheckInterface::class, $check);
            $this->assertSame('akeeba_admintools', $check->getProvider());
            $this->assertSame('akeeba_admintools', $check->getCategory());
        }
    }

    public function testOnCollectChecksRegistersExpectedCheckSlugs(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $slugs = array_map(static fn(HealthCheckInterface $check) => $check->getSlug(), $checks);

        $expectedSlugs = [
            'akeeba_admintools.installed',
            'akeeba_admintools.waf_enabled',
            'akeeba_admintools.security_events',
            'akeeba_admintools.blocked_attacks',
            'akeeba_admintools.active_bans',
            'akeeba_admintools.scan_age',
            'akeeba_admintools.file_alerts',
            'akeeba_admintools.temp_superusers',
            'akeeba_admintools.ip_whitelist',
            'akeeba_admintools.waf_rules',
            'akeeba_admintools.login_failures',
            'akeeba_admintools.geoblocking',
            'akeeba_admintools.sqli_blocks',
            'akeeba_admintools.xss_blocks',
            'akeeba_admintools.admin_access',
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

        $installedCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.installed');
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
        $this->assertStringContainsString('plg_healthchecker_akeebaadmintools', $providers[0]->logoUrl);
    }

    public function testCategoryHasLogoUrl(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertNotNull($categories[0]->logoUrl);
        $this->assertStringContainsString('plg_healthchecker_akeebaadmintools', $categories[0]->logoUrl);
    }

    public function testRegisters15SecurityChecks(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $this->assertCount(15, $checks);
    }

    public function testWafEnabledCheckReturnsWarningWhenNoRulesEnabled(): void
    {
        // Create database that simulates WAF table existing but no enabled rules
        $database = $this->createMockDatabaseWithWafTableButNoRules();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $wafCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.waf_enabled');
        $this->assertNotNull($wafCheck);

        $result = $wafCheck->run();
        $this->assertSame('warning', $result->healthStatus->value);
        $this->assertStringContainsString('No WAF rules', $result->description);
    }

    public function testAllChecksReturnWarningWhenAdminToolsNotInstalled(): void
    {
        // Create database that returns empty array for SHOW TABLES query (Admin Tools not installed)
        $database = $this->createMockDatabaseWithEmptyTables();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();

        // Test each check returns warning when Admin Tools is not installed
        foreach ($checks as $check) {
            $result = $check->run();
            $this->assertSame(
                'warning',
                $result->healthStatus->value,
                "Check {$check->getSlug()} should return warning when Admin Tools not installed",
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
        // Slug is 'akeeba_admintools.installed' so param is 'check_akeeba_admintools_installed'
        $params = new \Joomla\Registry\Registry();
        $params->set('check_akeeba_admintools_installed', 0);
        $this->plugin->params = $params;

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $slugs = array_map(static fn(HealthCheckInterface $check) => $check->getSlug(), $checks);

        $this->assertNotContains('akeeba_admintools.installed', $slugs);
    }

    public function testScanAgeCheckReturnsCriticalWhenNoScansCompleted(): void
    {
        // Create database where scan table exists but no completed scans
        $database = $this->createMockDatabaseWithScanTableButNoScans();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $scanCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.scan_age');
        $this->assertNotNull($scanCheck);

        $result = $scanCheck->run();
        $this->assertSame('critical', $result->healthStatus->value);
        $this->assertStringContainsString('No file integrity scans', $result->description);
    }

    public function testScanAgeCheckReturnsCriticalWhenScanOlderThan30Days(): void
    {
        // Create database where last scan was over 30 days ago
        $database = $this->createMockDatabaseWithOldScan(35);
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $scanCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.scan_age');
        $this->assertNotNull($scanCheck);

        $result = $scanCheck->run();
        $this->assertSame('critical', $result->healthStatus->value);
        $this->assertStringContainsString('days ago', $result->description);
    }

    public function testScanAgeCheckReturnsWarningWhenScanBetween7And30Days(): void
    {
        // Create database where last scan was 15 days ago
        $database = $this->createMockDatabaseWithOldScan(15);
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $scanCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.scan_age');
        $this->assertNotNull($scanCheck);

        $result = $scanCheck->run();
        $this->assertSame('warning', $result->healthStatus->value);
        $this->assertStringContainsString('days ago', $result->description);
    }

    public function testFileAlertsCheckReturnsCriticalWhenHighThreatAlerts(): void
    {
        // Create database with high threat alerts
        $database = $this->createMockDatabaseWithHighThreatAlerts();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $alertsCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.file_alerts');
        $this->assertNotNull($alertsCheck);

        $result = $alertsCheck->run();
        $this->assertSame('critical', $result->healthStatus->value);
        $this->assertStringContainsString('high-threat', $result->description);
    }

    public function testFileAlertsCheckReturnsWarningWhenLowThreatAlerts(): void
    {
        // Create database with low threat alerts
        $database = $this->createMockDatabaseWithLowThreatAlerts();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $alertsCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.file_alerts');
        $this->assertNotNull($alertsCheck);

        $result = $alertsCheck->run();
        $this->assertSame('warning', $result->healthStatus->value);
        $this->assertStringContainsString('require review', $result->description);
    }

    public function testLoginFailuresCheckReturnsWarningWhenHighFailures(): void
    {
        // Create database with more than 10 login failures
        $database = $this->createMockDatabaseWithLoginFailures(15);
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $loginCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.login_failures');
        $this->assertNotNull($loginCheck);

        $result = $loginCheck->run();
        $this->assertSame('warning', $result->healthStatus->value);
        $this->assertStringContainsString('15 login failures', $result->description);
    }

    public function testTempSuperUsersCheckReturnsWarningWhenExpiredFound(): void
    {
        // Create database with expired temporary super users
        $database = $this->createMockDatabaseWithExpiredTempSuperUsers();
        $this->plugin->setDatabase($database);

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $tempCheck = $this->findCheckBySlug($event->getChecks(), 'akeeba_admintools.temp_superusers');
        $this->assertNotNull($tempCheck);

        $result = $tempCheck->run();
        $this->assertSame('warning', $result->healthStatus->value);
        $this->assertStringContainsString('expired', $result->description);
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
     * Create a mock database that simulates Admin Tools being installed
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

                // Return different values for sequential queries
                // Most queries just need a count, return small values
                return match ($this->queryCount) {
                    // Scan age check returns a recent date
                    default => date('Y-m-d H:i:s', strtotime('-1 day')),
                };
            }

            public function loadColumn(): array
            {
                // Return table name to indicate Admin Tools is installed
                return ['#__admintools_log'];
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
                return [
                    '#__admintools_log',
                    '#__admintools_wafblacklists',
                    '#__admintools_ipautoban',
                    '#__admintools_scans',
                    '#__admintools_scanalerts',
                    '#__admintools_tempsupers',
                    '#__admintools_ipallow',
                ];
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
     * Create a mock database that simulates Admin Tools NOT being installed
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

    /**
     * Create a mock database that simulates WAF table existing but no rules enabled
     */
    private function createMockDatabaseWithWafTableButNoRules(): DatabaseInterface
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

                // Return 0 for the WAF enabled count query
                return 0;
            }

            public function loadColumn(): array
            {
                // Return table name to indicate WAF table exists
                return ['#__admintools_wafblacklists'];
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
                return ['#__admintools_wafblacklists'];
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
     * Create a mock database where scan table exists but no completed scans
     */
    private function createMockDatabaseWithScanTableButNoScans(): DatabaseInterface
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
                // Return null (no completed scans found)
                return null;
            }

            public function loadColumn(): array
            {
                // Return table name to indicate Admin Tools is installed
                return ['#__admintools_scans'];
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
                return ['#__admintools_scans'];
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
     * Create a mock database where last scan was X days ago
     */
    private function createMockDatabaseWithOldScan(int $daysAgo): DatabaseInterface
    {
        return new class ($daysAgo) implements DatabaseInterface {
            public function __construct(
                private int $daysAgo,
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
                // Return scan date from X days ago
                return date('Y-m-d H:i:s', strtotime("-{$this->daysAgo} days"));
            }

            public function loadColumn(): array
            {
                return ['#__admintools_scans'];
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
                return ['#__admintools_scans'];
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
     * Create a mock database with high threat file alerts
     */
    private function createMockDatabaseWithHighThreatAlerts(): DatabaseInterface
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

                // First count query returns high threat count
                return 5;
            }

            public function loadColumn(): array
            {
                return ['#__admintools_scanalerts'];
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
                return ['#__admintools_scanalerts'];
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
     * Create a mock database with low threat file alerts (no high threat)
     */
    private function createMockDatabaseWithLowThreatAlerts(): DatabaseInterface
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

                // First query (high threat count) returns 0, second (any alerts) returns > 0
                return match ($this->queryCount) {
                    1 => 0,  // No high threat alerts
                    default => 3,  // Some low threat alerts
                };
            }

            public function loadColumn(): array
            {
                return ['#__admintools_scanalerts'];
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
                return ['#__admintools_scanalerts'];
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
     * Create a mock database with login failures
     */
    private function createMockDatabaseWithLoginFailures(int $count): DatabaseInterface
    {
        return new class ($count) implements DatabaseInterface {
            public function __construct(
                private int $failureCount,
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
                return $this->failureCount;
            }

            public function loadColumn(): array
            {
                return ['#__admintools_log'];
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
                return ['#__admintools_log'];
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
     * Create a mock database with expired temporary super users
     */
    private function createMockDatabaseWithExpiredTempSuperUsers(): DatabaseInterface
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
                // Return count of expired temp super users
                return 2;
            }

            public function loadColumn(): array
            {
                return ['#__admintools_tempsupers'];
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
                return ['#__admintools_tempsupers'];
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
