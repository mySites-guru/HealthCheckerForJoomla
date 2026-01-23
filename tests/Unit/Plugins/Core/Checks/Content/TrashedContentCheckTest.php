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
        $check = new TrashedContentCheck();
        $this->assertSame('content.trashed_content', $check->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $check = new TrashedContentCheck();
        $this->assertSame('content', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new TrashedContentCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunWithZeroTrashedItemsReturnsGood(): void
    {
        $check = new TrashedContentCheck();
        $db = $this->createDatabaseMock(0);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('0', $result->description);
    }

    public function testRunWithFiftyTrashedItemsReturnsGood(): void
    {
        $check = new TrashedContentCheck();
        $db = $this->createDatabaseMock(50);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('50', $result->description);
    }

    public function testRunWithFiftyOneTrashedItemsReturnsWarning(): void
    {
        $check = new TrashedContentCheck();
        $db = $this->createDatabaseMock(51);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('51', $result->description);
        $this->assertStringContainsString('emptying the trash', $result->description);
    }

    public function testRunWithManyTrashedItemsReturnsWarning(): void
    {
        $check = new TrashedContentCheck();
        $db = $this->createDatabaseMock(500);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('500', $result->description);
    }

    public function testResultContainsCorrectMetadata(): void
    {
        $check = new TrashedContentCheck();
        $db = $this->createDatabaseMock(10);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame('content.trashed_content', $result->slug);
        $this->assertSame('content', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $check = new TrashedContentCheck();

        // Don't inject a database - should return warning about missing database
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
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
            ->willReturnCallback(fn($name) => "`{$name}`");
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('loadResult')
            ->willReturn($count);

        return $db;
    }
}
