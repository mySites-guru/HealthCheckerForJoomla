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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\PasswordPolicyCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordPolicyCheck::class)]
class PasswordPolicyCheckTest extends TestCase
{
    private PasswordPolicyCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        $this->check = new PasswordPolicyCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.password_policy', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->check->getCategory());
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

    public function testRunReturnsCriticalWhenPasswordLengthTooShort(): void
    {
        $this->app->set('minimum_length', 6);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('6 characters', $result->description);
    }

    public function testRunReturnsWarningWhenPasswordLengthShort(): void
    {
        $this->app->set('minimum_length', 10);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('10 characters', $result->description);
    }

    public function testRunReturnsWarningWhenNoComplexity(): void
    {
        $this->app->set('minimum_length', 12);
        $this->app->set('minimum_integers', 0);
        $this->app->set('minimum_symbols', 0);
        $this->app->set('minimum_uppercase', 0);
        $this->app->set('minimum_lowercase', 0);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('no complexity', $result->description);
    }

    public function testRunReturnsGoodWhenPasswordPolicyStrong(): void
    {
        $this->app->set('minimum_length', 12);
        $this->app->set('minimum_integers', 1);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('12 characters', $result->description);
        $this->assertStringContainsString('complexity', $result->description);
    }
}
