<?php

declare(strict_types=1);

/**
 * Datastar Content Security Policy (CSP) mode.
 *
 * Datastar 1.0.3 added opt-in CSP mode: instead of requiring `unsafe-eval`
 * in the site's script-src (the default, because expressions compile through
 * the Function() constructor), Datastar reads a `data-nonce` attribute from
 * the <html> element and stamps that nonce onto the code it compiles.
 * Combined with a `script-src 'nonce-...'` policy, the browser then blocks
 * any script that does not carry the server's per-request nonce, which is
 * the whole point of strict CSP.
 *
 * For that policy to hold on a WordPress site, three things must happen on
 * every frontend request:
 *   1. `data-nonce="..."` goes on the <html> tag (language_attributes
 *      filter).
 *   2. EVERY script tag WP prints must carry the nonce, or the strict header
 *      breaks the site: enqueued scripts via wp_script_attributes, inline
 *      scripts via wp_inline_script_attributes. Same-origin external scripts
 *      are covered by 'self'; script modules (how Datastar itself is
 *      enqueued) bypass wp_script_attributes but are same-origin, so 'self'
 *      covers them. CDN mode serves Datastar from jsdelivr, so the CDN host
 *      is appended to the policy in that case.
 *   3. The policy goes out as a header on send_headers (frontend only --
 *      the WP admin is out of scope; too much core inline JS).
 *
 * Opt-in through the `datastar_csp` option (admin toggle under the Datastar
 * tab) or the `hyperpress/datastar/csp_enabled` filter for library consumers.
 * Host plugins that manage their own nonce (e.g. across a full-page cache)
 * can supply one via `hyperpress/datastar/csp_nonce`.
 *
 * @since 1.6.0
 */

namespace HyperPress;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

final class DatastarCsp
{
    /**
     * Filter: force CSP mode on/off regardless of the stored option.
     * Receives the resolved boolean; return the desired boolean.
     */
    public const ENABLED_FILTER = 'hyperpress/datastar/csp_enabled';

    /**
     * Filter: the per-request nonce. Receives the freshly generated hex
     * string. Use it to feed a cache-stable nonce when a full-page cache
     * would otherwise pin one page's nonce.
     */
    public const NONCE_FILTER = 'hyperpress/datastar/csp_nonce';

    /**
     * Filter: the full policy string sent as the Content-Security-Policy
     * header. Receives the built policy and the nonce; return a string to
     * replace it (e.g. to allowlist analytics hosts).
     */
    public const HEADER_FILTER = 'hyperpress/datastar/csp_header';

    private static ?string $nonce = null;

    /**
     * Whether CSP mode is active for this request: Datastar must be the
     * selected runtime, the option (or filter) must enable it.
     */
    public static function isEnabled(): bool
    {
        $options = hp_get_options();

        $enabled = ($options['active_library'] ?? 'datastar') === 'datastar'
            && !empty($options['datastar_csp']);

        /**
         * Force or veto CSP mode programmatically.
         *
         * @param bool $enabled Resolved from the datastar_csp option.
         */
        return (bool) apply_filters(self::ENABLED_FILTER, $enabled);
    }

    /**
     * The request's CSP nonce. Generated once (32 hex chars from 16 random
     * bytes) and reused across the html attribute, script tags and header so
     * all three match. The NONCE_FILTER can replace it, but only values that
     * pass self::isValidNonce() are honored: the nonce is spliced into the
     * policy string sent via header() and into Datastar's data-nonce
     * attribute, where an empty or punctuation-bearing value would weaken
     * the policy or make the Datastar bundle throw at parse time. A rejected
     * filter value silently falls back to the generated nonce.
     */
    public static function nonce(): string
    {
        if (self::$nonce === null) {
            $nonce = bin2hex(random_bytes(16));
            $filtered = (string) apply_filters(self::NONCE_FILTER, $nonce);
            self::$nonce = self::isValidNonce($filtered) ? $filtered : $nonce;
        }

        return self::$nonce;
    }

    /**
     * Whether a candidate nonce is safe for every sink it reaches: non-empty
     * (Datastar 1.0.3 throws on an empty data-nonce), bounded length, and
     * limited to nonce-token characters, so it cannot break out of the
     * `'nonce-...'` quoting in the CSP header or carry CRLF.
     */
    public static function isValidNonce(string $nonce): bool
    {
        return $nonce !== ''
            && strlen($nonce) <= 128
            && preg_match('/^[A-Za-z0-9+\/_=-]+$/', $nonce) === 1;
    }

    /**
     * The `data-nonce` attribute fragment for the <html> tag. Leading space
     * included so callers can append it to an existing attribute string.
     */
    public static function htmlAttribute(string $nonce): string
    {
        return sprintf(' data-nonce="%s"', esc_attr($nonce));
    }

    /**
     * Script attributes array with the nonce added. Used for both the
     * wp_script_attributes and wp_inline_script_attributes filters.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function withNonce(array $attributes, ?string $nonce = null): array
    {
        $attributes['nonce'] = $nonce ?? self::nonce();

        return $attributes;
    }

    /**
     * The Content-Security-Policy header value.
     *
     * `$allow_jsdelivr` appends the jsdelivr origin for sites that load
     * Datastar (or HTMX) from the CDN instead of the vendored local copies --
     * a cross-origin external script is not covered by 'self'.
     */
    public static function headerValue(string $nonce, bool $allow_jsdelivr = false): string
    {
        $policy = sprintf("script-src 'self' 'nonce-%s'", $nonce);

        if ($allow_jsdelivr) {
            $policy .= ' https://cdn.jsdelivr.net';
        }

        /**
         * Replace or extend the policy string.
         *
         * @param string $policy Built policy (script-src only).
         * @param string $nonce  The request nonce.
         */
        return (string) apply_filters(self::HEADER_FILTER, $policy, $nonce);
    }

    /**
     * Register the hooks. No-op outside the frontend: the WP admin ships too
     * much non-nonceable core inline JavaScript to lock down, and AJAX/cron
     * responses carry no HTML. wp-admin, AJAX and cron constants are all set
     * before after_setup_theme, so these guards are reliable at boot timing.
     * Standalone entry points that never call send_headers (wp-login.php,
     * wp-signup.php, wp-activate.php) get the nonce plumbing but no policy
     * header -- a documented gap, not an enforced page.
     */
    public function boot(): void
    {
        if (!self::isEnabled()) {
            return;
        }

        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        add_filter('language_attributes', [self::class, 'addHtmlAttribute']);
        add_filter('wp_script_attributes', [self::class, 'addScriptNonce']);
        add_filter('wp_inline_script_attributes', [self::class, 'addScriptNonce']);
        add_action('send_headers', [self::class, 'sendHeader']);
    }

    public static function addHtmlAttribute(string $output): string
    {
        return $output . self::htmlAttribute(self::nonce());
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function addScriptNonce(array $attributes): array
    {
        return self::withNonce($attributes);
    }

    public static function sendHeader(): void
    {
        if (headers_sent()) {
            return;
        }

        $allow_jsdelivr = !empty(hp_get_option('load_from_cdn'));

        header('Content-Security-Policy: ' . self::headerValue(self::nonce(), $allow_jsdelivr));
    }
}
