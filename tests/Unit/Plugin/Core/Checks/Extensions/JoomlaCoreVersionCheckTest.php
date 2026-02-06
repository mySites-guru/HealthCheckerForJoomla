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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\JoomlaCoreVersionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JoomlaCoreVersionCheck::class)]
class JoomlaCoreVersionCheckTest extends TestCase
{
    private JoomlaCoreVersionCheck $joomlaCoreVersionCheck;

    protected function setUp(): void
    {
        $this->joomlaCoreVersionCheck = new JoomlaCoreVersionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.joomla_core_version', $this->joomlaCoreVersionCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->joomlaCoreVersionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->joomlaCoreVersionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->joomlaCoreVersionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoUpdateAvailable(): void
    {
        // Version stub returns 5.0.0
        $database = MockDatabaseFactory::createWithResult(null);
        $this->joomlaCoreVersionCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5.0.0', $healthCheckResult->description);
        $this->assertStringContainsString('latest', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenUpdateAvailable(): void
    {
        // Current version from stub is 5.0.0, newer version available
        $database = MockDatabaseFactory::createWithResult('5.1.0');
        $this->joomlaCoreVersionCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5.0.0', $healthCheckResult->description);
        $this->assertStringContainsString('5.1.0', $healthCheckResult->description);
    }
}
