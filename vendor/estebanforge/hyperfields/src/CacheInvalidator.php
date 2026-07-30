<?php

declare(strict_types=1);

/**
 * Automatic cache invalidation for HyperFields-managed data.
 *
 * When HyperFields persists a value in the WordPress admin (options page,
 * wp-settings compatibility page, post/term/user meta container, or an
 * option import), the cached representations of that data can go stale.
 * This class re-establishes cache coherence by clearing two layers that
 * HyperFields cannot invalidate on its own:
 *
 *   1. Transients  — snapshots that may hold the old value.
 *   2. OPcache     — compiled bytecode. PHP file changes invalidate it, not
 *      option changes, but hosts that cache generated config PHP or compiled
 *      templates keyed off options benefit from a reset on save.
 *
 * It hooks HyperFields' semantic save actions, which themselves fire only
 * when a value actually changed (no-op writes are filtered upstream), so
 * invalidation runs once per real change rather than on every form submit.
 *
 * Transient clearing is BACKEND-AWARE (this is the part that matters on
 * Redis/Memcached):
 *   - No external object cache: transients live in `wp_options` as
 *     `_transient_*` rows. A direct SQL DELETE clears them.
 *   - External object cache (Redis/Memcached): transients live ONLY in the
 *     object cache under the `transient` / `site-transient` groups (WP core
 *     `set_transient()` calls `wp_cache_set($name, $value, 'transient', ...)`
 *     when `wp_using_ext_object_cache()` is true). No `_transient_*` rows
 *     exist, so a DB DELETE is a no-op. We clear them surgically with
 *     `wp_cache_flush_group('transient'|'site-transient')` (available since
 *     WP 6.1; the WP 6.5+ floor guarantees it).
 *
 * A full `wp_cache_flush()` is deliberately a separate, default-OFF opt-in:
 * it is the documented performance anti-pattern (Trac #63070 wontfix; the
 * redis-cache FAQ warns frequent flushes cause timeouts). Use it only on a
 * persistent backend that does NOT support group flushing (rare).
 *
 * Requires WordPress 6.5+ (no `function_exists` guards for cache functions).
 *
 * Disable everything:
 *   add_filter('hyperfields/cache/auto_invalidate', '__return_false');
 *
 * Disable a single layer:
 *   add_filter('hyperfields/cache/flush_transients', '__return_false');
 *   add_filter('hyperfields/cache/flush_object_cache', '__return_false');
 *   add_filter('hyperfields/cache/reset_opcache', '__return_false');
 *
 * Manual flush from theme/plugin code:
 *   HyperFields\CacheInvalidator::flush();
 *   // or
 *   hf_flush_hyperfields_cache();
 *
 * @since 1.5.0
 */

namespace HyperFields;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    return;
}

final class CacheInvalidator
{
    /**
     * Object-cache groups WP core uses to store transients when an external
     * object cache is present (see `set_transient()` / `set_site_transient()`).
     *
     * @var array<int, string>
     */
    private const TRANSIENT_GROUPS = ['transient', 'site-transient'];

    /**
     * HyperFields semantic save actions that mutate persisted data.
     *
     * Each fires only on a real change (no-op gated upstream), so hooking
     * here means "flush once per actual write," not "flush on every submit."
     *
     * @var array<int, string>
     */
    private const SAVE_ACTIONS = [
        'hyperfields/options_page/after_save',
        'hyperfields/settings/after_save',
        'hyperfields/post_meta_container_saved',
        'hyperfields/term_meta_container_saved',
        'hyperfields/user_meta_container_saved',
        'hyperfields/import/after',
    ];

    /**
     * Register the flush against every HyperFields save action.
     *
     * Called from LibraryBootstrap::init(). Safe to call once; the actions
     * are idempotent because flush() re-checks the master filter each time.
     *
     * @return void
     */
    public static function init(): void
    {
        foreach (self::SAVE_ACTIONS as $action) {
            add_action($action, [self::class, 'onSave'], 10, 0);
        }
    }

    /**
     * Save-action callback. Honors the master filter before doing anything.
     *
     * @return void
     */
    public static function onSave(): void
    {
        if (!self::isEnabled()) {
            return;
        }

        self::flush();
    }

    /**
     * Run the cache flush unconditionally.
     *
     * Public entry point for manual/programmatic flushing (the helper
     * `hf_flush_hyperfields_cache()` delegates here). Each layer is gated
     * by its own filter so callers keep selective control even when invoking
     * directly.
     *
     * @return void
     */
    public static function flush(): void
    {
        if ((bool) apply_filters('hyperfields/cache/flush_transients', true)) {
            self::clearTransients();
        }

        // Full object-cache flush is the documented anti-pattern (Trac
        // #63070). Off by default; opt in only when a persistent backend
        // cannot flush transient groups and you accept a full cache rebuild.
        if (
            (bool) apply_filters('hyperfields/cache/flush_object_cache', false)
            && wp_using_ext_object_cache()
        ) {
            wp_cache_flush();
        }

        if ((bool) apply_filters('hyperfields/cache/reset_opcache', true)) {
            self::resetOpCache();
        }
    }

    /**
     * Whether automatic invalidation is enabled at all.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return (bool) apply_filters('hyperfields/cache/auto_invalidate', true);
    }

    /**
     * Clear transients, picking the correct mechanism for the active backend.
     *
     * - External object cache: transients live in the object cache (no DB
     *   rows). Flush the `transient` + `site-transient` groups surgically via
     *   `wp_cache_flush_group()`, which the WP 6.5+ floor guarantees and the
     *   backend advertises through `wp_cache_supports('flush_group')`.
     * - No external object cache: transients live in `wp_options`. Delete the
     *   `_transient_*` / `_site_transient_*` rows directly.
     *
     * On backends that advertise no group-flush support (some Memcached
     * drop-ins) this method does nothing by default; wire up the explicit
     * full-flush opt-in (`hyperfields/cache/flush_object_cache`) if you need
     * transients cleared there.
     *
     * @return void
     */
    private static function clearTransients(): void
    {
        if (wp_using_ext_object_cache()) {
            if (wp_cache_supports('flush_group')) {
                foreach (self::TRANSIENT_GROUPS as $group) {
                    wp_cache_flush_group($group);
                }
            }

            return;
        }

        self::purgeDatabaseTransients();
    }

    /**
     * Delete every transient row from the database.
     *
     * Only reached when no external object cache is in use, so the rows
     * genuinely exist. Transients are by definition regenerable caches, so a
     * full purge (not just expired ones) is safe: anything deleted is
     * recomputed on next read. `_transient_*` matches both data and timeout
     * rows (the timeout name `_transient_timeout_*` is itself prefixed by
     * `_transient_`), so a single LIKE per transient family removes the pair.
     * Site transients on the main network of a Multisite install live in the
     * sitemeta table and are purged there to match core's handling.
     *
     * @return void
     */
    private static function purgeDatabaseTransients(): void
    {
        global $wpdb;

        if (!($wpdb instanceof \wpdb)) {
            return;
        }

        // Per-site transients + site transients always live in the options
        // table of the current blog.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                    OR option_name LIKE %s",
                $wpdb->esc_like('_transient_') . '%',
                $wpdb->esc_like('_site_transient_') . '%'
            )
        );

        // On Multisite, network-wide site transients are stored in sitemeta
        // on the main network. Clean them so a network admin save clears the
        // full transient footprint, mirroring core's handling.
        if (is_multisite() && is_main_network() && is_main_site()) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->sitemeta}
                     WHERE meta_key LIKE %s",
                    $wpdb->esc_like('_site_transient_') . '%'
                )
            );
        }
    }

    /**
     * Reset the OPcache bytecode store.
     *
     * `opcache_reset()` clears the whole compiled-file cache for the current
     * SAPI (PHP-FPM worker pool in the common case). It is a no-op when the
     * OPcache extension is absent. The call is silenced because hosts that
     * set `opcache.restrict_api` or disable reset emit a PHP warning before
     * returning false; the failure is benign (no stale data is served, the
     * cache simply is not reset) and not worth surfacing as a user error.
     *
     * Note: OPcache stores compiled PHP bytecode, not option values. A value
     * change alone never makes bytecode stale; this reset exists for sites
     * that derive PHP/config files from options, or where a host caches
     * compiled templates keyed off option state.
     *
     * @return void
     */
    private static function resetOpCache(): void
    {
        if (!function_exists('opcache_reset')) {
            return;
        }

        @opcache_reset(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    }
}
