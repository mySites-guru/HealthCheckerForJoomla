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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\MissingUpdatesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MissingUpdatesCheck::class)]
class MissingUpdatesCheckTest extends TestCase
{
    private MissingUpdatesCheck $missingUpdatesCheck;

    protected function setUp(): void
    {
        $this->missingUpdatesCheck = new MissingUpdatesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.missing_updates', $this->missingUpdatesCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->missingUpdatesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->missingUpdatesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->missingUpdatesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->missingUpdatesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoUpdates(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->missingUpdatesCheck->setDatabase($database);

        $healthCheckResult = $this->missingUpdatesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('up to date', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenFewUpdates(): void
    {
        $database = MockDatabaseFactory::createWithResult(3);
        $this->missingUpdatesCheck->setDatabase($database);

        $healthCheckResult = $this->missingUpdatesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('3 extension update', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenManyUpdates(): void
    {
        $database = MockDatabaseFactory::createWithResult(10);
        $this->missingUpdatesCheck->setDatabase($database);

        $healthCheckResult = $this->missingUpdatesCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10 extension update', $healthCheckResult->description);
    }
}
