<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Content;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\TrashedContentCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrashedContentCheck::class)]
class TrashedContentCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $this->assertSame('content.trashed_content', $trashedContentCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $this->assertSame('content', $trashedContentCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $this->assertSame('core', $trashedContentCheck->getProvider());
    }

    public function testRunWithZeroTrashedItemsReturnsGood(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $database = $this->createDatabaseMock(0);
        $trashedContentCheck->setDatabase($database);

        $healthCheckResult = $trashedContentCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('0', $healthCheckResult->description);
    }

    public function testRunWithFiftyTrashedItemsReturnsGood(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $database = $this->createDatabaseMock(50);
        $trashedContentCheck->setDatabase($database);

        $healthCheckResult = $trashedContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('50', $healthCheckResult->description);
    }

    public function testRunWithFiftyOneTrashedItemsReturnsWarning(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $database = $this->createDatabaseMock(51);
        $trashedContentCheck->setDatabase($database);

        $healthCheckResult = $trashedContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('51', $healthCheckResult->description);
        $this->assertStringContainsString('emptying the trash', $healthCheckResult->description);
    }

    public function testRunWithManyTrashedItemsReturnsWarning(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $database = $this->createDatabaseMock(500);
        $trashedContentCheck->setDatabase($database);

        $healthCheckResult = $trashedContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('500', $healthCheckResult->description);
    }

    public function testResultContainsCorrectMetadata(): void
    {
        $trashedContentCheck = new TrashedContentCheck();
        $database = $this->createDatabaseMock(10);
        $trashedContentCheck->setDatabase($database);

        $healthCheckResult = $trashedContentCheck->run();

        $this->assertSame('content.trashed_content', $healthCheckResult->slug);
        $this->assertSame('content', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $trashedContentCheck = new TrashedContentCheck();

        // Don't inject a database - should return warning about missing database
        $healthCheckResult = $trashedContentCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    /**
     * Create a mock database that returns a specific count of trashed items
     */
    private function createDatabaseMock(int $count): DatabaseInterface
    {
        $query = $this->createMock(QueryInterface::class);
        $query->method('select')
            ->willReturnSelf();
        $query->method('from')
            ->willReturnSelf();
        $query->method('where')
            ->willReturnSelf();

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')
            ->willReturn($query);
        $db->method('quoteName')
            ->willReturnCallback(fn(string $name): string => sprintf('`%s`', $name));
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('loadResult')
            ->willReturn($count);

        return $db;
    }
}
