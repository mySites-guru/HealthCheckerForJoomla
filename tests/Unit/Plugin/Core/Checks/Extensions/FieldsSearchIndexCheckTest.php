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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\FieldsSearchIndexCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FieldsSearchIndexCheck::class)]
class FieldsSearchIndexCheckTest extends TestCase
{
    private FieldsSearchIndexCheck $fieldsSearchIndexCheck;

    protected function setUp(): void
    {
        $this->fieldsSearchIndexCheck = new FieldsSearchIndexCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.fields_search_index', $this->fieldsSearchIndexCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->fieldsSearchIndexCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->fieldsSearchIndexCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->fieldsSearchIndexCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithSmartSearchDisabledReturnsGood(): void
    {
        // Query 1: com_finder not enabled
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->fieldsSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertSame(
            'COM_HEALTHCHECKER_CHECK_EXTENSIONS_FIELDS_SEARCH_INDEX_GOOD',
            $healthCheckResult->description,
        );
    }

    public function testRunWithNoPublishedFieldsReturnsGood(): void
    {
        // Query 1: com_finder enabled
        // Query 2: 0 published fields
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->fieldsSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertSame(
            'COM_HEALTHCHECKER_CHECK_EXTENSIONS_FIELDS_SEARCH_INDEX_GOOD_2',
            $healthCheckResult->description,
        );
    }

    public function testRunWithFieldsButNoIndexingReturnsWarning(): void
    {
        // Query 1: com_finder enabled
        // Query 2: 5 published fields
        // Query 3: 0 fields with search indexing
        $database = MockDatabaseFactory::createWithSequentialResults([1, 5, 0]);
        $this->fieldsSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('FIELDS_SEARCH_INDEX_WARNING', $healthCheckResult->description);
    }

    public function testRunWithSomeFieldsIndexedReturnsGood(): void
    {
        // Query 1: com_finder enabled
        // Query 2: 5 published fields
        // Query 3: 3 fields with search indexing
        $database = MockDatabaseFactory::createWithSequentialResults([1, 5, 3]);
        $this->fieldsSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('FIELDS_SEARCH_INDEX_GOOD_3', $healthCheckResult->description);
    }

    public function testRunWithAllFieldsIndexedReturnsGood(): void
    {
        // Query 1: com_finder enabled
        // Query 2: 3 published fields
        // Query 3: 3 fields with search indexing
        $database = MockDatabaseFactory::createWithSequentialResults([1, 3, 3]);
        $this->fieldsSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('FIELDS_SEARCH_INDEX_GOOD_3', $healthCheckResult->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Worst case: fields exist with no indexing
        $database = MockDatabaseFactory::createWithSequentialResults([1, 10, 0]);
        $this->fieldsSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testActionUrlReturnedOnWarning(): void
    {
        $this->assertNotNull($this->fieldsSearchIndexCheck->getActionUrl(HealthStatus::Warning));
        $this->assertStringContainsString(
            'com_fields',
            $this->fieldsSearchIndexCheck->getActionUrl(HealthStatus::Warning),
        );
    }

    public function testActionUrlNullOnGood(): void
    {
        $this->assertNull($this->fieldsSearchIndexCheck->getActionUrl(HealthStatus::Good));
    }

    public function testRunWithSingleFieldNoIndexingReturnsWarning(): void
    {
        // Query 1: com_finder enabled
        // Query 2: 1 published field
        // Query 3: 0 fields with search indexing
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1, 0]);
        $this->fieldsSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->fieldsSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }
}
