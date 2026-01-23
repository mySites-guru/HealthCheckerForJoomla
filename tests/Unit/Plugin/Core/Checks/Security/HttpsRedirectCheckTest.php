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
use Joomla\CMS\Uri\Uri;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\HttpsRedirectCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpsRedirectCheck::class)]
class HttpsRedirectCheckTest extends TestCase
{
    private HttpsRedirectCheck $check;

    private CMSApplication $app;

    private string $htaccessPath;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        Uri::resetMockSsl();
        $this->check = new HttpsRedirectCheck();
        $this->htaccessPath = JPATH_ROOT . '/.htaccess';

        // Ensure JPATH_ROOT exists
        if (! is_dir(JPATH_ROOT)) {
            mkdir(JPATH_ROOT, 0777, true);
        }

        // Clean up any existing .htaccess
        if (file_exists($this->htaccessPath)) {
            unlink($this->htaccessPath);
        }
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
        Uri::resetMockSsl();

        // Clean up .htaccess after each test
        if (file_exists($this->htaccessPath)) {
            chmod($this->htaccessPath, 0644);
            unlink($this->htaccessPath);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.https_redirect', $this->check->getSlug());
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

    public function testRunReturnsCriticalWhenNoHttpsAndNoRedirect(): void
    {
        // No Force SSL, not using HTTPS, no .htaccess redirect
        $this->app->set('force_ssl', 0);
        // Uri::getInstance()->isSsl() returns false by default

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('not configured', $result->description);
    }

    public function testRunReturnsWarningWhenForceSslIsAdminOnly(): void
    {
        $this->app->set('force_ssl', 1);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('administrator', strtolower($result->description));
        $this->assertStringContainsString('option 2', $result->description);
    }

    public function testRunReturnsWarningWhenForceSslEntireSiteButNoHttps(): void
    {
        // Force SSL is enabled for entire site, but current connection is not HTTPS
        $this->app->set('force_ssl', 2);
        // Uri::getInstance()->isSsl() returns false by default

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Force SSL is enabled', $result->description);
        $this->assertStringContainsString('SSL certificate', $result->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunResultDescriptionIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testRunResultDescriptionContainsHttpsOrSsl(): void
    {
        $result = $this->check->run();

        // The description should contain HTTPS or SSL related information
        $this->assertTrue(
            stripos($result->description, 'https') !== false ||
            stripos($result->description, 'ssl') !== false ||
            stripos($result->description, 'redirect') !== false ||
            stripos($result->description, 'configured') !== false,
            'Description should mention HTTPS, SSL, redirect, or configuration status',
        );
    }

    public function testRunResultContainsSlug(): void
    {
        $result = $this->check->run();

        $this->assertSame('security.https_redirect', $result->slug);
    }

    public function testRunResultContainsTitle(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testRunResultHasProvider(): void
    {
        $result = $this->check->run();

        $this->assertSame('core', $result->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $result = $this->check->run();

        $this->assertSame('security', $result->category);
    }

    public function testRunWithHtaccessRedirectPattern1(): void
    {
        // Create .htaccess with RewriteCond %{HTTPS} pattern
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        // When htaccess redirect is configured, code falls through to Good fallback
        // The redirect IS configured - so it's considered correct configuration
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithHtaccessRedirectPattern2(): void
    {
        // Create .htaccess with RewriteRule https:// pattern
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
RewriteRule ^(.*)$ https://example.com/$1 [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        // The htaccess contains "https://" which matches the redirect pattern
        // So hasHtaccessRedirect is true, falls to Good fallback
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithHtaccessRedirectPattern3(): void
    {
        // Create .htaccess with https://%{HTTP_HOST} pattern
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule .* https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        // Contains both "RewriteCond %{HTTPS}" and "https://%{HTTP_HOST}" patterns
        // So hasHtaccessRedirect is true, falls to Good fallback
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsCriticalWithEmptyHtaccess(): void
    {
        file_put_contents($this->htaccessPath, '');
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunReturnsCriticalWithNoHtaccess(): void
    {
        // No .htaccess file
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunWithForceSslStringValue(): void
    {
        // Test that string values are cast correctly
        $this->app->set('force_ssl', '1');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testCriticalDescriptionMentionsConfiguration(): void
    {
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertTrue(
            stripos($result->description, 'Force SSL') !== false ||
            stripos($result->description, 'configuration') !== false ||
            stripos($result->description, 'htaccess') !== false,
        );
    }

    public function testWarningForAdminOnlyMentionsEntireSite(): void
    {
        $this->app->set('force_ssl', 1);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('entire site', $result->description);
    }

    public function testRunWithHtaccessContainingBothPatterns(): void
    {
        // Create .htaccess with multiple HTTPS patterns
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
# HTTPS Redirect
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        // Contains HTTPS redirect patterns, so hasHtaccessRedirect is true
        // Falls to Good fallback since redirect is configured
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithNoHtaccessRedirectPatterns(): void
    {
        // Create .htaccess without HTTPS redirect patterns
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunHandlesCaseInsensitiveHtaccessPatterns(): void
    {
        // Test case insensitive pattern matching
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
REWRITECOND %{HTTPS} OFF
RewriteRule ^(.*)$ HTTPS://%{HTTP_HOST}/$1 [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        // The check uses stripos which is case-insensitive
        // Contains HTTPS patterns so hasHtaccessRedirect is true
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithForceSslDefaultValue(): void
    {
        // Don't set force_ssl, should use default of 0
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testWarningForForceSslTwoButNotHttpsIsDescriptive(): void
    {
        $this->app->set('force_ssl', 2);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Should mention checking SSL certificate
        $this->assertStringContainsString('certificate', strtolower($result->description));
    }

    public function testRunReturnsGoodWhenForceSslEntireSiteAndHttps(): void
    {
        // Force SSL enabled for entire site AND currently using HTTPS - optimal
        $this->app->set('force_ssl', 2);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enforced', strtolower($result->description));
        $this->assertStringContainsString('entire site', strtolower($result->description));
    }

    public function testRunReturnsGoodWhenHtaccessRedirectAndHttps(): void
    {
        // .htaccess redirect configured AND currently using HTTPS
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('htaccess', strtolower($result->description));
    }

    public function testRunReturnsWarningWhenHttpsButNoRedirect(): void
    {
        // Using HTTPS but no automatic redirect configured
        $this->app->set('force_ssl', 0);
        Uri::setMockSsl(true);
        // No .htaccess or no redirect patterns

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('redirect', strtolower($result->description));
    }

    public function testRunReturnsWarningWhenHttpsAndEmptyHtaccess(): void
    {
        // Using HTTPS but .htaccess has no redirect patterns
        file_put_contents($this->htaccessPath, 'RewriteEngine On');
        $this->app->set('force_ssl', 0);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not be redirected', $result->description);
    }

    public function testGoodDescriptionMentionsJoomlaConfigForForceSsl(): void
    {
        $this->app->set('force_ssl', 2);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Joomla', $result->description);
    }

    public function testGoodDescriptionMentionsHtaccessForHtaccessRedirect(): void
    {
        $htaccessContent = <<<'HTACCESS'
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('.htaccess', $result->description);
    }

    public function testFallbackGoodCaseWhenHtaccessRedirectButNotHttps(): void
    {
        // .htaccess has redirect patterns but not currently on HTTPS
        // This falls to the final "Good" fallback
        $htaccessContent = <<<'HTACCESS'
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);
        $this->app->set('force_ssl', 0);
        // isHttps = false (default)

        $result = $this->check->run();

        // This case hits the final fallback since:
        // - forceSsl != 2
        // - forceSsl != 1
        // - not critical (hasHtaccessRedirect is true OR isHttps is false... but htaccess exists)
        // - falls to final Good
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
