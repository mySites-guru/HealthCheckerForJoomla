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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\TwitterCardsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TwitterCardsCheck::class)]
class TwitterCardsCheckTest extends TestCase
{
    private TwitterCardsCheck $twitterCardsCheck;

    protected function setUp(): void
    {
        $this->twitterCardsCheck = new TwitterCardsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.twitter_cards', $this->twitterCardsCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->twitterCardsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->twitterCardsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->twitterCardsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenAllTwitterCardsPresent(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="My Site Title">
    <meta name="twitter:description" content="My site description">
    <meta name="twitter:image" content="https://example.com/image.jpg">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('essential', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithOpenGraphFallbacks(): void
    {
        // Twitter card type is required, but other fields fall back to OG tags
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta name="twitter:card" content="summary">
    <meta property="og:title" content="My Site Title">
    <meta property="og:description" content="My site description">
    <meta property="og:image" content="https://example.com/image.jpg">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('fallback', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenMissingTwitterCardType(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta name="twitter:title" content="My Site Title">
    <meta name="twitter:description" content="My site description">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('twitter:card', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenAllTagsMissing(): void
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
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Missing', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenHttpError(): void
    {
        $httpClient = MockHttpFactory::createWithGetResponse(500, '');
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('HTTP 500', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenConnectionFails(): void
    {
        $httpClient = MockHttpFactory::createThatThrows('Connection refused');
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to check', $healthCheckResult->description);
    }

    public function testDetectsContentBeforeNameOrder(): void
    {
        // Test tags with content attribute before name attribute
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta content="summary_large_image" name="twitter:card">
    <meta content="My Site Title" name="twitter:title">
    <meta content="My site description" name="twitter:description">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testDetectsOpenGraphContentBeforePropertyOrder(): void
    {
        // Test OG fallback with reversed attribute order
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta name="twitter:card" content="summary">
    <meta content="My Site Title" property="og:title">
    <meta content="My site description" property="og:description">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testSuggestsImageWhenMissing(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="My Site Title">
    <meta name="twitter:description" content="My site description">
</head>
<body></body>
</html>
HTML;

        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('image', $healthCheckResult->description);
    }

    public function testResultMetadata(): void
    {
        $html = '<html><head></head><body></body></html>';
        $httpClient = MockHttpFactory::createWithGetResponse(200, $html);
        $this->twitterCardsCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->twitterCardsCheck->run();

        $this->assertSame('seo.twitter_cards', $healthCheckResult->slug);
        $this->assertSame('seo', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }
}
