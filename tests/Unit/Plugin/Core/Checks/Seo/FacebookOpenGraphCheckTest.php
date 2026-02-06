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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\FacebookOpenGraphCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FacebookOpenGraphCheck::class)]
class FacebookOpenGraphCheckTest extends TestCase
{
    private FacebookOpenGraphCheck $facebookOpenGraphCheck;

    protected function setUp(): void
    {
        $this->facebookOpenGraphCheck = new FacebookOpenGraphCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.facebook_open_graph', $this->facebookOpenGraphCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->facebookOpenGraphCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->facebookOpenGraphCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->facebookOpenGraphCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenAllOpenGraphTagsPresent(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta property="og:title" content="My Site Title">
    <meta property="og:description" content="My site description">
    <meta property="og:image" content="https://example.com/image.jpg">
    <meta property="og:url" content="https://example.com/">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('All essential', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithFacebookAppId(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta property="og:title" content="My Site Title">
    <meta property="og:description" content="My site description">
    <meta property="og:image" content="https://example.com/image.jpg">
    <meta property="og:url" content="https://example.com/">
    <meta property="fb:app_id" content="123456789">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Facebook App ID', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenMissingOgTags(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>My Site</title>
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Missing', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSomeTtagsMissing(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta property="og:title" content="My Site Title">
    <meta property="og:description" content="My site description">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('og:image', $healthCheckResult->description);
        $this->assertStringContainsString('og:url', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenHttpError(): void
    {
        $httpClient = MockHttpFactory::createWithGetResponse(500, '');
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('HTTP 500', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenConnectionFails(): void
    {
        $httpClient = MockHttpFactory::createThatThrows('Connection refused');
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to check', $healthCheckResult->description);
    }

    public function testDetectsContentBeforePropertyOrder(): void
    {
        // Test tags with content attribute before property attribute
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta content="My Site Title" property="og:title">
    <meta content="My site description" property="og:description">
    <meta content="https://example.com/image.jpg" property="og:image">
    <meta content="https://example.com/" property="og:url">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testResultMetadata(): void
    {
        $html = '<html><head></head><body></body></html>';
        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->facebookOpenGraphCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->facebookOpenGraphCheck->run();

        $this->assertSame('seo.facebook_open_graph', $healthCheckResult->slug);
        $this->assertSame('seo', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }
}
