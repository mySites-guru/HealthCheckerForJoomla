<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Service;

use Joomla\Filter\InputFilter;

\defined('_JEXEC') || die;

/**
 * Service for sanitizing health check descriptions.
 *
 * This service provides HTML sanitization for health check descriptions,
 * allowing developers to use safe formatting tags while preventing XSS attacks.
 * It uses Joomla's InputFilter class to strip dangerous tags and attributes.
 *
 * Allowed tags:
 * - br, p: Line breaks and paragraphs
 * - strong, b: Bold text
 * - em, i: Italic/emphasis
 * - u: Underline
 * - code, pre: Code formatting
 * - ul, ol, li: Lists
 *
 * NOT allowed (stripped):
 * - a: Links (phishing risk)
 * - script, style, iframe: Script execution
 * - Event handlers (onclick, onerror, etc.)
 * - style attributes
 *
 * @since 3.1.0
 */
final class DescriptionSanitizer
{
    /**
     * Tags allowed in health check descriptions.
     *
     * @var string[]
     */
    private const ALLOWED_TAGS = ['br', 'p', 'strong', 'b', 'em', 'i', 'u', 'code', 'pre', 'ul', 'ol', 'li'];

    /**
     * Sanitize a health check description, allowing only safe HTML tags.
     *
     * @param string $description The raw description from the health check
     *
     * @return string The sanitized description with only allowed HTML tags
     *
     * @since 3.1.0
     */
    public function sanitize(string $description): string
    {
        $inputFilter = new InputFilter(
            self::ALLOWED_TAGS,
            [],  // No attributes allowed
            InputFilter::ONLY_ALLOW_DEFINED_TAGS,
            InputFilter::ONLY_ALLOW_DEFINED_ATTRIBUTES,
            1,  // XSS auto-clean enabled
        );

        return $inputFilter->clean($description, 'html');
    }
}
