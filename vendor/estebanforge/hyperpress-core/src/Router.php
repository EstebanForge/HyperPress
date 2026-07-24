<?php

/**
 * Handles the API endpoints for HyperPress for WordPress.
 * Registers both the primary (HYPERPRESS_ENDPOINT) and legacy (HYPERPRESS_LEGACY_ENDPOINT) routes.
 *
 * @since   2023-11-22
 */

namespace HyperPress;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

/**
 * Routes Class.
 */
class Router
{
    /**
     * Register main API routes.
     * Registers both the new primary endpoint and the legacy endpoint for backward compatibility.
     * Outside wp-json, uses the WP rewrite API.
     *
     * @since 2023-11-22
     * @return void
     */
    public function registerMainRoute(): void
    {
        // Register the new primary endpoint (e.g., /wp-html/v1/)
        add_rewrite_endpoint(Config::ENDPOINT . '/' . Config::ENDPOINT_VERSION, EP_ROOT, Config::ENDPOINT);

        // Register the legacy endpoint for backward compatibility (e.g., /wp-htmx/v1/)
        add_rewrite_endpoint(Config::LEGACY_ENDPOINT . '/' . Config::ENDPOINT_VERSION, EP_ROOT, Config::LEGACY_ENDPOINT);
    }

    /**
     * Register query variables for the API endpoints.
     *
     * @since 2023-11-22
     * @param array $vars WordPress query variables.
     *
     * @return array Modified query variables.
     */
    public function registerQueryVars(array $vars): array
    {
        $vars[] = Config::ENDPOINT;
        $vars[] = Config::LEGACY_ENDPOINT;

        return $vars;
    }
}
