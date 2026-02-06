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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\SessionLifetimeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionLifetimeCheck::class)]
class SessionLifetimeCheckTest extends TestCase
{
    private SessionLifetimeCheck $sessionLifetimeCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->sessionLifetimeCheck = new SessionLifetimeCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.session_lifetime', $this->sessionLifetimeCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->sessionLifetimeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->sessionLifetimeCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->sessionLifetimeCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenLifetimeInRange(): void
    {
        $this->cmsApplication->set('lifetime', 30);

        $healthCheckResult = $this->sessionLifetimeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('30 minutes', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenLifetimeTooShort(): void
    {
        $this->cmsApplication->set('lifetime', 10);

        $healthCheckResult = $this->sessionLifetimeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('very short', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenLifetimeTooLong(): void
    {
        $this->cmsApplication->set('lifetime', 120);

        $healthCheckResult = $this->sessionLifetimeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('longer than recommended', $healthCheckResult->description);
    }
}
