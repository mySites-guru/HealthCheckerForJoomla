<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ContentSecurityPolicyCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentSecurityPolicyCheck::class)]
class ContentSecurityPolicyCheckTest extends TestCase
{
    private ContentSecurityPolicyCheck $check;

    protected function setUp(): void
    {
        $this->check = new ContentSecurityPolicyCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.content_security_policy', $this->check->getSlug());
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

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenPluginNotFound(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not found', $result->description);
    }

    public function testRunReturnsWarningWhenPluginDisabled(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 0;
        $plugin->params = '{}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsWarningWhenCspNotEnabled(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": 0}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not enabled', $result->description);
    }

    public function testRunReturnsGoodWhenCspEnabled(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": 1}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
    }
}
