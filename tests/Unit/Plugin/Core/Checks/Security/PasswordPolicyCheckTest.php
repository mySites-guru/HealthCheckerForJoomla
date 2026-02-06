<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\PasswordPolicyCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordPolicyCheck::class)]
class PasswordPolicyCheckTest extends TestCase
{
    private PasswordPolicyCheck $passwordPolicyCheck;

    protected function setUp(): void
    {
        $this->passwordPolicyCheck = new PasswordPolicyCheck();
    }

    protected function tearDown(): void
    {
        ComponentHelper::resetParams();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.password_policy', $this->passwordPolicyCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->passwordPolicyCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->passwordPolicyCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->passwordPolicyCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsCriticalWhenPasswordLengthTooShort(): void
    {
        $registry = new Registry([
            'minimum_length' => 6,
        ]);
        ComponentHelper::setParams('com_users', $registry);

        $healthCheckResult = $this->passwordPolicyCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('6 characters', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenPasswordLengthShort(): void
    {
        $registry = new Registry([
            'minimum_length' => 10,
        ]);
        ComponentHelper::setParams('com_users', $registry);

        $healthCheckResult = $this->passwordPolicyCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10 characters', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenNoComplexity(): void
    {
        $registry = new Registry([
            'minimum_length' => 12,
            'minimum_integers' => 0,
            'minimum_symbols' => 0,
            'minimum_uppercase' => 0,
            'minimum_lowercase' => 0,
        ]);
        ComponentHelper::setParams('com_users', $registry);

        $healthCheckResult = $this->passwordPolicyCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('no complexity', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenPasswordPolicyStrong(): void
    {
        $registry = new Registry([
            'minimum_length' => 12,
            'minimum_integers' => 1,
        ]);
        ComponentHelper::setParams('com_users', $registry);

        $healthCheckResult = $this->passwordPolicyCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('12 characters', $healthCheckResult->description);
        $this->assertStringContainsString('complexity', $healthCheckResult->description);
    }
}
