<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Utilities;

use Joomla\CMS\Http\Http;
use Joomla\Http\Response;

/**
 * Factory for creating mock HTTP clients for testing
 *
 * This allows tests to simulate various HTTP responses without making
 * actual network requests.
 */
class MockHttpFactory
{
    /**
     * Create a mock HTTP client that returns a specific response for GET requests
     *
     * @param int    $code    HTTP status code
     * @param string $body    Response body
     * @param array<string, string|array<string>>  $headers Response headers
     */
    public static function createWithGetResponse(int $code, string $body = '', array $headers = []): Http
    {
        return new class ($code, $body, $headers) extends Http {
            public function __construct(
                private readonly int $code,
                private readonly string $body,
                private readonly array $responseHeaders,
            ) {}

            public function get(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, $this->body, $this->responseHeaders);
            }

            public function head(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, '', $this->responseHeaders);
            }
        };
    }

    /**
     * Create a mock HTTP client that returns a specific response for HEAD requests
     * Used for checks that only fetch headers (like ServerTimeCheck)
     *
     * @param int   $code    HTTP status code
     * @param array<string, string|array<string>> $headers Response headers
     */
    public static function createWithHeadResponse(int $code, array $headers = []): Http
    {
        return new class ($code, $headers) extends Http {
            public function __construct(
                private readonly int $code,
                private readonly array $responseHeaders,
            ) {}

            public function get(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, '', $this->responseHeaders);
            }

            public function head(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, '', $this->responseHeaders);
            }
        };
    }

    /**
     * Create a mock HTTP client that throws an exception (simulates network failure)
     *
     * @param string $message Exception message
     */
    public static function createThatThrows(string $message = 'Network error'): Http
    {
        return new class ($message) extends Http {
            public function __construct(
                private readonly string $message,
            ) {}

            public function get(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function head(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function post(string $url, mixed $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function put(string $url, mixed $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function delete(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function patch(string $url, mixed $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }
        };
    }

    /**
     * Create a mock HTTP client with JSON API response
     *
     * @param int          $code HTTP status code
     * @param array<mixed> $data Data to encode as JSON
     */
    public static function createWithJsonResponse(int $code, array $data): Http
    {
        return self::createWithGetResponse($code, json_encode($data) ?: '[]', [
            'Content-Type' => 'application/json',
        ]);
    }
}
