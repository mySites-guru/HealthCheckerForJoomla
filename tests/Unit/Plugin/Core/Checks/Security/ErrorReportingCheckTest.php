<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ErrorReportingCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorReportingCheck::class)]
class ErrorReportingCheckTest extends TestCase
{
    private ErrorReportingCheck $errorReportingCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->errorReportingCheck = new ErrorReportingCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.error_reporting', $this->errorReportingCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->errorReportingCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->errorReportingCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->errorReportingCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithDefaultSettingReturnsGood(): void
    {
        // Default is 'default' which returns good
        $healthCheckResult = $this->errorReportingCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertSame('security.error_reporting', $healthCheckResult->slug);
    }

    public function testRunWithNoneSettingReturnsGood(): void
    {
        $this->cmsApplication->set('error_reporting', 'none');

        $healthCheckResult = $this->errorReportingCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('appropriately configured', $healthCheckResult->description);
    }

    public function testRunWithSimpleSettingReturnsGood(): void
    {
        $this->cmsApplication->set('error_reporting', 'simple');

        $healthCheckResult = $this->errorReportingCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithMaximumSettingReturnsWarning(): void
    {
        $this->cmsApplication->set('error_reporting', 'maximum');

        $healthCheckResult = $this->errorReportingCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('maximum/development', $healthCheckResult->description);
    }

    public function testRunWithDevelopmentSettingReturnsWarning(): void
    {
        $this->cmsApplication->set('error_reporting', 'development');

        $healthCheckResult = $this->errorReportingCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('sensitive information', $healthCheckResult->description);
    }
}
