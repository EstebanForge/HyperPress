<?php

/**
 * Handles the API endpoint HXWP_ENDPOINT
 *
 * @package HXWP
 * @since   2023-11-22
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Routes Class
 */
class Router
{
    /**
     * Register route
     * Outside wp-json, use WP rewrite API instead
     *
     * @since 2023-11-22
     * @return void
     */
    public function register_main_route()
    {
        // Catch URL starting with HXWP_ENDPOINT
        add_rewrite_endpoint(HXWP_ENDPOINT . '/' . HXWP_ENDPOINT_VERSION . '', EP_ROOT, HXWP_ENDPOINT);
    }

    /**
     * Register query var
     *
     * @since 2023-11-22
     * @param array $vars
     *
     * @return array
     */
    public function register_query_vars($vars)
    {
        $vars[] = HXWP_ENDPOINT;

        return $vars;
    }
}
