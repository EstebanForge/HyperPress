<?php

declare(strict_types=1);

/**
 * Options Resolver.
 *
 * Single source of truth for HyperPress option resolution. Consolidates the
 * defaults previously scattered across Main::getOptions(), Config::getOptions(),
 * and Assets::getOptions(), and exposes a canonical `hyperpress/options` filter
 * that always wins over stored database options.
 *
 * Resolution order (each step overrides the previous):
 *   1. Hard-coded defaults
 *   2. Deprecated `hyperpress/config/default_options` filter (applied to defaults)
 *   3. Deprecated `hyperpress/assets/default_options` filter (applied to defaults)
 *   4. Stored `hyperpress_options` option from the database
 *   5. Canonical `hyperpress/options` filter
 *
 * @since 1.2.0
 */

namespace HyperPress;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

/**
 * Resolves the merged HyperPress option array.
 */
class OptionsResolver
{
    /**
     * Canonical filter. Applied last so library consumers always win.
     */
    public const FILTER = 'hyperpress/options';

    /**
     * Action fired once per request after options are resolved, from
     * Main::run(). Receives the merged options array.
     */
    public const ACTION = 'hyperpress/configured';

    /**
     * Per-request cache keyed by the `$htmx_extensions` argument. Different
     * callers pass different extension maps (Main::getOptions vs the public
     * hp_get_options helper), so each unique input gets its own cached copy.
     *
     * @var array<string, array>
     */
    private static array $cache = [];

    /**
     * Build the default option set, optionally including HTMX extension keys.
     *
     * @since 1.2.0
     *
     * @param array $htmx_extensions Map of extension_key => details. Used to
     *                               synthesize `load_extension_*` default keys.
     * @return array
     */
    public static function defaults(array $htmx_extensions = []): array
    {
        $defaults = [
            'active_library' => 'datastar',
            // htmx 4.x is the default for NEW installs. Sites with a stored
            // options row that predates this option keep htmx 2.x via
            // applyHtmxVersionRule().
            'htmx_version' => '4',
            'load_from_cdn' => 0,
            // hx-live ships with htmx 4 and replaces the Alpine/hyperscript
            // pairing: on by default when htmx 4 is the selected runtime.
            // Inert under htmx 2 (Assets.php gates on htmx_version).
            'load_hxlive' => 1,
            'load_hyperscript' => 0,
            'load_alpinejs_with_htmx' => 0,
            'set_htmx_hxboost' => 0,
            'load_htmx_backend' => 0,
            'enable_alpinejs_core' => 0,
            'enable_alpine_ajax' => 0,
            'load_alpinejs_backend' => 0,
            'load_datastar_backend' => 0,
            // Datastar CSP mode (1.0.3+). Opt-in: a strict script-src nonce
            // policy blocks any script printed outside wp_enqueue_script.
            'datastar_csp' => 0,
            'hyperpress_meta_config_content' => '',
        ];

        foreach (array_keys($htmx_extensions) as $extension_key) {
            // Match the key shape used by Admin/Options.php and the stored
            // option: underscores, not hyphens. The CDN map uses hyphens
            // (e.g. `head-support`), but the admin form field and DB row
            // both store `load_extension_head_support`.
            $defaults['load_extension_' . str_replace('-', '_', $extension_key)] = 0;
        }

        return $defaults;
    }

    /**
     * Apply the htmx version legacy rule to a merged options array.
     *
     * htmx 4.x is the default for new installs. A stored options row written
     * before the htmx_version option existed (non-empty row, key absent)
     * belongs to a site already running the 2.x line and must not be silently
     * upgraded. An empty row means a fresh install and keeps the '4' default.
     *
     * Also normalizes the stored value: non-canonical writes (WP-CLI, imports
     * and direct DB edits can store int 4) are cast to string, and anything
     * unexpected falls back to '2' — a corrupt value must never upgrade the
     * site to the 4.x line.
     *
     * Pure function, separated from resolve() so the test bootstrap's
     * get_option passthrough cannot block coverage of the legacy branch.
     *
     * @param array $stored Raw stored hyperpress_options row. Key presence
     *                      decides; the key's value is irrelevant here.
     * @param array $merged Merged options (defaults + stored).
     * @return array Merged options with htmx_version normalized.
     */
    public static function applyHtmxVersionRule(array $stored, array $merged): array
    {
        $version = isset($merged['htmx_version']) ? (string) $merged['htmx_version'] : '4';
        $version = in_array($version, ['2', '4'], true) ? $version : '2';

        // A key present but explicitly null (importer bug, direct DB edit,
        // filter returning null) is as unknown as an absent key: the legacy
        // rule applies to it too.
        $has_explicit_version = array_key_exists('htmx_version', $stored) && $stored['htmx_version'] !== null;

        if (!empty($stored) && !$has_explicit_version) {
            $version = '2';
        }

        $merged['htmx_version'] = $version;

        return $merged;
    }

    /**
     * Resolve the merged options array. Cached per `(blog_id, $htmx_extensions)`
     * shape for the lifetime of the request, so Main, Config, Assets, and any
     * external helper all observe the same array. The blog id is included
     * because the static cache persists across `switch_to_blog()` boundaries
     * on multisite; without it, the first site's options would leak into
     * subsequent sites on the same request.
     *
     * @since 1.2.0
     *
     * @param array $htmx_extensions Optional extension list for default synthesis.
     * @return array
     */
    public static function resolve(array $htmx_extensions = []): array
    {
        $blog_id = function_exists('get_current_blog_id') ? get_current_blog_id() : 0;
        $ext_key = empty($htmx_extensions) ? '__empty__' : md5(serialize($htmx_extensions));
        $cache_key = $blog_id . ':' . $ext_key;

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $defaults = self::defaults($htmx_extensions);

        // Deprecated filters — kept alive for BC. Apply to defaults only,
        // preserving their original semantics. New code should use
        // OptionsResolver::FILTER ('hyperpress/options') instead, which
        // is applied LAST and always wins over the database.
        $defaults = apply_filters_deprecated(
            'hyperpress/config/default_options',
            [$defaults],
            '1.2.0',
            self::FILTER,
            'Use the hyperpress/options filter instead.'
        );

        $defaults = apply_filters_deprecated(
            'hyperpress/assets/default_options',
            [$defaults],
            '1.2.0',
            self::FILTER,
            'Use the hyperpress/options filter instead.'
        );

        $stored = get_option('hyperpress_options', []);
        if (!is_array($stored)) {
            $stored = [];
        }

        $merged = self::applyHtmxVersionRule($stored, wp_parse_args($stored, $defaults));

        self::$cache[$cache_key] = apply_filters(self::FILTER, $merged);

        return self::$cache[$cache_key];
    }
}
