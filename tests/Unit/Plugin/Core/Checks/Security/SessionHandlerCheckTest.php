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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\SessionHandlerCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionHandlerCheck::class)]
class SessionHandlerCheckTest extends TestCase
{
    private SessionHandlerCheck $sessionHandlerCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->sessionHandlerCheck = new SessionHandlerCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.session_handler', $this->sessionHandlerCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->sessionHandlerCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->sessionHandlerCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->sessionHandlerCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsCriticalWhenSessionHandlerIsNone(): void
    {
        $this->cmsApplication->set('session_handler', 'none');

        $healthCheckResult = $this->sessionHandlerCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('none', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSessionHandlerIsDatabase(): void
    {
        $this->cmsApplication->set('session_handler', 'database');

        $healthCheckResult = $this->sessionHandlerCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('database', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSessionHandlerIsFilesystem(): void
    {
        $this->cmsApplication->set('session_handler', 'filesystem');

        $healthCheckResult = $this->sessionHandlerCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('filesystem', $healthCheckResult->description);
    }

    public function testRunReturnsGoodForOtherHandlers(): void
    {
        $this->cmsApplication->set('session_handler', 'redis');

        $healthCheckResult = $this->sessionHandlerCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('redis', $healthCheckResult->description);
    }
}
