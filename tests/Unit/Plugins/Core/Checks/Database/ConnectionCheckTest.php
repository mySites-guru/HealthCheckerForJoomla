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
        $connectionCheck = new ConnectionCheck();
        $this->assertSame('database.connection', $connectionCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $connectionCheck = new ConnectionCheck();
        $this->assertSame('database', $connectionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $connectionCheck = new ConnectionCheck();
        $this->assertSame('core', $connectionCheck->getProvider());
    }

    public function testRunWithWorkingDatabaseReturnsGood(): void
    {
        $connectionCheck = new ConnectionCheck();

        // Create a mock database that works correctly
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willReturn(true);

        $connectionCheck->setDatabase($db);
        $healthCheckResult = $connectionCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('working correctly', $healthCheckResult->description);
    }

    public function testRunWithFailingDatabaseReturnsCritical(): void
    {
        $connectionCheck = new ConnectionCheck();

        // Create a mock database that throws an exception
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $connectionCheck->setDatabase($db);
        $healthCheckResult = $connectionCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Connection refused', $healthCheckResult->description);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $connectionCheck = new ConnectionCheck();

        // Don't inject a database - should return warning about missing database
        $healthCheckResult = $connectionCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        // Without database injection, the check should fail gracefully
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Warning, HealthStatus::Critical]);
    }

    public function testResultContainsCorrectMetadata(): void
    {
        $connectionCheck = new ConnectionCheck();

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willReturn(true);

        $connectionCheck->setDatabase($db);
        $healthCheckResult = $connectionCheck->run();

        $this->assertSame('database.connection', $healthCheckResult->slug);
        $this->assertSame('database', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testDatabaseExceptionMessageIsIncludedInResult(): void
    {
        $connectionCheck = new ConnectionCheck();

        $errorMessage = 'SQLSTATE[HY000] [2002] Connection timed out';
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('execute')
            ->willThrowException(new \Exception($errorMessage));

        $connectionCheck->setDatabase($db);
        $healthCheckResult = $connectionCheck->run();

        $this->assertStringContainsString($errorMessage, $healthCheckResult->description);
    }
}
