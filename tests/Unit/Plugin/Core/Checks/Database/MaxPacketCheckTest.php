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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\MaxPacketCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaxPacketCheck::class)]
class MaxPacketCheckTest extends TestCase
{
    private MaxPacketCheck $maxPacketCheck;

    protected function setUp(): void
    {
        $this->maxPacketCheck = new MaxPacketCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.max_packet', $this->maxPacketCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->maxPacketCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->maxPacketCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->maxPacketCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsCriticalWhenPacketTooSmall(): void
    {
        // 512 KB - below minimum 1MB
        $object = (object) [
            'Value' => 512 * 1024,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('too small', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenPacketBelowRecommended(): void
    {
        // 8 MB - above minimum but below recommended 16MB
        $object = (object) [
            'Value' => 8 * 1024 * 1024,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('below recommended', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenPacketSufficient(): void
    {
        // 16 MB - meets recommended threshold
        $object = (object) [
            'Value' => 16 * 1024 * 1024,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('16 MB', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenPacketLarge(): void
    {
        // 64 MB - well above recommended
        $object = (object) [
            'Value' => 64 * 1024 * 1024,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('64 MB', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenPacketZero(): void
    {
        // 0 bytes - null/missing value treated as 0
        $object = (object) [
            'Value' => 0,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('too small', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenValueMissing(): void
    {
        // Object without Value property (null coalesce to 0)
        $object = (object) [];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsCriticalAtExactlyBelowMinimum(): void
    {
        // 1 byte below 1MB minimum threshold
        $object = (object) [
            'Value' => (1024 * 1024) - 1,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningAtExactlyMinimum(): void
    {
        // Exactly 1MB - meets minimum but below recommended
        $object = (object) [
            'Value' => 1024 * 1024,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 MB', $healthCheckResult->description);
    }

    public function testRunReturnsWarningAtJustBelowRecommended(): void
    {
        // 1 byte below 16MB recommended threshold
        $object = (object) [
            'Value' => (16 * 1024 * 1024) - 1,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenPacketVeryLarge(): void
    {
        // 1 GB - tests GB unit formatting
        $object = (object) [
            'Value' => 1024 * 1024 * 1024,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 GB', $healthCheckResult->description);
    }

    public function testRunFormatsKBCorrectly(): void
    {
        // 100 KB - tests KB formatting in critical message
        $object = (object) [
            'Value' => 100 * 1024,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('100 KB', $healthCheckResult->description);
    }

    public function testRunFormatsBytesCorrectly(): void
    {
        // 512 bytes - tests byte formatting
        $object = (object) [
            'Value' => 512,
        ];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->maxPacketCheck->setDatabase($database);

        $healthCheckResult = $this->maxPacketCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('512 B', $healthCheckResult->description);
    }
}
