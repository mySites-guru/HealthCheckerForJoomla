<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

// This file provides additional test mock functionality.
// Base interfaces and classes are defined in tests/stubs/ files.
// This file only extends with additional functionality for testing.

namespace Joomla\CMS\Event\Result;

// Only define if not already defined by stubs
if (! trait_exists(ResultAware::class, false)) {
    /**
     * Mock ResultAware trait for testing
     */
    trait ResultAware
    {
        abstract public function typeCheckResult(mixed $data): void;

        public function addResult(mixed $data): void
        {
            $this->typeCheckResult($data);

            if (! isset($this->arguments['result'])) {
                $this->arguments['result'] = [];
            }

            $this->arguments['result'][] = $data;
        }
    }
}

// Only define if not already defined by stubs
if (! interface_exists(ResultAwareInterface::class, false)) {
    /**
     * Mock ResultAwareInterface for testing
     */
    interface ResultAwareInterface
    {
        public function addResult(mixed $data): void;

        public function typeCheckResult(mixed $data): void;
    }
}
