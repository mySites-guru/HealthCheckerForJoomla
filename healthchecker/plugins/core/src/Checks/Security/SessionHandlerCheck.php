<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Session Handler Health Check
 *
 * This check verifies the session storage handler configuration. Joomla supports
 * multiple session handlers including database, filesystem, and none.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Session data contains authentication tokens and user state. On shared hosting,
 * filesystem sessions may be accessible to other accounts on the same server,
 * potentially allowing session hijacking. Database sessions provide better isolation
 * and can be more easily monitored and purged.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Session handler is set to "database", which stores sessions in the database
 *       for better security on shared hosting and easier session management.
 *
 * WARNING: Session handler is set to "filesystem". On shared hosting, this may allow
 *          other accounts to read session data. Consider switching to database sessions.
 *
 * CRITICAL: Session handler is set to "none", meaning sessions will not persist.
 *           Users will be unable to stay logged in. This is a broken configuration.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SessionHandlerCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'security.session_handler';
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
     * Perform the session handler configuration check.
     *
     * Evaluates the session storage mechanism for security implications.
     * Joomla supports multiple session handlers:
     * - database: Sessions stored in #__session table (most secure)
     * - filesystem: Sessions stored in server temp directory
     * - none: Sessions not persisted (broken configuration)
     *
     * Security considerations:
     * - Database sessions: Isolated per site, easily managed and purged
     * - Filesystem sessions: On shared hosting, may be readable by other accounts
     *   (potential session hijacking risk if server permissions are misconfigured)
     * - None: Sessions won't persist - users cannot stay logged in
     *
     * @return HealthCheckResult Result indicating session handler security:
     *                           - CRITICAL: Handler set to 'none' (sessions won't persist)
     *                           - WARNING: Handler set to 'filesystem' (security risk on shared hosting)
     *                           - GOOD: Handler set to 'database' or other valid handler
     */
    protected function performCheck(): HealthCheckResult
    {
        // Retrieve session handler configuration
        // Note: Joomla 5 default is 'filesystem' - not stored in DB if using default
        $sessionHandler = Factory::getApplication()->get('session_handler', 'filesystem');

        // Critical: 'none' means sessions are completely disabled
        if ($sessionHandler === 'none') {
            return $this->critical('Session handler is set to none. Sessions will not persist.');
        }

        // Good: Database storage is recommended for security and manageability
        if ($sessionHandler === 'database') {
            return $this->good('Session handler is set to database (recommended).');
        }

        // Warning: Filesystem sessions may have security implications on shared hosting
        // Session files could potentially be accessed by other server accounts
        if ($sessionHandler === 'filesystem') {
            return $this->warning(
                'Session handler is set to filesystem. Database sessions are more secure for shared hosting.',
            );
        }

        // Other valid handler types (e.g., Redis, Memcached) are acceptable
        return $this->good(sprintf('Session handler is set to: %s', $sessionHandler));
    }
}
