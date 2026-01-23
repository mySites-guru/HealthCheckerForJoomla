<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * PHP End-of-Life Status Health Check
 *
 * This check fetches PHP version lifecycle data from the endoflife.date API
 * and warns when the current PHP version is approaching or past its end-of-life date.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * PHP versions have defined support lifecycles. Each version receives:
 * - Active support: Bug fixes and security patches
 * - Security support: Security patches only
 * - End of life: No patches at all
 *
 * Running an EOL PHP version means your server has known, unpatched security
 * vulnerabilities that attackers actively exploit. Even "security only" mode
 * means you're missing bug fixes that could cause site instability.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Your PHP version is under active support with more than 90 days
 *       remaining. You're receiving both bug fixes and security patches.
 *
 * WARNING: Either your PHP version is in security-only support (no bug fixes),
 *          or active support ends within 90 days. Plan your PHP upgrade.
 *          Also shown if the API cannot be reached (graceful degradation).
 *
 * CRITICAL: Your PHP version is past its end-of-life date and receives NO
 *           security patches. Your server has known vulnerabilities.
 *           Upgrade PHP immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use Joomla\CMS\Http\HttpFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PhpEolCheck extends AbstractHealthCheck
{
    /**
     * API endpoint for PHP version lifecycle data.
     *
     * This endpoint provides structured JSON data about PHP version support
     * lifecycles including active support and end-of-life dates.
     */
    private const API_URL = 'https://endoflife.date/api/php.json';

    /**
     * Number of days before support ends to trigger a warning.
     *
     * If active support ends within this many days, a warning is issued
     * to give administrators time to plan their PHP upgrade.
     */
    private const WARNING_DAYS_THRESHOLD = 90;

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.php_eol'
     */
    public function getSlug(): string
    {
        return 'system.php_eol';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Check if the current PHP version is approaching or past its end-of-life date.
     *
     * Fetches PHP lifecycle data from the endoflife.date API and compares the current
     * PHP version against its support and EOL dates. Each PHP version goes through
     * three stages: active support (bug fixes + security), security-only support
     * (security patches only), and end-of-life (no patches at all).
     *
     * The check warns when:
     * - Active support ends within 90 days
     * - Version is in security-only mode
     * - Version is past EOL (critical)
     *
     * @return HealthCheckResult CRITICAL if past EOL date,
     *                           WARNING if API unreachable, in security-only mode, or support ending soon,
     *                           GOOD if under active support with time remaining
     */
    protected function performCheck(): HealthCheckResult
    {
        $currentVersion = PHP_VERSION;
        $cycle = $this->extractCycle($currentVersion);

        try {
            $eolData = $this->fetchEolData();
        } catch (\Exception $exception) {
            return $this->warning(
                sprintf(
                    'Unable to fetch PHP end-of-life data: %s. PHP %s is installed.',
                    $exception->getMessage(),
                    $currentVersion,
                ),
            );
        }

        $versionInfo = $this->findVersionInfo($eolData, $cycle);

        if ($versionInfo === null) {
            return $this->warning(
                sprintf(
                    'PHP %s lifecycle information not found in API. This may be a very new or very old version.',
                    $currentVersion,
                ),
            );
        }

        $today = new \DateTime();

        try {
            $supportDate = new \DateTime($versionInfo['support']);
            $eolDate = new \DateTime($versionInfo['eol']);
        } catch (\Exception) {
            return $this->warning(
                sprintf('PHP %s lifecycle dates could not be parsed from API response.', $currentVersion),
            );
        }

        // Past EOL - critical
        if ($today > $eolDate) {
            return $this->critical(
                sprintf(
                    'PHP %s reached end-of-life on %s and no longer receives security patches. Upgrade immediately.',
                    $currentVersion,
                    $eolDate->format('j M Y'),
                ),
            );
        }

        // Past active support (security only mode)
        if ($today > $supportDate) {
            $daysUntilEol = (int) $today->diff($eolDate)
                ->days;

            return $this->warning(
                sprintf(
                    'PHP %s is in security-only support. Active support ended %s. EOL in %d days (%s).',
                    $currentVersion,
                    $supportDate->format('j M Y'),
                    $daysUntilEol,
                    $eolDate->format('j M Y'),
                ),
            );
        }

        // Active support but ending soon
        $daysUntilSupportEnds = (int) $today->diff($supportDate)
            ->days;

        if ($daysUntilSupportEnds <= self::WARNING_DAYS_THRESHOLD) {
            return $this->warning(
                sprintf(
                    'PHP %s active support ends in %d days (%s). Plan your upgrade.',
                    $currentVersion,
                    $daysUntilSupportEnds,
                    $supportDate->format('j M Y'),
                ),
            );
        }

        // All good - active support with plenty of time
        return $this->good(
            sprintf(
                'PHP %s is under active support until %s (EOL: %s).',
                $currentVersion,
                $supportDate->format('j M Y'),
                $eolDate->format('j M Y'),
            ),
        );
    }

    /**
     * Extract the major.minor cycle from a full PHP version string.
     *
     * The endoflife.date API uses major.minor version cycles (e.g., "8.3")
     * while PHP_VERSION includes the patch number (e.g., "8.3.15"). This
     * method extracts just the major.minor portion for API lookups.
     *
     * @param string $version Full PHP version string (e.g., "8.3.15")
     *
     * @return string Major.minor version cycle (e.g., "8.3")
     */
    private function extractCycle(string $version): string
    {
        $parts = explode('.', $version);

        return $parts[0] . '.' . ($parts[1] ?? '0');
    }

    /**
     * Fetch PHP end-of-life data from the endoflife.date API.
     *
     * Makes an HTTP GET request to retrieve structured JSON data about PHP
     * version lifecycles. The data includes active support dates, security
     * support dates, and end-of-life dates for each PHP version.
     *
     * @return array<int, array<string, mixed>> Array of version information
     */
    private function fetchEolData(): array
    {
        $http = HttpFactory::getHttp();
        $response = $http->get(self::API_URL, [], 10);

        if ($response->code !== 200) {
            throw new \RuntimeException('API returned status ' . $response->code);
        }

        $data = json_decode((string) $response->body, true);

        if (! \is_array($data)) {
            throw new \RuntimeException('Invalid JSON response');
        }

        return $data;
    }

    /**
     * Find lifecycle information for a specific PHP version cycle.
     *
     * Searches the EOL data array for an entry matching the given cycle
     * (e.g., "8.3"). Returns the full version information including support
     * and EOL dates if found.
     *
     * @param array<int, array<string, mixed>> $eolData Complete EOL data from API
     * @param string                           $cycle   PHP version cycle to find (e.g., "8.3")
     *
     * @return array<string, mixed>|null Version information array or null if not found
     */
    private function findVersionInfo(array $eolData, string $cycle): ?array
    {
        foreach ($eolData as $version) {
            if (isset($version['cycle']) && $version['cycle'] === $cycle) {
                return $version;
            }
        }

        return null;
    }
}
