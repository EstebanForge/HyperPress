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

        // A ruleset flushed outside a web request (WP-CLI "wp rewrite flush",
        // "wp rewrite structure", CLI-driven activation) is generated while
        // this router runs on every request (Bootstrap boots Main in REST/CLI
        // too, for the Abilities layer), so the ruleset can lack our endpoint
        // rules and /wp-html/v1/ degrades to the info page. Rebuild the
        // ruleset once per request window, but never from inbound REST, cron,
        // or CLI calls: a rewrite flush must be a page-load side effect, not
        // something a webhook or a cron task triggers. After the heal every
        // later request takes the early return.
        static $self_heal_checked = false;
        if ($self_heal_checked) {
            return;
        }
        $self_heal_checked = true;

        // Self-heal is a page-load side effect only (see comment above).
        if (wp_doing_cron()
            || (defined('REST_REQUEST') && REST_REQUEST === true)
            || (defined('WP_CLI') && WP_CLI === true)
            || (defined('DOING_AJAX') && DOING_AJAX === true)
        ) {
            return;
        }

        $rules = get_option('rewrite_rules');
        if (!is_array($rules)) {
            return;
        }
        foreach ($rules as $match => $query) {
            if (strpos((string) $match, Config::ENDPOINT) !== false) {
                return;
            }
        }

        // Backoff: if something keeps stripping the rule (option filter,
        // read replica, broken object cache), re-flushing on every request
        // would hammer the DB. Retry at most once per TTL window.
        if (get_transient('hyperpress_router_heal_lock')) {
            return;
        }
        set_transient('hyperpress_router_heal_lock', 1, 5 * MINUTE_IN_SECONDS);

        flush_rewrite_rules(false);
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
