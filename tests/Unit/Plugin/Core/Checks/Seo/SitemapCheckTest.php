<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\SitemapCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SitemapCheck::class)]
class SitemapCheckTest extends TestCase
{
    private SitemapCheck $sitemapCheck;

    private string $sitemapPath;

    protected function setUp(): void
    {
        $this->sitemapCheck = new SitemapCheck();
        $this->sitemapPath = JPATH_ROOT . '/sitemap.xml';

        // Ensure JPATH_ROOT exists
        if (! is_dir(JPATH_ROOT)) {
            mkdir(JPATH_ROOT, 0777, true);
        }

        // Clean up any existing sitemap.xml
        if (file_exists($this->sitemapPath)) {
            unlink($this->sitemapPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up sitemap.xml after each test
        if (file_exists($this->sitemapPath)) {
            unlink($this->sitemapPath);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.sitemap', $this->sitemapCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->sitemapCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->sitemapCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->sitemapCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenSitemapNotFound(): void
    {
        // No sitemap.xml exists
        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not found', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenValidUrlsetSitemapExists(): void
    {
        $sitemapContent = <<<'SITEMAP'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
        <lastmod>2024-01-01</lastmod>
    </url>
</urlset>
SITEMAP;
        file_put_contents($this->sitemapPath, $sitemapContent);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('present', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenValidSitemapIndexExists(): void
    {
        $sitemapContent = <<<'SITEMAP'
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap-posts.xml</loc>
        <lastmod>2024-01-01</lastmod>
    </sitemap>
</sitemapindex>
SITEMAP;
        file_put_contents($this->sitemapPath, $sitemapContent);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenSitemapIsEmpty(): void
    {
        file_put_contents($this->sitemapPath, '');

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('empty', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSitemapIsWhitespaceOnly(): void
    {
        file_put_contents($this->sitemapPath, "   \n\t  \n");

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('empty', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSitemapHasInvalidXml(): void
    {
        $invalidXml = <<<'SITEMAP'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
    <!-- Missing closing tag -->
SITEMAP;
        file_put_contents($this->sitemapPath, $invalidXml);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('invalid XML', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenXmlLacksSitemapStructure(): void
    {
        // Valid XML but not a sitemap (missing urlset or sitemapindex)
        $nonSitemapXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Not a sitemap</title>
    </channel>
</rss>
XML;
        file_put_contents($this->sitemapPath, $nonSitemapXml);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('valid sitemap structure', $healthCheckResult->description);
    }

    public function testRunHandlesCaseInsensitiveUrlset(): void
    {
        // Test that we detect urlset regardless of case
        $sitemapContent = <<<'SITEMAP'
<?xml version="1.0" encoding="UTF-8"?>
<URLSET xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
    </url>
</URLSET>
SITEMAP;
        file_put_contents($this->sitemapPath, $sitemapContent);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunHandlesCaseInsensitiveSitemapindex(): void
    {
        // Test that we detect sitemapindex regardless of case
        $sitemapContent = <<<'SITEMAP_WRAP'
        <?xml version="1.0" encoding="UTF-8"?>
        <SITEMAPINDEX xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            <sitemap>
                <loc>https://example.com/sitemap-posts.xml</loc>
            </sitemap>
        </SITEMAPINDEX>
        SITEMAP_WRAP;
        file_put_contents($this->sitemapPath, $sitemapContent);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunNeverReturnsCritical(): void
    {
        // Even with invalid sitemap, should only return warning
        file_put_contents($this->sitemapPath, 'completely invalid content');

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunHandlesMinimalValidSitemap(): void
    {
        // Minimal valid sitemap with just urlset element
        $sitemapContent = '<?xml version="1.0"?><urlset></urlset>';
        file_put_contents($this->sitemapPath, $sitemapContent);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunHandlesSitemapWithBom(): void
    {
        // UTF-8 BOM followed by valid XML
        $bom = "\xEF\xBB\xBF";
        $sitemapContent = $bom . '<?xml version="1.0"?><urlset></urlset>';
        file_put_contents($this->sitemapPath, $sitemapContent);

        $healthCheckResult = $this->sitemapCheck->run();

        // Should handle BOM gracefully
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }
}
