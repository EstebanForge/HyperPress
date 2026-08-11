<?php

declare(strict_types=1);

/**
 * Core bootstrap for HyperFields.
 *
 * Dev-environment auto-init bridge: when HyperFields is loaded directly via
 * Composer (unprefixed), this file schedules initialization at
 * after_setup_theme by delegating to LibraryBootstrap::init(), which lives
 * under the PSR-4 root and is the prefixable entry point.
 *
 * Under namespace prefixing (Mozart) this file is not copied (it sits outside
 * src/); prefixed consumers call HyperFields\LibraryBootstrap::init()
 * explicitly, which is already prefixed.
 *
 * @since 1.0.0
 */

// CONTRACT FREEZE: the callback name (hyperfields_bootstrap_init) and the
// init() target (\HyperFields\LibraryBootstrap::init) are part of the
// cross-version contract. A stale copy of this bootstrap can win Composer's
// autoload.files race (the first vendor/autoload.php required) and call into
// whatever class copy wins the SPL election; renaming the method in a future
// major makes every request fatal with an undefined-method error at
// after_setup_theme. Keep these names stable across all majors.

// 1. Scheduler. Runs unconditionally and needs nothing from WordPress, so it
//    sits ABOVE the ABSPATH guard. This covers both bootstrap failure windows:
//    the WP-CLI window (ABSPATH pre-defined, but plugin.php not yet loaded so
//    add_action is absent) and the HTTP window (ABSPATH undefined, this file
//    returns at the guard below, but the registration above already ran).
//    When add_action is absent we write the registration straight into
//    $GLOBALS['wp_filter'] in the preinitialized-hooks format; WordPress core
//    converts that raw array into a real WP_Hook when plugin.php loads
//    (WP_Hook::build_preinitialized_hooks, since WP 4.7, Trac #38929).
if (!function_exists('hyperfields_bootstrap_init')) {
    /**
     * Initialize HyperFields for this copy at after_setup_theme.
     *
     * @return void
     */
    function hyperfields_bootstrap_init(): void
    {
        \HyperFields\LibraryBootstrap::init();
    }
}

if (function_exists('add_action')) {
    // === false, not !has_action: has_action returns the priority int (0 for
    // this priority-0 callback), which is falsy, so a loose !has_action would
    // always re-add.
    if (has_action('after_setup_theme', 'hyperfields_bootstrap_init') === false) {
        add_action('after_setup_theme', 'hyperfields_bootstrap_init', 0);
    }
} else {
    // Pre-plugin-API window. Write the callback into $GLOBALS['wp_filter'] in
    // the raw array format WP_Hook::build_preinitialized_hooks expects.
    // is_array is mandatory: WP_Hook implements ArrayAccess, so a ??= on an
    // already-built WP_Hook would silently discard. accepted_args is read
    // unconditionally by add_filter, so it must be present (PHP 8 warning).
    $hooks = $GLOBALS['wp_filter']['after_setup_theme'] ?? [];
    if (is_array($hooks)) {
        $hooks[0]['hyperfields_bootstrap_init'] ??= [
            'function'      => 'hyperfields_bootstrap_init',
            'accepted_args' => 1,
        ];
        $GLOBALS['wp_filter']['after_setup_theme'] = $hooks;
    } else {
        // Unreachable in standard WP startup (WP_Hook cannot exist before
        // plugin.php loads), but log so a future regression cannot fail silent.
        error_log('HyperFields: after_setup_theme wp_filter slot is not an array; preinit registration skipped.');
    }
}

// 2. Everything below needs WordPress.
// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERFIELDS_TESTING_MODE')) {
    return;
}

// Composer autoloader. Skip the nested vendor/autoload.php when this file is
// itself inside another package's /vendor/ tree (would double-declare Composer
// autoloader classes). bootstrap.php runs once per process (Composer files
// autoload dedup + require_once), so no global reload guard is needed.
$normalizedDir = str_replace('\\', '/', __DIR__);
$loadedFromVendorTree = str_contains($normalizedDir, '/vendor/');
if (!$loadedFromVendorTree && file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (!$loadedFromVendorTree) {
    // No autoloader found: surface an admin notice (or error_log before
    // plugin.php has loaded) but continue so tests can register hooks.
    if (function_exists('add_action')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="error"><p>' . esc_html__('HyperFields: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'hyperfields') . '</p></div>';
        });
    } else {
        error_log('HyperFields: Composer autoloader not found.');
    }
}
