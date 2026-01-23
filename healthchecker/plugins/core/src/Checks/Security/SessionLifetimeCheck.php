<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Session Lifetime Health Check
 *
 * This check verifies that the session timeout is configured appropriately. Session
 * lifetime controls how long a user remains logged in after their last activity.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Long session lifetimes increase the risk of session hijacking if a user forgets to
 * log out on a shared computer. Very short lifetimes frustrate users with frequent
 * re-authentication. A balance between security and usability is recommended
 * (typically 15-60 minutes).
 *
 * RESULT MEANINGS:
 *
 * GOOD: Session lifetime is between 15 and 60 minutes, providing a good balance
 *       between security and user convenience.
 *
 * WARNING: Session lifetime is either very short (under 15 minutes, causing user
 *          frustration) or very long (over 60 minutes, increasing session hijacking
 *          risk). Adjust the session lifetime in Global Configuration.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SessionLifetimeCheck extends AbstractHealthCheck
{
    /**
     * Maximum recommended session lifetime in minutes.
     *
     * Sessions longer than this increase session hijacking risk if users
     * forget to log out on shared/public computers.
     */
    private const MAX_RECOMMENDED_MINUTES = 60;

    /**
     * Minimum recommended session lifetime in minutes.
     *
     * Sessions shorter than this cause user frustration due to frequent
     * re-authentication requirements.
     */
    private const MIN_RECOMMENDED_MINUTES = 15;

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'security.session_lifetime';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the session lifetime configuration check.
     *
     * Evaluates whether the session timeout balances security and usability.
     * The session lifetime controls how long users remain logged in after
     * their last activity before being automatically logged out.
     *
     * Security vs. Usability trade-offs:
     * - Too long (>60 min): Increases session hijacking risk on shared computers
     * - Too short (<15 min): Frustrates users with frequent re-authentication
     * - Recommended: 15-60 minutes for most sites
     *
     * The check compares the configured lifetime against recommended thresholds:
     * - Under 15 minutes: Warning (too short, poor UX)
     * - 15-60 minutes: Good (balanced)
     * - Over 60 minutes: Warning (too long, security risk)
     *
     * @return HealthCheckResult Result indicating session lifetime appropriateness:
     *                           - WARNING: Lifetime too long (>60 min) or too short (<15 min)
     *                           - GOOD: Lifetime within recommended range (15-60 min)
     */
    protected function performCheck(): HealthCheckResult
    {
        // Retrieve session lifetime configuration in minutes (default: 15)
        $sessionLifetime = (int) Factory::getApplication()->get('lifetime', 15);

        // Warning: Sessions longer than 60 minutes pose session hijacking risk
        // If a user forgets to log out on a shared computer, their session
        // remains active and could be exploited by the next user
        if ($sessionLifetime > self::MAX_RECOMMENDED_MINUTES) {
            return $this->warning(
                sprintf(
                    'Session lifetime (%d minutes) is longer than recommended (%d minutes). Consider reducing for security.',
                    $sessionLifetime,
                    self::MAX_RECOMMENDED_MINUTES,
                ),
            );
        }

        // Warning: Sessions shorter than 15 minutes cause poor user experience
        // Users will be logged out frequently, especially during content editing
        if ($sessionLifetime < self::MIN_RECOMMENDED_MINUTES) {
            return $this->warning(
                sprintf(
                    'Session lifetime (%d minutes) is very short. Users may experience frequent logouts.',
                    $sessionLifetime,
                ),
            );
        }

        // Good: Session lifetime is within the recommended security/usability balance
        return $this->good(sprintf('Session lifetime is %d minutes.', $sessionLifetime));
    }
}
