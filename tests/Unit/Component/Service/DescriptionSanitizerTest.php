<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Service;

use MySitesGuru\HealthChecker\Component\Administrator\Service\DescriptionSanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DescriptionSanitizer::class)]
final class DescriptionSanitizerTest extends TestCase
{
    private DescriptionSanitizer $descriptionSanitizer;

    protected function setUp(): void
    {
        $this->descriptionSanitizer = new DescriptionSanitizer();
    }

    public function testPlainTextPassesThroughUnchanged(): void
    {
        $input = 'This is a plain text description without any HTML.';
        $this->assertSame($input, $this->descriptionSanitizer->sanitize($input));
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', $this->descriptionSanitizer->sanitize(''));
    }

    public function testAllowsLineBreakTags(): void
    {
        $input = 'Line one<br>Line two<br/>Line three';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<br>', $result);
    }

    public function testAllowsParagraphTags(): void
    {
        $input = '<p>First paragraph</p><p>Second paragraph</p>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('</p>', $result);
    }

    public function testAllowsStrongAndBoldTags(): void
    {
        $input = 'This is <strong>important</strong> and <b>bold</b>.';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('</strong>', $result);
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('</b>', $result);
    }

    public function testAllowsEmphasisAndItalicTags(): void
    {
        $input = 'This is <em>emphasized</em> and <i>italic</i>.';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<em>', $result);
        $this->assertStringContainsString('</em>', $result);
        $this->assertStringContainsString('<i>', $result);
        $this->assertStringContainsString('</i>', $result);
    }

    public function testAllowsUnderlineTags(): void
    {
        $input = 'This is <u>underlined</u> text.';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<u>', $result);
        $this->assertStringContainsString('</u>', $result);
    }

    public function testAllowsCodeTags(): void
    {
        $input = 'Use the <code>phpinfo()</code> function.';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString('</code>', $result);
    }

    public function testAllowsPreTags(): void
    {
        $input = '<pre>Some preformatted code here</pre>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('</pre>', $result);
    }

    public function testAllowsUnorderedListTags(): void
    {
        $input = '<ul><li>Item one</li><li>Item two</li></ul>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('</ul>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('</li>', $result);
    }

    public function testAllowsOrderedListTags(): void
    {
        $input = '<ol><li>First</li><li>Second</li></ol>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('</ol>', $result);
    }

    public function testStripsScriptTags(): void
    {
        $input = 'Normal text<script>alert("XSS")</script>more text';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('Normal text', $result);
        $this->assertStringContainsString('more text', $result);
    }

    public function testStripsStyleTags(): void
    {
        $input = 'Normal text<style>body { display: none; }</style>more text';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('<style>', $result);
        $this->assertStringNotContainsString('</style>', $result);
    }

    public function testStripsIframeTags(): void
    {
        $input = 'Text<iframe src="https://evil.com"></iframe>more text';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('<iframe>', $result);
        $this->assertStringNotContainsString('</iframe>', $result);
        $this->assertStringNotContainsString('evil.com', $result);
    }

    public function testStripsAnchorTags(): void
    {
        $input = 'Click <a href="https://phishing.com">here</a> for more info.';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('<a ', $result);
        $this->assertStringNotContainsString('</a>', $result);
        $this->assertStringNotContainsString('phishing.com', $result);
        $this->assertStringContainsString('here', $result);
    }

    public function testStripsOnclickEventHandler(): void
    {
        $input = '<p onclick="alert(1)">Click me</p>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function testStripsOnerrorEventHandler(): void
    {
        $input = '<code onerror="alert(1)">test</code>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('onerror', $result);
    }

    public function testStripsOnloadEventHandler(): void
    {
        $input = '<p onload="malicious()">Content</p>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('onload', $result);
        $this->assertStringNotContainsString('malicious', $result);
    }

    public function testStripsOnmouseoverEventHandler(): void
    {
        $input = '<strong onmouseover="evil()">Hover</strong>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('onmouseover', $result);
    }

    public function testStripsJavascriptUrl(): void
    {
        $input = 'Text javascript:alert(1) more text';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testStripsStyleAttributes(): void
    {
        $input = '<p style="color: red;">Styled text</p>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringNotContainsString('style=', $result);
        $this->assertStringNotContainsString('color:', $result);
    }

    public function testComplexFormattedDescription(): void
    {
        $input = <<<'HTML'
<p>PHP version <strong>8.1.0</strong> detected.</p>
<p>This version is <em>outdated</em>. Consider upgrading to:</p>
<ul>
    <li><code>PHP 8.2</code> (recommended)</li>
    <li><code>PHP 8.3</code> (latest)</li>
</ul>
<p>Run: <pre>apt update && apt install php8.3</pre></p>
HTML;

        $result = $this->descriptionSanitizer->sanitize($input);

        // All allowed tags should be present
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<em>', $result);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString('<pre>', $result);

        // Content should be preserved
        $this->assertStringContainsString('PHP version', $result);
        $this->assertStringContainsString('8.1.0', $result);
        $this->assertStringContainsString('apt update', $result);
    }

    public function testXssAttackInAllowedTag(): void
    {
        $input = '<code><script>alert(document.cookie)</script></code>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<code>', $result);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('document.cookie', $result);
    }

    public function testMixedValidAndInvalidTags(): void
    {
        $input = '<p>Good</p><script>Bad</script><strong>Also good</strong><iframe>Evil</iframe>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<p>Good</p>', $result);
        $this->assertStringContainsString('<strong>Also good</strong>', $result);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('<iframe>', $result);
    }

    public function testSpecialCharactersInTextPreserved(): void
    {
        $input = 'Value is &lt; 100 and &gt; 50. Use &amp; for ampersand.';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        $this->assertStringContainsString('&amp;', $result);
    }

    public function testNestedAllowedTagsWork(): void
    {
        $input = '<p><strong>Bold <em>and italic</em></strong></p>';
        $result = $this->descriptionSanitizer->sanitize($input);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<em>', $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function xssPayloadsProvider(): array
    {
        return [
            'img onerror' => ['<img src=x onerror=alert(1)>', ''],
            'svg onload' => ['<svg onload=alert(1)>', ''],
            'body onload' => ['<body onload=alert(1)>', ''],
            'input onfocus' => ['<input onfocus=alert(1) autofocus>', ''],
            'marquee onstart' => ['<marquee onstart=alert(1)>', ''],
            'video onerror' => ['<video><source onerror="alert(1)">', ''],
            'object data' => ['<object data="javascript:alert(1)">', ''],
            'embed src' => ['<embed src="javascript:alert(1)">', ''],
            'form action' => ['<form action="javascript:alert(1)"><input type=submit>', ''],
            'base href' => ['<base href="javascript:alert(1)//">', ''],
        ];
    }

    #[DataProvider('xssPayloadsProvider')]
    public function testXssPayloadsAreStripped(string $payload, string $expected): void
    {
        $result = $this->descriptionSanitizer->sanitize($payload);

        // Should not contain script execution
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('onload', $result);
        $this->assertStringNotContainsString('onfocus', $result);
        $this->assertStringNotContainsString('onstart', $result);
    }
}
