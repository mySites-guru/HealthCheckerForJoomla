<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Category;

use Joomla\CMS\Language\Text;

\defined('_JEXEC') || die;

/**
 * Immutable value object representing a health check category.
 *
 * Categories organize health checks in the UI. The Health Checker includes
 * 8 standard categories (system, database, security, users, extensions,
 * performance, seo, content), but third-party plugins can register custom
 * categories via the CollectCategoriesEvent.
 *
 * All properties are readonly to ensure immutability after creation.
 *
 * @since 1.0.0
 */
final class HealthCategory
{
    /**
     * Create a new health category.
     *
     * The label can be either a plain string or a language key constant.
     * If it's a language key, it will be automatically translated when
     * toArray() is called.
     *
     * @param string      $slug      Unique identifier for the category (e.g., "system", "security")
     * @param string      $label     Display label (can be language key or plain text)
     * @param string      $icon      FontAwesome icon class (e.g., "fa-server", "fa-shield-halved")
     * @param int         $sortOrder Display order in UI (lower numbers appear first, default: 50)
     * @param string|null $logoUrl   Optional URL to category logo image
     *
     * @since 1.0.0
     */
    public function __construct(
        /**
         * Unique slug identifier for this category.
         *
         * Used in URLs, filtering, and check association. Should be lowercase
         * with underscores for spaces (e.g., "system", "security", "my_custom_category").
         *
         * @var string The category slug
         * @since 1.0.0
         */
        public readonly string $slug,

        /**
         * Display label for the category.
         *
         * Can be either a plain string or a language key constant
         * (e.g., "COM_HEALTHCHECKER_CATEGORY_SYSTEM"). If it's a language key,
         * it will be translated automatically via toArray().
         *
         * @var string The category label or language key
         * @since 1.0.0
         */
        public readonly string $label,

        /**
         * FontAwesome 6 icon class for visual representation.
         *
         * Should be a valid FontAwesome class name without the "fa-" prefix being
         * required but typically included (e.g., "fa-server", "fa-shield-halved").
         * FontAwesome 6 is bundled with Joomla 5.
         *
         * @var string FontAwesome icon class
         * @since 1.0.0
         */
        public readonly string $icon,

        /**
         * Sort order for display in the UI.
         *
         * Lower numbers appear first. Standard categories use increments of 10
         * (10=system, 20=database, 30=security, etc.) to allow third-party
         * categories to insert between them. Default is 50.
         *
         * @var int Sort order value (default: 50)
         * @since 1.0.0
         */
        public readonly int $sortOrder = 50,

        /**
         * Optional URL to a logo image for the category.
         *
         * If provided, this logo can be displayed instead of or alongside the
         * FontAwesome icon in the UI. Useful for third-party plugin branding.
         *
         * @var string|null URL to logo image or null
         * @since 1.0.0
         */
        public readonly ?string $logoUrl = null,
    ) {}

    /**
     * Convert the category to an associative array with translated label.
     *
     * This method is used for JSON serialization and passing data to the UI.
     * The label is automatically translated if it's a language key constant.
     *
     * @return array{slug: string, label: string, icon: string, sortOrder: int, logoUrl: string|null}
     *               Array representation of the category
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        // Translate the label - if it's a language key, translate it
        $translatedLabel = Text::_($this->label);

        return [
            'slug' => $this->slug,
            'label' => $translatedLabel,
            'icon' => $this->icon,
            'sortOrder' => $this->sortOrder,
            'logoUrl' => $this->logoUrl,
        ];
    }
}
