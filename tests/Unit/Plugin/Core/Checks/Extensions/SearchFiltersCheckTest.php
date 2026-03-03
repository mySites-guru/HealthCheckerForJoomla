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
    private SearchFiltersCheck $searchFiltersCheck;

    protected function setUp(): void
    {
        $this->searchFiltersCheck = new SearchFiltersCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.search_filters', $this->searchFiltersCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->searchFiltersCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->searchFiltersCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->searchFiltersCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->searchFiltersCheck->run();

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
        $this->searchFiltersCheck->setDatabase($database);

        $healthCheckResult = $this->searchFiltersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD_DISABLED', $healthCheckResult->description);
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
        $this->searchFiltersCheck->setDatabase($database);

        $healthCheckResult = $this->searchFiltersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD_NO_FILTERS', $healthCheckResult->description);
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
        $this->searchFiltersCheck->setDatabase($database);

        $healthCheckResult = $this->searchFiltersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD_NO_FILTERS', $healthCheckResult->description);
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
        $this->searchFiltersCheck->setDatabase($database);

        $healthCheckResult = $this->searchFiltersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_GOOD', $healthCheckResult->description);
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
        $this->searchFiltersCheck->setDatabase($database);

        $healthCheckResult = $this->searchFiltersCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_WARNING_EMPTY', $healthCheckResult->description);
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
        $this->searchFiltersCheck->setDatabase($database);

        $healthCheckResult = $this->searchFiltersCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEARCH_FILTERS_WARNING_MISSING', $healthCheckResult->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $healthCheckResult = $this->searchFiltersCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}
