<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Facebook Open Graph Meta Tags Health Check
 *
 * This check fetches your site's homepage and verifies that Facebook Open Graph
 * meta tags are present, which control how your content appears when shared on Facebook.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Facebook uses Open Graph meta tags to create rich previews when your pages are
 * shared. Without them, Facebook makes guesses that often result in poor previews
 * with wrong images or truncated text. Proper Open Graph tags significantly
 * increase click-through rates from Facebook shares.
 *
 * Required tags for optimal Facebook sharing:
 * - og:title (share preview title)
 * - og:description (share preview description)
 * - og:image (preview image, ideally 1200x630px)
 * - og:url (canonical URL)
 * - og:type (content type: website, article, etc.)
 *
 * Optional but recommended:
 * - fb:app_id (for Facebook Insights)
 * - og:site_name (your site's name)
 *
 * RESULT MEANINGS:
 *
 * GOOD: Essential Facebook Open Graph meta tags are present on your homepage,
 * ensuring rich share previews on Facebook.
 *
 * WARNING: Some or all Facebook Open Graph meta tags are missing. Shares may
 * display poorly on Facebook. Install a social meta extension or add the tags
 * to your template.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Uri\Uri;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class FacebookOpenGraphCheck extends AbstractHealthCheck
{
    /**
     * HTTP request timeout in seconds.
     */
    private const HTTP_TIMEOUT_SECONDS = 10;

    /**
     * Required Open Graph tags for Facebook.
     *
     * @var array<string>
     */
    private const REQUIRED_TAGS = ['og:title', 'og:description', 'og:image', 'og:url'];

    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'seo.facebook_open_graph'
     */
    public function getSlug(): string
    {
        return 'seo.facebook_open_graph';
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

    /**
     * Perform the Facebook Open Graph meta tags check.
     *
     * Fetches the site's homepage and checks for the presence of essential
     * Open Graph meta tags required for optimal Facebook sharing.
     *
     * @return HealthCheckResult Good if essential tags found, Warning if missing
     */
    protected function performCheck(): HealthCheckResult
    {
        try {
            $siteUrl = Uri::root();
            $http = HttpFactory::getHttp();
            $response = $http->get($siteUrl, [], self::HTTP_TIMEOUT_SECONDS);

            if ($response->code !== 200) {
                return $this->warning(
                    sprintf(
                        'Unable to fetch homepage (HTTP %d). Cannot verify Facebook Open Graph tags.',
                        $response->code,
                    ),
                );
            }

            $html = $response->body;
            $foundTags = $this->findOpenGraphTags($html);
            $missingTags = array_diff(self::REQUIRED_TAGS, array_keys($foundTags));

            // Check for optional but valuable fb:app_id
            $hasFbAppId = isset($foundTags['fb:app_id']);

            if ($missingTags === []) {
                $message = sprintf(
                    'All essential Facebook Open Graph tags found: %s.',
                    implode(', ', self::REQUIRED_TAGS),
                );

                if ($hasFbAppId) {
                    $message .= ' Facebook App ID is also configured for Insights.';
                }

                return $this->good($message);
            }

            // Some tags are missing
            $foundCount = \count(self::REQUIRED_TAGS) - \count($missingTags);

            return $this->warning(
                sprintf(
                    'Missing Facebook Open Graph tags: %s. Found %d of %d required tags. Add these tags to improve Facebook share previews.',
                    implode(', ', $missingTags),
                    $foundCount,
                    \count(self::REQUIRED_TAGS),
                ),
            );
        } catch (\Exception $exception) {
            return $this->warning('Unable to check Facebook Open Graph tags: ' . $exception->getMessage());
        }
    }

    /**
     * Parse HTML and find Open Graph meta tags.
     *
     * @param string $html The HTML content to parse
     *
     * @return array<string, string> Array of tag name => content pairs
     */
    private function findOpenGraphTags(string $html): array
    {
        $tags = [];

        // Match Open Graph meta tags: <meta property="og:*" content="...">
        // Also match fb:* tags
        if (preg_match_all(
            '/<meta\s+[^>]*property=["\']?(og:[^"\'>\s]+|fb:[^"\'>\s]+)["\']?\s+[^>]*content=["\']?([^"\'>\s][^"\']*)["\']?[^>]*>/i',
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
            '/<meta\s+[^>]*content=["\']?([^"\'>\s][^"\']*)["\']?\s+[^>]*property=["\']?(og:[^"\'>\s]+|fb:[^"\'>\s]+)["\']?[^>]*>/i',
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
