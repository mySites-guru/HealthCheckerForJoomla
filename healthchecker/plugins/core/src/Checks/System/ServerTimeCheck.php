<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Server Time Health Check
 *
 * This check verifies that the server time is accurate by comparing it against
 * the HTTP Date header from Google's servers (which are synchronized to Google's
 * atomic clocks). Accurate server time is critical for scheduled tasks, content
 * publishing, session management, security tokens, and log timestamps.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Incorrect server time can cause:
 * - Scheduled tasks to run at wrong times or not at all
 * - Content to appear published prematurely or late
 * - Session timeouts to behave unexpectedly
 * - Security tokens (CSRF, JWT) to be invalid due to time skew
 * - Log entries to have misleading timestamps
 * - SSL/TLS certificate validation failures
 * - Cron jobs and backups to run at wrong times
 *
 * RESULT MEANINGS:
 *
 * GOOD: Server time is within 30 seconds of the authoritative time source.
 *       Time synchronization is working correctly.
 *
 * WARNING: Server time differs from the time source by 30 seconds to 5 minutes.
 *          This may cause minor issues with scheduled tasks. Consider checking
 *          NTP synchronization on the server.
 *
 * CRITICAL: Server time differs by more than 5 minutes from the time source.
 *           This will cause significant issues with scheduled tasks, security
 *           tokens, and SSL certificates. Immediate action required.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use Joomla\CMS\Http\HttpFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ServerTimeCheck extends AbstractHealthCheck
{
    /**
     * Warning threshold in seconds (30 seconds).
     */
    private const WARNING_THRESHOLD_SECONDS = 30;

    /**
     * Critical threshold in seconds (5 minutes).
     */
    private const CRITICAL_THRESHOLD_SECONDS = 300;

    /**
     * HTTP request timeout in seconds.
     */
    private const HTTP_TIMEOUT_SECONDS = 5;

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.server_time'
     */
    public function getSlug(): string
    {
        return 'system.server_time';
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
     * Perform the server time health check.
     *
     * Compares the server's current time against the HTTP Date header from
     * Google's servers to detect clock drift. Google's servers are synchronized
     * to atomic clocks, making them a reliable time reference.
     *
     * @return HealthCheckResult Good if time is accurate, Warning/Critical if drifted
     */
    /**
     * Perform the Server Time health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $timezone = date_default_timezone_get();
        $dateTimeZone = new \DateTimeZone($timezone);

        // Try to fetch authoritative time from HTTP Date header
        $result = $this->fetchTimeFromHttpHeader();

        // If we couldn't reach any time source, fall back to informational display
        if ($result === null) {
            $serverTime = new \DateTimeImmutable('now', $dateTimeZone);

            return $this->good(
                sprintf(
                    'Server time: %s (%s). Unable to verify against external time source.',
                    $serverTime->format('Y-m-d H:i:s'),
                    $timezone,
                ),
            );
        }

        [$serverTime, $externalTime, $source] = $result;

        // Calculate the difference in seconds
        $diffSeconds = abs($serverTime->getTimestamp() - $externalTime->getTimestamp());

        // Format times for display (in server's local timezone)
        $serverTimeLocal = $serverTime->setTimezone($dateTimeZone)
            ->format('Y-m-d H:i:s');
        $externalTimeLocal = $externalTime->setTimezone($dateTimeZone)
            ->format('Y-m-d H:i:s');

        // Check against critical threshold (5 minutes)
        if ($diffSeconds > self::CRITICAL_THRESHOLD_SECONDS) {
            return $this->critical(
                sprintf(
                    'Server time is off by %s. Server: %s, Actual: %s (%s). Check NTP synchronization immediately.',
                    $this->formatTimeDiff($diffSeconds),
                    $serverTimeLocal,
                    $externalTimeLocal,
                    $timezone,
                ),
            );
        }

        // Check against warning threshold (30 seconds)
        if ($diffSeconds > self::WARNING_THRESHOLD_SECONDS) {
            return $this->warning(
                sprintf(
                    'Server time is off by %s. Server: %s, Actual: %s (%s). Consider checking NTP synchronization.',
                    $this->formatTimeDiff($diffSeconds),
                    $serverTimeLocal,
                    $externalTimeLocal,
                    $timezone,
                ),
            );
        }

        return $this->good(
            sprintf(
                'Server time is accurate: %s (%s). Verified against %s (drift: %ds).',
                $serverTimeLocal,
                $timezone,
                $source,
                $diffSeconds,
            ),
        );
    }

    /**
     * Fetch the current time from HTTP Date header of reliable sources.
     *
     * Uses a HEAD request to Google or Cloudflare to get the Date header,
     * which contains the server's current time synchronized to atomic clocks.
     * This is more reliable than third-party time APIs.
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable, 2: string}|null
     *         Array of [serverTime, externalTime, source] or null if unavailable
     */
    private function fetchTimeFromHttpHeader(): ?array
    {
        // List of reliable time sources (use HEAD request for minimal data transfer)
        $sources = [
            'https://www.google.com' => 'Google',
            'https://www.cloudflare.com' => 'Cloudflare',
        ];

        foreach ($sources as $url => $name) {
            $result = $this->tryFetchTimeFromUrl($url, $name);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Try to fetch time from a specific URL's Date header.
     *
     * @param string $url  The URL to fetch
     * @param string $name The friendly name of the source
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable, 2: string}|null
     */
    private function tryFetchTimeFromUrl(string $url, string $name): ?array
    {
        try {
            // Record server time immediately before the request
            $serverTimeBefore = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            $http = HttpFactory::getHttp();

            // Use HEAD request - we only need headers, not body
            $response = $http->head($url, [], self::HTTP_TIMEOUT_SECONDS);

            // Record server time immediately after the request
            $serverTimeAfter = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            // Get the Date header from the response
            $dateHeader = $response->headers['Date'] ?? $response->headers['date'] ?? null;

            if ($dateHeader === null) {
                return null;
            }

            // Handle array headers (Joomla HTTP client may return arrays)
            if (is_array($dateHeader)) {
                $dateHeader = $dateHeader[0] ?? null;
                if ($dateHeader === null) {
                    return null;
                }
            }

            // Parse the HTTP Date header (RFC 7231 format: "Wed, 21 Jan 2026 12:31:34 GMT")
            $externalTime = \DateTimeImmutable::createFromFormat(
                'D, d M Y H:i:s \G\M\T',
                $dateHeader,
                new \DateTimeZone('UTC'),
            );

            if ($externalTime === false) {
                // Try alternative format without day name
                $externalTime = \DateTimeImmutable::createFromFormat(
                    'd M Y H:i:s \G\M\T',
                    $dateHeader,
                    new \DateTimeZone('UTC'),
                );
            }

            if ($externalTime === false) {
                return null;
            }

            // Use the midpoint of before/after times as our server time reference
            // This accounts for network latency
            $serverTimestamp = (int) (($serverTimeBefore->getTimestamp() + $serverTimeAfter->getTimestamp()) / 2);
            $serverTime = (new \DateTimeImmutable())->setTimestamp($serverTimestamp)
                ->setTimezone(new \DateTimeZone('UTC'));

            return [$serverTime, $externalTime, $name];
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Format a time difference in seconds to a human-readable string.
     *
     * @param int $seconds The time difference in seconds
     *
     * @return string Formatted string like "2 minutes 30 seconds" or "45 seconds"
     */
    private function formatTimeDiff(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = (int) floor($seconds / 3600);
            $minutes = (int) floor(($seconds % 3600) / 60);

            return sprintf(
                '%d hour%s %d minute%s',
                $hours,
                $hours !== 1 ? 's' : '',
                $minutes,
                $minutes !== 1 ? 's' : '',
            );
        }

        if ($seconds >= 60) {
            $minutes = (int) floor($seconds / 60);
            $secs = $seconds % 60;

            return sprintf(
                '%d minute%s %d second%s',
                $minutes,
                $minutes !== 1 ? 's' : '',
                $secs,
                $secs !== 1 ? 's' : '',
            );
        }

        return sprintf('%d second%s', $seconds, $seconds !== 1 ? 's' : '');
    }
}
