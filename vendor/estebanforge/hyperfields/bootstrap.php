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
    // No autoloader found: surface an admin notice but continue so tests can
    // register hooks.
    add_action('admin_notices', function () {
        echo '<div class="error"><p>' . esc_html__('HyperFields: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'hyperfields') . '</p></div>';
    });
}

// Schedule initialization at after_setup_theme (priority 0, original timing).
// Delegates to the prefixable LibraryBootstrap::init().
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

if (function_exists('add_action') && !has_action('after_setup_theme', 'hyperfields_bootstrap_init')) {
    add_action('after_setup_theme', 'hyperfields_bootstrap_init', 0);
}
