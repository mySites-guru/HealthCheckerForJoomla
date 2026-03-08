<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * X/Twitter Cards Meta Tags Health Check
 *
 * This check fetches your site's homepage and verifies that X/Twitter Card
 * meta tags are present, which control how your content appears when shared on X.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * X (formerly Twitter) uses Card meta tags to create rich previews when your
 * pages are shared. Without them, posts linking to your site show plain text
 * links instead of attractive cards with images and descriptions. Rich previews
 * dramatically increase engagement and click-through rates from X shares.
 *
 * Required tags for X/Twitter Cards:
 * - twitter:card (card type: summary, summary_large_image, etc.)
 * - twitter:title (post preview title)
 * - twitter:description (post preview description)
 *
 * Recommended tags:
 * - twitter:image (preview image URL)
 * - twitter:site (X username of site, e.g., @yoursite)
 *
 * Note: X also falls back to Open Graph tags if Twitter-specific tags are missing.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Essential X/Twitter Card meta tags are present on your homepage,
 * ensuring rich post previews on X.
 *
 * WARNING: Some or all X/Twitter Card meta tags are missing. Posts may display
 * poorly on X. Install a social meta extension or add the tags to your template.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class TwitterCardsCheck extends AbstractHealthCheck
{
    /**
     * HTTP request timeout in seconds.
     */
    private const HTTP_TIMEOUT_SECONDS = 10;

    /**
     * Required Twitter Card tags.
     *
     * @var array<string>
     */
    private const REQUIRED_TAGS = ['twitter:card', 'twitter:title', 'twitter:description'];

    /**
     * Open Graph fallback tags that X accepts.
     *
     * @var array<string, string>
     */
    private const OG_FALLBACKS = [
        'twitter:title' => 'og:title',
        'twitter:description' => 'og:description',
        'twitter:image' => 'og:image',
    ];

    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'seo.twitter_cards'
     */
    public function getSlug(): string
    {
        return 'seo.twitter_cards';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'seo'
     */
    public function getCategory(): string
    {
        return 'seo';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Seo/TwitterCardsCheck.php';
    }

    /**
     * Perform the X/Twitter Cards meta tags check.
     *
     * Fetches the site's homepage and checks for the presence of essential
     * Twitter Card meta tags required for optimal X sharing. Also checks for
     * Open Graph fallbacks that X accepts.
     *
     * @return HealthCheckResult Good if essential tags found, Warning if missing
     */
    protected function performCheck(): HealthCheckResult
    {
        try {
            $siteUrl = Uri::root();
            $http = $this->getHttpClient();
            $response = $http->get($siteUrl, [], self::HTTP_TIMEOUT_SECONDS);

            if ($response->code !== 200) {
                return $this->warning(
                    Text::sprintf('COM_HEALTHCHECKER_CHECK_SEO_TWITTER_CARDS_WARNING', $response->code),
                );
            }

            $html = $response->body;
            $twitterTags = $this->findTwitterTags($html);
            $ogTags = $this->findOpenGraphTags($html);

            // Check which required tags are present (including OG fallbacks)
            $missingTags = [];
            $usingFallbacks = [];

            foreach (self::REQUIRED_TAGS as $tag) {
                if (isset($twitterTags[$tag])) {
                    // Tag is present directly
                    continue;
                }

                // Check if OG fallback exists
                if (isset(self::OG_FALLBACKS[$tag]) && isset($ogTags[self::OG_FALLBACKS[$tag]])) {
                    $usingFallbacks[] = $tag;
                    continue;
                }

                $missingTags[] = $tag;
            }

            // Check for twitter:image (recommended but not required)
            $hasImage = isset($twitterTags['twitter:image']) || isset($ogTags['og:image']);

            if ($missingTags === []) {
                $message = Text::_('COM_HEALTHCHECKER_CHECK_SEO_TWITTER_CARDS_GOOD');

                if ($usingFallbacks !== []) {
                    $message .= ' ' . Text::sprintf(
                        'COM_HEALTHCHECKER_CHECK_SEO_TWITTER_CARDS_GOOD_FALLBACKS',
                        implode(', ', $usingFallbacks),
                    );
                }

                if (! $hasImage) {
                    $message .= ' ' . Text::_('COM_HEALTHCHECKER_CHECK_SEO_TWITTER_CARDS_GOOD_NOIMAGE');
                }

                return $this->good($message);
            }

            // Some tags are missing
            $foundCount = \count(self::REQUIRED_TAGS) - \count($missingTags);

            return $this->warning(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_SEO_TWITTER_CARDS_WARNING_2',
                    implode(', ', $missingTags),
                    $foundCount,
                    \count(self::REQUIRED_TAGS),
                ),
            );
        } catch (\Exception $exception) {
            return $this->warning(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_SEO_TWITTER_CARDS_WARNING_3', $exception->getMessage()),
            );
        }
    }

    /**
     * Parse HTML and find Twitter Card meta tags.
     *
     * @param string $html The HTML content to parse
     *
     * @return array<string, string> Array of tag name => content pairs
     */
    private function findTwitterTags(string $html): array
    {
        $tags = [];

        // Match Twitter meta tags with either name or property attribute:
        // <meta name="twitter:*" content="..."> or <meta property="twitter:*" content="...">
        // Some themes/extensions use "property" instead of "name" for Twitter tags.
        foreach (['name', 'property'] as $attribute) {
            if (preg_match_all(
                '/<meta\s+[^>]*' . $attribute . '=["\']?(twitter:[^"\'>\s]+)["\']?\s+[^>]*content=["\']?([^"\'>\s][^"\']*)["\']?[^>]*>/i',
                $html,
                $matches,
                PREG_SET_ORDER,
            )) {
                foreach ($matches as $match) {
                    $tags[$match[1]] ??= $match[2];
                }
            }

            // Also check for reverse order: content before name/property
            if (preg_match_all(
                '/<meta\s+[^>]*content=["\']?([^"\'>\s][^"\']*)["\']?\s+[^>]*' . $attribute . '=["\']?(twitter:[^"\'>\s]+)["\']?[^>]*>/i',
                $html,
                $matches,
                PREG_SET_ORDER,
            )) {
                foreach ($matches as $match) {
                    $tags[$match[2]] ??= $match[1];
                }
            }
        }

        return $tags;
    }

    /**
     * Parse HTML and find Open Graph meta tags (for fallback checking).
     *
     * @param string $html The HTML content to parse
     *
     * @return array<string, string> Array of tag name => content pairs
     */
    private function findOpenGraphTags(string $html): array
    {
        $tags = [];

        // Match Open Graph meta tags: <meta property="og:*" content="...">
        if (preg_match_all(
            '/<meta\s+[^>]*property=["\']?(og:[^"\'>\s]+)["\']?\s+[^>]*content=["\']?([^"\'>\s][^"\']*)["\']?[^>]*>/i',
            $html,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $tags[$match[1]] = $match[2];
            }
        }

        // Also check for reverse order: content before property
        if (preg_match_all(
            '/<meta\s+[^>]*content=["\']?([^"\'>\s][^"\']*)["\']?\s+[^>]*property=["\']?(og:[^"\'>\s]+)["\']?[^>]*>/i',
            $html,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $tags[$match[2]] = $match[1];
            }
        }

        return $tags;
    }
}
