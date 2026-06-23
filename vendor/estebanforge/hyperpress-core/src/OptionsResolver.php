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
            'load_from_cdn' => 0,
            'load_hyperscript' => 0,
            'load_alpinejs_with_htmx' => 0,
            'set_htmx_hxboost' => 0,
            'load_htmx_backend' => 0,
            'enable_alpinejs_core' => 0,
            'enable_alpine_ajax' => 0,
            'load_alpinejs_backend' => 0,
            'load_datastar_backend' => 0,
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

        $merged = wp_parse_args($stored, $defaults);

        self::$cache[$cache_key] = apply_filters(self::FILTER, $merged);

        return self::$cache[$cache_key];
    }
}
