<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Event;

use Joomla\Event\Event;

\defined('_JEXEC') || die;

/**
 * Event triggered before the health check report is displayed
 *
 * This event allows plugins to inject content (like banners) into the report view
 * before it's rendered to the user. Plugins can add HTML content that will be
 * displayed at the top of the report.
 *
 * @since 1.0.0
 */
final class BeforeReportDisplayEvent extends Event
{
    /**
     * HTML content to inject before the report
     *
     * @var string[]
     * @since 1.0.0
     */
    private array $htmlContent = [];

    /**
     * Constructs the BeforeReportDisplayEvent.
     *
     * Initializes the event with the name from the HealthCheckerEvents enum
     * to ensure consistency across the codebase.
     */
    public function __construct()
    {
        parent::__construct(HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value);
    }

    /**
     * Add HTML content to be displayed before the report
     *
     * @param   string  $html  HTML content to inject
     *
     * @since   1.0.0
     */
    public function addHtmlContent(string $html): void
    {
        $this->htmlContent[] = $html;
    }

    /**
     * Get all HTML content to display
     *
     * @return  string  Combined HTML content from all plugins
     *
     * @since   1.0.0
     */
    public function getHtmlContent(): string
    {
        return implode("\n", $this->htmlContent);
    }
}
