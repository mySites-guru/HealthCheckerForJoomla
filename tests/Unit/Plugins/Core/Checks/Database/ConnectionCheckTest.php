<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Database;

use Joomla\Database\DatabaseInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\ConnectionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionCheck::class)]
class ConnectionCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $check = new ConnectionCheck();
        $this->assertSame('database.connection', $check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $check = new ConnectionCheck();
        $this->assertSame('database', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new ConnectionCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunWithWorkingDatabaseReturnsGood(): void
    {
        $check = new ConnectionCheck();

        // Create a mock database that works correctly
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willReturn(true);

        $check->setDatabase($db);
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('working correctly', $result->description);
    }

    public function testRunWithFailingDatabaseReturnsCritical(): void
    {
        $check = new ConnectionCheck();

        // Create a mock database that throws an exception
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $check->setDatabase($db);
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Connection refused', $result->description);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $check = new ConnectionCheck();

        // Don't inject a database - should return warning about missing database
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        // Without database injection, the check should fail gracefully
        $this->assertContains($result->healthStatus, [HealthStatus::Warning, HealthStatus::Critical]);
    }

    public function testResultContainsCorrectMetadata(): void
    {
        $check = new ConnectionCheck();

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willReturn(true);

        $check->setDatabase($db);
        $result = $check->run();

        $this->assertSame('database.connection', $result->slug);
        $this->assertSame('database', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testDatabaseExceptionMessageIsIncludedInResult(): void
    {
        $check = new ConnectionCheck();

        $errorMessage = 'SQLSTATE[HY000] [2002] Connection timed out';
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willThrowException(new \Exception($errorMessage));

        $check->setDatabase($db);
        $result = $check->run();

        $this->assertStringContainsString($errorMessage, $result->description);
    }
}
