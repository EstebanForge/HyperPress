<?php

declare(strict_types=1);

/**
 * Core bootstrap for HyperPress-Core.
 *
 * Dev-environment auto-init bridge: when HyperPress-Core is loaded directly
 * via Composer (unprefixed), this file schedules initialization at
 * after_setup_theme by delegating to Bootstrap::init(), which lives under the
 * PSR-4 root and holds the prefix-safe first-to-boot LOADED guard. There is
 * no candidate election, no version compare, and no jetpack dependency.
 *
 * Under namespace prefixing (Mozart) this file is not copied (it sits outside
 * src/); prefixed consumers call HyperPress\Bootstrap::init() explicitly,
 * which is already prefixed.
 *
 * @since 2.0.0
 */

// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

// Composer autoloader. Skip the nested vendor/autoload.php when this file is
// itself inside another package's /vendor/ tree (would double-declare Composer
// autoloader classes). bootstrap.php runs once per process (Composer files
// autoload dedup + require_once), so no global reload guard is needed.
$normalizedDir = str_replace('\\', '/', __DIR__);
$loadedFromVendorTree = str_contains($normalizedDir, '/vendor/');
if (!$loadedFromVendorTree) {
    // Optional dev override: load local HyperFields/HyperBlocks copies before
    // Composer, so a monorepo checkout can develop against sibling sources.
    $use_local_libs = getenv('HYPERPRESS_USE_LOCAL_LIBS') === '1';
    if ($use_local_libs) {
        foreach ([dirname(__DIR__) . '/HyperFields', dirname(__DIR__) . '/HyperBlocks'] as $lib_path) {
            $lib_path = realpath($lib_path) ?: $lib_path;
            $bootstrap = $lib_path . '/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }
        }
    }

    if (file_exists(__DIR__ . '/vendor/autoload_packages.php')) {
        require_once __DIR__ . '/vendor/autoload_packages.php';
    }
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    } else {
        // No autoloader found: surface an admin notice but continue so tests
        // can register hooks.
        add_action('admin_notices', static function (): void {
            echo '<div class="error"><p>' . esc_html__('HyperPress: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'api-for-htmx') . '</p></div>';
        });
    }
}

// Bootstrap the HyperFields and HyperBlocks dependencies. When HyperPress-Core
// runs standalone (no upstream plugin bootstrapping them first), trigger each
// library's bootstrap so their after_setup_theme initialization runs. The
// first-to-boot LOADED guard inside each library prevents double-init.
if (!$loadedFromVendorTree) {
    foreach ([
        __DIR__ . '/vendor/estebanforge/hyperfields/bootstrap.php',
        __DIR__ . '/vendor/estebanforge/hyperblocks/bootstrap.php',
    ] as $dep_bootstrap) {
        if (file_exists($dep_bootstrap)) {
            require_once $dep_bootstrap;
        }
    }
}

// Schedule initialization at after_setup_theme (priority 0, original timing).
// Delegates to Bootstrap::init(), which carries the prefix-safe first-to-boot
// guard and sets Config runtime identity.
if (!function_exists('hyperpress_bootstrap_init')) {
    /**
     * Initialize HyperPress-Core for this copy at after_setup_theme.
     *
     * @return void
     */
    function hyperpress_bootstrap_init(): void
    {
        \HyperPress\Bootstrap::init();
    }
}

if (function_exists('add_action') && !has_action('after_setup_theme', 'hyperpress_bootstrap_init')) {
    add_action('after_setup_theme', 'hyperpress_bootstrap_init', 0);
}
