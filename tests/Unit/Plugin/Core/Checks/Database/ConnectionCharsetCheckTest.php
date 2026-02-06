<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\ConnectionCharsetCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionCharsetCheck::class)]
class ConnectionCharsetCheckTest extends TestCase
{
    private ConnectionCharsetCheck $connectionCharsetCheck;

    protected function setUp(): void
    {
        $this->connectionCharsetCheck = new ConnectionCharsetCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.connection_charset', $this->connectionCharsetCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->connectionCharsetCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->connectionCharsetCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->connectionCharsetCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->connectionCharsetCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenCharsetIsUtf8mb4(): void
    {
        $charsetObj = new \stdClass();
        $charsetObj->Value = 'utf8mb4';

        $database = MockDatabaseFactory::createWithObject($charsetObj);
        $this->connectionCharsetCheck->setDatabase($database);

        $healthCheckResult = $this->connectionCharsetCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('utf8mb4', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCharsetIsUtf8(): void
    {
        $charsetObj = new \stdClass();
        $charsetObj->Value = 'utf8';

        $database = MockDatabaseFactory::createWithObject($charsetObj);
        $this->connectionCharsetCheck->setDatabase($database);

        $healthCheckResult = $this->connectionCharsetCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('utf8', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCharsetIsLatin1(): void
    {
        $charsetObj = new \stdClass();
        $charsetObj->Value = 'latin1';

        $database = MockDatabaseFactory::createWithObject($charsetObj);
        $this->connectionCharsetCheck->setDatabase($database);

        $healthCheckResult = $this->connectionCharsetCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('latin1', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenResultIsNull(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->connectionCharsetCheck->setDatabase($database);

        $healthCheckResult = $this->connectionCharsetCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to determine', $healthCheckResult->description);
    }
}
