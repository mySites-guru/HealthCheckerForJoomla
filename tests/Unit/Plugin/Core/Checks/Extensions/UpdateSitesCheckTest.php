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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\UpdateSitesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateSitesCheck::class)]
class UpdateSitesCheckTest extends TestCase
{
    private UpdateSitesCheck $updateSitesCheck;

    protected function setUp(): void
    {
        $this->updateSitesCheck = new UpdateSitesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.update_sites', $this->updateSitesCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->updateSitesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->updateSitesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->updateSitesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->updateSitesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenAllUpdateSitesEnabled(): void
    {
        // First loadResult returns enabled count (10), second returns disabled count (0)
        $database = MockDatabaseFactory::createWithSequentialResults([10, 0]);
        $this->updateSitesCheck->setDatabase($database);

        $healthCheckResult = $this->updateSitesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10 update site(s) enabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSomeUpdateSitesDisabled(): void
    {
        // First loadResult returns enabled count (8), second returns disabled count (3)
        $database = MockDatabaseFactory::createWithSequentialResults([8, 3]);
        $this->updateSitesCheck->setDatabase($database);

        $healthCheckResult = $this->updateSitesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('3 update site(s) disabled', $healthCheckResult->description);
        $this->assertStringContainsString('security updates', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenOneUpdateSiteDisabled(): void
    {
        // First loadResult returns enabled count (5), second returns disabled count (1)
        $database = MockDatabaseFactory::createWithSequentialResults([5, 1]);
        $this->updateSitesCheck->setDatabase($database);

        $healthCheckResult = $this->updateSitesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 update site(s) disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenNoUpdateSites(): void
    {
        // No update sites at all - both counts are 0
        $database = MockDatabaseFactory::createWithSequentialResults([0, 0]);
        $this->updateSitesCheck->setDatabase($database);

        $healthCheckResult = $this->updateSitesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('0 update site(s) enabled', $healthCheckResult->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check never returns critical status (per docblock)
        $database = MockDatabaseFactory::createWithSequentialResults([0, 10]);
        $this->updateSitesCheck->setDatabase($database);

        $healthCheckResult = $this->updateSitesCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}
