<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use HealthChecker\Tests\Utilities\MockHttpFactory;
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

    // ---- Phase 1: Physical file tests (existing behavior) ----

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
        $this->assertStringContainsString('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD', $healthCheckResult->description);
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
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_3',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsWarningWhenSitemapIsWhitespaceOnly(): void
    {
        file_put_contents($this->sitemapPath, "   \n\t  \n");

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_3',
            $healthCheckResult->description,
        );
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
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_4',
            $healthCheckResult->description,
        );
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
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_5',
            $healthCheckResult->description,
        );
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

    // ---- Phase 2: HTTP fallback tests (new behavior) ----

    public function testRunReturnsGoodWhenDynamicSitemapServedViaHttp(): void
    {
        // No physical file exists — HTTP returns valid sitemap
        $sitemapXml = <<<'SITEMAP'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/</loc>
    </url>
</urlset>
SITEMAP;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $sitemapXml);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_2',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsGoodWhenDynamicSitemapIndexServedViaHttp(): void
    {
        // No physical file — HTTP returns a sitemapindex (e.g. PWT Sitemap after redirect)
        $sitemapXml = <<<'SITEMAP'
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap-articles.xml</loc>
    </sitemap>
</sitemapindex>
SITEMAP;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $sitemapXml);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_2',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsWarningWhenHttpReturnsNon200(): void
    {
        // No physical file, HTTP returns 404
        $httpClient = MockHttpFactory::createWithGetResponse(404);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsWarningWhenAllHttpPathsReturnEmptyBody(): void
    {
        // No physical file, all HTTP paths return 200 but empty body — skips to warning
        $httpClient = MockHttpFactory::createWithGetResponse(200, '');
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsWarningWhenAllHttpPathsReturnInvalidXml(): void
    {
        // No physical file, all HTTP paths return 200 but invalid XML — skips to warning
        $httpClient = MockHttpFactory::createWithGetResponse(200, '<html><body>Not XML</body>');
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsWarningWhenAllHttpPathsReturnXmlWithoutSitemapStructure(): void
    {
        // No physical file, all HTTP paths return valid XML but not sitemap structure
        $xmlContent = '<?xml version="1.0"?><rss><channel><title>Feed</title></channel></rss>';
        $httpClient = MockHttpFactory::createWithGetResponse(200, $xmlContent);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsWarningWhenHttpThrowsException(): void
    {
        // No physical file, network failure
        $httpClient = MockHttpFactory::createThatThrows('Connection timed out');
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING',
            $healthCheckResult->description,
        );
    }

    public function testRunPrefersPhysicalFileOverHttp(): void
    {
        // Physical file exists AND HTTP client is set — should use file, not HTTP
        $sitemapContent = '<?xml version="1.0"?><urlset></urlset>';
        file_put_contents($this->sitemapPath, $sitemapContent);

        // Set HTTP client that would return different content (should not be called)
        $httpClient = MockHttpFactory::createThatThrows('Should not be called');
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        // Should use GOOD (file-based), not GOOD_2 (HTTP-based)
        $this->assertStringContainsString('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD', $healthCheckResult->description);
        $this->assertStringNotContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_2',
            $healthCheckResult->description,
        );
    }

    public function testHttpFallbackNeverReturnsCritical(): void
    {
        // Even with HTTP returning invalid content, should only return warning
        $httpClient = MockHttpFactory::createWithGetResponse(200, 'completely invalid content');
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    // ---- Phase 3: Alternative sitemap path tests ----

    public function testRunReturnsGoodWhenSitemapFoundAtXmlSitemap(): void
    {
        // /sitemap.xml returns 404, but /xml-sitemap returns valid sitemap
        $sitemapXml = '<?xml version="1.0"?><urlset></urlset>';
        $httpClient = MockHttpFactory::createWithUrlResponses([
            'sitemap.xml' => [
                'code' => 404,
            ],
            'xml-sitemap' => [
                'code' => 200,
                'body' => $sitemapXml,
            ],
        ]);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_3',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsGoodWhenSitemapFoundAtXmlSitemapXml(): void
    {
        // /sitemap.xml and /xml-sitemap both 404, but /xml-sitemap.xml works
        $sitemapXml = '<?xml version="1.0"?><sitemapindex></sitemapindex>';
        $httpClient = MockHttpFactory::createWithUrlResponses([
            // xml-sitemap.xml contains xml-sitemap, so order matters — match specific first
            'xml-sitemap.xml' => [
                'code' => 200,
                'body' => $sitemapXml,
            ],
            'sitemap.xml' => [
                'code' => 404,
            ],
            'xml-sitemap' => [
                'code' => 404,
            ],
        ]);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_3',
            $healthCheckResult->description,
        );
    }

    public function testRunPrefersSitemapXmlOverAlternativePaths(): void
    {
        // All paths return valid sitemap — should use /sitemap.xml (first in list)
        $sitemapXml = '<?xml version="1.0"?><urlset></urlset>';
        $httpClient = MockHttpFactory::createWithGetResponse(200, $sitemapXml);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_2',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsWarningWhenAllPathsFail(): void
    {
        // All paths return 404
        $httpClient = MockHttpFactory::createWithGetResponse(404);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING',
            $healthCheckResult->description,
        );
    }

    public function testRunSkipsAlternativePathWithInvalidXml(): void
    {
        // /sitemap.xml 404, /xml-sitemap returns invalid XML, /xml-sitemap.xml returns valid
        $validXml = '<?xml version="1.0"?><urlset></urlset>';
        $httpClient = MockHttpFactory::createWithUrlResponses([
            'xml-sitemap.xml' => [
                'code' => 200,
                'body' => $validXml,
            ],
            'sitemap.xml' => [
                'code' => 404,
            ],
            'xml-sitemap' => [
                'code' => 200,
                'body' => 'not xml at all',
            ],
        ]);
        $this->sitemapCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->sitemapCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}
