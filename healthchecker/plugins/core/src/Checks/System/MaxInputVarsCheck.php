<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Max Input Variables Health Check
 *
 * This check verifies that PHP can accept enough form variables for complex Joomla forms.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla's admin forms can have hundreds of input fields (menus, permissions, article editors).
 * If max_input_vars is too low:
 * - Form data is silently truncated without warning
 * - Menu configurations get partially saved
 * - Permission settings may be lost
 * - Complex page builder layouts break
 *
 * RESULT MEANINGS:
 *
 * GOOD: max_input_vars is 3000 or higher.
 *       All Joomla forms including complex menu structures and
 *       permission matrices will work correctly.
 *
 * WARNING: max_input_vars is between 1000-3000.
 *          Basic forms work but large menu structures or extensive
 *          permission configurations may lose data.
 *
 * CRITICAL: max_input_vars is below 1000.
 *           Data loss is likely. Forms with many fields will be truncated.
 *           Menu items and permissions may save incorrectly.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MaxInputVarsCheck extends AbstractHealthCheck
{
    /**
     * Minimum acceptable number of input variables.
     */
    private const MINIMUM_VARS = 1000;

    /**
     * Recommended number of input variables.
     */
    private const RECOMMENDED_VARS = 3000;

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.max_input_vars'
     */
    public function getSlug(): string
    {
        return 'system.max_input_vars';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Perform the max input vars check.
     *
     * Verifies that PHP can accept enough form variables for complex Joomla forms.
     * Complex menu structures, permission matrices, and page builders can submit
     * hundreds of input fields. If max_input_vars is too low, data is silently truncated.
     *
     * @return HealthCheckResult Critical if below 1000, Warning if below 3000, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $maxInputVars = (int) ini_get('max_input_vars');

        if ($maxInputVars < self::MINIMUM_VARS) {
            return $this->critical(
                sprintf(
                    'max_input_vars (%d) is below the minimum required %d. Forms with many fields may lose data.',
                    $maxInputVars,
                    self::MINIMUM_VARS,
                ),
            );
        }

        if ($maxInputVars < self::RECOMMENDED_VARS) {
            return $this->warning(
                sprintf(
                    'max_input_vars (%d) is below the recommended %d. Large forms may have issues.',
                    $maxInputVars,
                    self::RECOMMENDED_VARS,
                ),
            );
        }

        return $this->good(sprintf('max_input_vars (%d) meets requirements.', $maxInputVars));
    }
}
