<?php

/**
 * Handles the API endpoints for HyperPress for WordPress.
 * Registers both the primary (HMAPI_ENDPOINT) and legacy (HMAPI_LEGACY_ENDPOINT) routes.
 *
 * @since   2023-11-22
 */

namespace HMApi;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
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
    public function register_main_route(): void
    {
        // Register the new primary endpoint (e.g., /wp-html/v1/)
        if (defined('HMAPI_ENDPOINT') && defined('HMAPI_ENDPOINT_VERSION')) {
            add_rewrite_endpoint(HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION, EP_ROOT, HMAPI_ENDPOINT);
        }

        // Register the legacy endpoint for backward compatibility (e.g., /wp-htmx/v1/)
        if (defined('HMAPI_LEGACY_ENDPOINT') && defined('HMAPI_ENDPOINT_VERSION')) {
            add_rewrite_endpoint(HMAPI_LEGACY_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION, EP_ROOT, HMAPI_LEGACY_ENDPOINT);
        }
    }

    /**
     * Register query variables for the API endpoints.
     *
     * @since 2023-11-22
     * @param array $vars WordPress query variables.
     *
     * @return array Modified query variables.
     */
    public function register_query_vars(array $vars): array
    {
        if (defined('HMAPI_ENDPOINT')) {
            $vars[] = HMAPI_ENDPOINT;
        }
        if (defined('HMAPI_LEGACY_ENDPOINT')) {
            $vars[] = HMAPI_LEGACY_ENDPOINT;
        }

        return $vars;
    }
}
