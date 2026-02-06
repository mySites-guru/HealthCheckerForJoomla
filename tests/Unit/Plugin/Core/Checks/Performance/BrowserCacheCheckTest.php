<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\BrowserCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BrowserCacheCheck::class)]
class BrowserCacheCheckTest extends TestCase
{
    private BrowserCacheCheck $browserCacheCheck;

    private string $htaccessPath;

    protected function setUp(): void
    {
        $this->browserCacheCheck = new BrowserCacheCheck();

        // Create temp directory if it doesn't exist
        if (! is_dir(JPATH_ROOT)) {
            mkdir(JPATH_ROOT, 0777, true);
        }

        $this->htaccessPath = JPATH_ROOT . '/.htaccess';

        // Remove any existing .htaccess
        if (file_exists($this->htaccessPath)) {
            unlink($this->htaccessPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up .htaccess after each test
        if (file_exists($this->htaccessPath)) {
            unlink($this->htaccessPath);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.browser_cache', $this->browserCacheCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->browserCacheCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->browserCacheCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->browserCacheCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenHtaccessNotFound(): void
    {
        // No .htaccess file exists
        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('.htaccess file not found', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenHtaccessIsEmpty(): void
    {
        file_put_contents($this->htaccessPath, '');

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('.htaccess file is empty', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenExpiresByTypeFound(): void
    {
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
</IfModule>
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Expires headers', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenExpiresDefaultFound(): void
    {
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 month"
</IfModule>
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Expires headers', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenCacheControlFound(): void
    {
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_headers.c>
    <FilesMatch "\.(jpg|jpeg|png|gif|ico)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
</IfModule>
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Cache-Control headers', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenBothExpiresAndCacheControlFound(): void
    {
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
<IfModule mod_headers.c>
    Header set Cache-Control "max-age=31536000, public"
</IfModule>
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Expires headers', $healthCheckResult->description);
        $this->assertStringContainsString('Cache-Control headers', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenOnlyModExpiresReferenced(): void
    {
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_expires.c>
    # Browser caching will be configured later
</IfModule>
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('mod_expires module reference found', $healthCheckResult->description);
        $this->assertStringContainsString('no ExpiresByType rules detected', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenNoCachingRulesFound(): void
    {
        $htaccessContent = <<<'HTACCESS'
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No browser caching rules detected', $healthCheckResult->description);
    }

    public function testRunIsCaseInsensitiveForDirectives(): void
    {
        // Test lowercase expiresByType
        $htaccessContent = 'expiresbytype image/jpeg "access plus 1 year"';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunNeverReturnsCritical(): void
    {
        // Even with no caching rules, should only return warning
        file_put_contents($this->htaccessPath, 'RewriteEngine On');

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunIsCaseInsensitiveForCacheControl(): void
    {
        $htaccessContent = 'header set cache-control "max-age=31536000"';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunIsCaseInsensitiveForExpiresDefault(): void
    {
        $htaccessContent = 'expiresdefault "access plus 1 month"';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunDescriptionMentionsBothMethodsWhenPresent(): void
    {
        $htaccessContent = <<<'HTACCESS'
ExpiresByType image/jpeg "access plus 1 year"
Header set Cache-Control "max-age=31536000"
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Expires headers', $healthCheckResult->description);
        $this->assertStringContainsString('Cache-Control headers', $healthCheckResult->description);
        $this->assertStringContainsString(' and ', $healthCheckResult->description);
    }

    public function testRunHandlesHtaccessWithOnlyComments(): void
    {
        $htaccessContent = <<<'HTACCESS'
# This is a comment
# Another comment
# No actual directives
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No browser caching rules', $healthCheckResult->description);
    }

    public function testRunHandlesHtaccessWithModExpiresAndRules(): void
    {
        // If mod_expires is referenced AND has ExpiresByType, should be good
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
</IfModule>
HTACCESS;
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWarningMessageSuggestsAction(): void
    {
        $htaccessContent = 'RewriteEngine On';
        file_put_contents($this->htaccessPath, $htaccessContent);

        $healthCheckResult = $this->browserCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Consider adding', $healthCheckResult->description);
    }
}
