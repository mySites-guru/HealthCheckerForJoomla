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
    private ConnectionCharsetCheck $check;

    protected function setUp(): void
    {
        $this->check = new ConnectionCharsetCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.connection_charset', $this->check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->check->getCategory());
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
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenCharsetIsUtf8mb4(): void
    {
        $charsetObj = new \stdClass();
        $charsetObj->Value = 'utf8mb4';

        $database = MockDatabaseFactory::createWithObject($charsetObj);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('utf8mb4', $result->description);
    }

    public function testRunReturnsWarningWhenCharsetIsUtf8(): void
    {
        $charsetObj = new \stdClass();
        $charsetObj->Value = 'utf8';

        $database = MockDatabaseFactory::createWithObject($charsetObj);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('utf8', $result->description);
    }

    public function testRunReturnsWarningWhenCharsetIsLatin1(): void
    {
        $charsetObj = new \stdClass();
        $charsetObj->Value = 'latin1';

        $database = MockDatabaseFactory::createWithObject($charsetObj);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('latin1', $result->description);
    }

    public function testRunReturnsCriticalWhenResultIsNull(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Unable to determine', $result->description);
    }
}
