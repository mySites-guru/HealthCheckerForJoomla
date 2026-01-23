<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Meta Keywords Health Check
 *
 * This check reports on the status of meta keywords in Global Configuration,
 * providing historical context about their (lack of) SEO value.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Meta keywords were once important for SEO but have been ignored by major search
 * engines since 2009 due to widespread abuse and spam. Google, Bing, and other
 * major search engines do not use meta keywords for ranking. This check is purely
 * informational to help site owners understand that time spent on meta keywords
 * is better spent on quality content.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either meta keywords are not set (which is fine since they don't affect
 * rankings), or they are set (also fine, just not beneficial for SEO). This
 * check always returns GOOD status since meta keywords are neither helpful
 * nor harmful to SEO.
 *
 * WARNING: This check does not return warnings.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MetaKeywordsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'seo.meta_keywords'
     */
    public function getSlug(): string
    {
        return 'seo.meta_keywords';
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
     * Perform the meta keywords health check.
     *
     * Reports on meta keywords status in Global Configuration with educational
     * context about their obsolescence. Always returns GOOD status since meta
     * keywords are neither harmful nor beneficial for modern SEO (all major
     * search engines have ignored them since 2009).
     *
     * @return HealthCheckResult The check result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        // Retrieve the global meta keywords configuration value.
        // This setting is still present in Joomla for legacy reasons but
        // has no impact on SEO since Google (2009), Bing, Yahoo, and other
        // major search engines stopped using meta keywords due to spam abuse.
        $metaKeys = Factory::getApplication()->get('MetaKeys', '');

        // Meta keywords are set - not harmful, just not useful for SEO.
        // Some site owners still populate this field out of habit or
        // misunderstanding of current SEO best practices.
        if (! in_array(trim((string) $metaKeys), ['', '0'], true)) {
            return $this->good(
                'Meta keywords are set in Global Configuration. Note: Major search engines no longer use meta keywords for ranking. Focus on quality content instead.',
            );
        }

        // Meta keywords are empty - this is the modern best practice.
        // Time is better spent on quality content, proper headings, and
        // descriptive meta descriptions that search engines actually use.
        return $this->good(
            'Meta keywords are not set. This is fine - major search engines have not used meta keywords for ranking since 2009.',
        );
    }
}
