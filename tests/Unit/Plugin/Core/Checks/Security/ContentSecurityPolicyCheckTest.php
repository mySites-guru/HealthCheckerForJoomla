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
    private ContentSecurityPolicyCheck $contentSecurityPolicyCheck;

    protected function setUp(): void
    {
        $this->contentSecurityPolicyCheck = new ContentSecurityPolicyCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.content_security_policy', $this->contentSecurityPolicyCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->contentSecurityPolicyCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->contentSecurityPolicyCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->contentSecurityPolicyCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenPluginNotFound(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not found', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenPluginDisabled(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 0;
        $plugin->params = '{}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCspNotEnabled(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": 0}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not enabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenCspEnabled(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": 1}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenParamsEmpty(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not configured', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenParamsIsEmptyArray(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '[]';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        // json_decode('[]', true) returns [], which is_array but empty
        // The code checks: if (! is_array($params) || $params === [])
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenParamsIsNull(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = 'null';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        // json_decode('null', true) returns null, which !is_array
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not configured', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenParamsIsInvalidJson(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = 'invalid json';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        // json_decode('invalid json', true) returns null
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not configured', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCspMissingFromParams(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"some_other_setting": 1}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        // contentsecuritypolicy not in params, defaults to 0
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not enabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenCspEnabledAsString(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": "1"}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        // String "1" is cast to int 1
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testResultContainsCorrectCategory(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": 1}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame('security', $healthCheckResult->category);
    }

    public function testResultContainsCorrectSlug(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": 1}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame('security.content_security_policy', $healthCheckResult->slug);
    }

    public function testResultContainsProvider(): void
    {
        $plugin = new \stdClass();
        $plugin->enabled = 1;
        $plugin->params = '{"contentsecuritypolicy": 1}';

        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->contentSecurityPolicyCheck->setDatabase($database);

        $healthCheckResult = $this->contentSecurityPolicyCheck->run();

        $this->assertSame('core', $healthCheckResult->provider);
    }
}
