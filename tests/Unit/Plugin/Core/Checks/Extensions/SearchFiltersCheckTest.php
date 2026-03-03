<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\SearchFiltersCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchFiltersCheck::class)]
class SearchFiltersCheckTest extends TestCase
{
    private SearchFiltersCheck $check;

    protected function setUp(): void
    {
        $this->check = new SearchFiltersCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.search_filters', $this->check->getSlug());
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
        $healthCheckResult = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('check_error', strtolower($healthCheckResult->description));
    }

    public function testSmartSearchDisabledReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 0,
            ], // com_finder disabled
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD_DISABLED', $result->description);
    }

    public function testNoMenuItemsWithFiltersReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 1,
            ],    // com_finder enabled
            [
                'method' => 'loadObjectList',
                'return' => [],
            ], // no menu items
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD_NO_FILTERS', $result->description);
    }

    public function testMenuItemsWithNoFilterIdReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 1,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'id' => 1,
                        'title' => 'Search',
                        'params' => '{}',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD_NO_FILTERS', $result->description);
    }

    public function testFiltersWithMapsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 1,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'id' => 1,
                        'title' => 'Search Page',
                        'params' => '{"f":"5"}',
                    ],
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'filter_id' => 5,
                        'title' => 'Articles Filter',
                        'map_count' => 3,
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD', $result->description);
    }

    public function testEmptyFilterReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 1,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'id' => 1,
                        'title' => 'Search Page',
                        'params' => '{"f":"5"}',
                    ],
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'filter_id' => 5,
                        'title' => 'Empty Filter',
                        'map_count' => 0,
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_WARNING_EMPTY', $result->description);
    }

    public function testMissingFilterReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 1,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'id' => 1,
                        'title' => 'Search Page',
                        'params' => '{"filter_id":"99"}',
                    ],
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ], // no filters found
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_WARNING_MISSING', $result->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }
}
