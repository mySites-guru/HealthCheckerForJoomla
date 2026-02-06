<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\LazyLoadCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LazyLoadCheck::class)]
class LazyLoadCheckTest extends TestCase
{
    private LazyLoadCheck $lazyLoadCheck;

    protected function setUp(): void
    {
        $this->lazyLoadCheck = new LazyLoadCheck();
        // Reset plugin helper state for test isolation
        PluginHelper::resetEnabled();
    }

    protected function tearDown(): void
    {
        // Reset plugin helper state after each test
        PluginHelper::resetEnabled();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.lazy_load', $this->lazyLoadCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->lazyLoadCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->lazyLoadCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->lazyLoadCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        // PluginHelper::isEnabled returns false by default in stub
        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithLazyLoadEnabledReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        $params = json_encode([
            'lazy_images' => 1,
        ]);
        $database = $this->createDatabaseWithPluginParams($params);
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithLazyLoadDisabledReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        $params = json_encode([
            'lazy_images' => 0,
        ]);
        $database = $this->createDatabaseWithPluginParams($params);
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithEmptyParamsReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        $database = MockDatabaseFactory::createWithResult('');
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithInvalidJsonParamsReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        $database = MockDatabaseFactory::createWithResult('invalid-json{');
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithMissingLazyImagesParamReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        $params = json_encode([
            'other_setting' => 1,
        ]);
        $database = $this->createDatabaseWithPluginParams($params);
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunWithNullParamsReturnsWarning(): void
    {
        // Enable the plugin so we get to the params check
        PluginHelper::setEnabled('content', 'joomla', true);

        $database = MockDatabaseFactory::createWithResult(null);
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to determine', $healthCheckResult->description);
    }

    public function testRunWithLazyImagesStringValueEnabledReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        // Test with string '1' instead of integer
        $params = json_encode([
            'lazy_images' => '1',
        ]);
        $database = $this->createDatabaseWithPluginParams($params);
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithLazyImagesStringValueDisabledReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        // Test with string '0' instead of integer
        $params = json_encode([
            'lazy_images' => '0',
        ]);
        $database = $this->createDatabaseWithPluginParams($params);
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Lazy loading for images is disabled', $healthCheckResult->description);
    }

    public function testRunDescriptionMentionsLazyLoadingEnabled(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('content', 'joomla', true);

        $params = json_encode([
            'lazy_images' => 1,
        ]);
        $database = $this->createDatabaseWithPluginParams($params);
        $this->lazyLoadCheck->setDatabase($database);

        $healthCheckResult = $this->lazyLoadCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }

    /**
     * Create a mock database that returns plugin params
     */
    private function createDatabaseWithPluginParams(string $params): DatabaseInterface
    {
        return new class ($params) implements DatabaseInterface {
            public function __construct(
                private readonly string $params,
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
                return $this->params;
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
