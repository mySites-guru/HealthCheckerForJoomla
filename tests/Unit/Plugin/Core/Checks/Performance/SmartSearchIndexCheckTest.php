<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\SmartSearchIndexCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SmartSearchIndexCheck::class)]
class SmartSearchIndexCheckTest extends TestCase
{
    private SmartSearchIndexCheck $smartSearchIndexCheck;

    protected function setUp(): void
    {
        $this->smartSearchIndexCheck = new SmartSearchIndexCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.smart_search_index', $this->smartSearchIndexCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->smartSearchIndexCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->smartSearchIndexCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->smartSearchIndexCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->smartSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('database', strtolower($healthCheckResult->description));
    }

    public function testRunWithSmartSearchDisabledReturnsGood(): void
    {
        // com_finder not enabled (first query returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->smartSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->smartSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not enabled', strtolower($healthCheckResult->description));
    }

    public function testRunWithSmartSearchEnabledAndEmptyIndexReturnsWarning(): void
    {
        // com_finder enabled (first query returns 1), index empty (second query returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->smartSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->smartSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('empty', strtolower($healthCheckResult->description));
    }

    public function testRunWithSmartSearchEnabledAndPopulatedIndexReturnsGood(): void
    {
        // com_finder enabled (first query returns 1), index has 150 items (second query returns 150)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 150]);
        $this->smartSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->smartSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('150', $healthCheckResult->description);
    }

    public function testRunWithLargeIndexReturnsGood(): void
    {
        // com_finder enabled with 10000 items indexed
        $database = MockDatabaseFactory::createWithSequentialResults([1, 10000]);
        $this->smartSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->smartSearchIndexCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10000', $healthCheckResult->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Try various scenarios
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->smartSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->smartSearchIndexCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testWarningMessageContainsIndexerInstruction(): void
    {
        // Smart Search enabled but empty index
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->smartSearchIndexCheck->setDatabase($database);

        $healthCheckResult = $this->smartSearchIndexCheck->run();

        $this->assertStringContainsString('indexer', strtolower($healthCheckResult->description));
    }
}
