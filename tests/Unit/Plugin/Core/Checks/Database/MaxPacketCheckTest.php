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
    private MaxPacketCheck $check;

    protected function setUp(): void
    {
        $this->check = new MaxPacketCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.max_packet', $this->check->getSlug());
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

    public function testRunReturnsCriticalWhenPacketTooSmall(): void
    {
        // 512 KB - below minimum 1MB
        $object = (object) ['Value' => 512 * 1024];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('too small', $result->description);
    }

    public function testRunReturnsWarningWhenPacketBelowRecommended(): void
    {
        // 8 MB - above minimum but below recommended 16MB
        $object = (object) ['Value' => 8 * 1024 * 1024];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('below recommended', $result->description);
    }

    public function testRunReturnsGoodWhenPacketSufficient(): void
    {
        // 16 MB - meets recommended threshold
        $object = (object) ['Value' => 16 * 1024 * 1024];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('16 MB', $result->description);
    }

    public function testRunReturnsGoodWhenPacketLarge(): void
    {
        // 64 MB - well above recommended
        $object = (object) ['Value' => 64 * 1024 * 1024];
        $database = MockDatabaseFactory::createWithObject($object);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('64 MB', $result->description);
    }
}
