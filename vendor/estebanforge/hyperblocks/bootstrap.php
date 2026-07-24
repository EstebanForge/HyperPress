<?php

declare(strict_types=1);

/**
 * Core bootstrap for HyperBlocks.
 *
 * Dev-environment auto-init bridge: when HyperBlocks is loaded directly via
 * Composer (unprefixed), this file schedules initialization at
 * after_setup_theme by delegating to WordPress\Bootstrap::init(), which lives
 * under the PSR-4 root and holds the first-to-boot LOADED guard. Duplicate-load
 * protection is namespace-scoped and prefix-safe (see WordPress\Bootstrap::init());
 * there is no candidate election, no version compare, and no jetpack dependency.
 *
 * Under namespace prefixing (Mozart) this file is not copied (it sits outside
 * src/); prefixed consumers call HyperBlocks\WordPress\Bootstrap::init()
 * explicitly, which is already prefixed.
 *
 * @since 1.0.0
 */

// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
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
    add_action('admin_notices', static function (): void {
        echo '<div class="error"><p>' . esc_html__('HyperBlocks: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'hyperblocks') . '</p></div>';
    });
}

// Bootstrap the HyperFields dependency. When HyperBlocks runs standalone
// (not alongside the HyperFields plugin), trigger HyperFields' bootstrap so its
// after_setup_theme initialization runs. The guard inside HyperFields'
// bootstrap.php (first-to-boot LOADED) prevents double-initialization.
if (!$loadedFromVendorTree) {
    $hyperfieldsBootstrap = __DIR__ . '/vendor/estebanforge/hyperfields/bootstrap.php';
    if (file_exists($hyperfieldsBootstrap)) {
        require_once $hyperfieldsBootstrap;
    }
}

// Schedule initialization at after_setup_theme (priority 0, original timing).
// Delegates to WordPress\Bootstrap::init(), which carries the prefix-safe
// first-to-boot guard and sets Config runtime identity.
if (!function_exists('hyperblocks_bootstrap_init')) {
    /**
     * Initialize HyperBlocks for this copy at after_setup_theme.
     *
     * @return void
     */
    function hyperblocks_bootstrap_init(): void
    {
        \HyperBlocks\WordPress\Bootstrap::init();
    }
}

if (function_exists('add_action') && !has_action('after_setup_theme', 'hyperblocks_bootstrap_init')) {
    add_action('after_setup_theme', 'hyperblocks_bootstrap_init', 0);
}
