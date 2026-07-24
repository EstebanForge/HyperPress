<?php

declare(strict_types=1);

/**
 * Core plugin bootstrap file for HyperFields.
 *
 * This file is responsible for registering the plugin's hooks and initializing the autoloader.
 * It is designed to be loaded only once for library usage in host projects.
 *
 * @since 1.0.0
 */

if (!defined('HYPERFIELDS_DEFAULT_VERSION')) {
    define('HYPERFIELDS_DEFAULT_VERSION', '1.4.5');
}

// Define global functions BEFORE early-return guards so they're always available.
// Tests that run in separate processes need these functions even when HYPERFIELDS_BOOTSTRAP_LOADED is set.
if (!function_exists('hyperfields_resolve_plugin_url')) {
    /**
     * Resolve the library's public content URL, defending against class
     * shadowing.
     *
     * The multi-instance version election guarantees the newest *init* runs,
     * but it cannot guarantee the newest *class* is loaded: PHP's autoloader
     * stack may resolve HyperFields\LibraryBootstrap from a stale bundled
     * copy (e.g. a consumer vendoring hyperfields < 1.4.1 alongside a root
     * that resolves >= 1.4.1). resolveContentUrl() was added in 1.4.1, so a
     * stale class lacks it and the call would fatal. Guard the call, surface
     * the divergence loudly when it happens, and fall back to plugins_url().
     * The class-missing case is a normal fallback (no shadow); the alarm
     * fires only when the class IS loaded but lacks the method — the exact
     * divergence signature.
     *
     * The class FQCN is injectable so the guard branches are unit-testable
     * without process isolation (see ResolvePluginUrlTest).
     *
     * @since 1.4.2
     *
     * @param string $plugin_dir       Library base directory.
     * @param string $plugin_file_path Bootstrap file path (for plugins_url fallback).
     * @param string $plugin_version   Elected init version (for the alarm).
     * @param string $class            LibraryBootstrap FQCN (injectable for tests).
     *
     * @return string Resolved URL, or '' when unreachable.
     */
    function hyperfields_resolve_plugin_url(string $plugin_dir, string $plugin_file_path, string $plugin_version, string $class = 'HyperFields\\LibraryBootstrap', ?callable $alarm = null): string
    {
        if (class_exists($class) && method_exists($class, 'resolveContentUrl')) {
            return $class::resolveContentUrl($plugin_dir);
        }

        // Shadow signature: class loaded but method absent. Emit the alarm
        // via the injectable $alarm callable (defaults to error_log) and fall
        // back. Injectability makes the emission unit-testable without
        // capturing error_log under the test harness; the trigger logic is
        // also covered by hyperfields_is_class_shadowed().
        if (hyperfields_is_class_shadowed($class)) {
            $alarm ??= static function (string $message): void {
                if (function_exists('error_log')) {
                    error_log($message);
                }
            };
            $loaded_class_file = '(unknown)';
            try {
                $loaded_class_file = (new \ReflectionClass($class))->getFileName() ?: '(unknown)';
            } catch (\Throwable $e) {
                $loaded_class_file = '(unresolvable: ' . $e->getMessage() . ')';
            }
            $alarm(sprintf(
                'HyperFields: class shadowing detected. Elected init v%s at %s, but the loaded '
                . '%s (from %s) lacks resolveContentUrl() (added in 1.4.1). '
                . 'A stale bundled copy is shadowing the elected init. Falling back to plugins_url(). '
                . 'Fix: every consumer must directly require automattic/jetpack-autoloader in its '
                . 'composer.json so Jetpack owns class identity, and ship the same HyperFields version '
                . 'across all bundles.',
                $plugin_version,
                $plugin_file_path,
                $class,
                $loaded_class_file
            ));
        }

        return function_exists('plugins_url') ? plugins_url('', $plugin_file_path) : '';
    }
}

if (!function_exists('hyperfields_is_class_shadowed')) {
    /**
     * Detect the class-shadowing signature: the LibraryBootstrap class is
     * loaded but lacks resolveContentUrl() (added in 1.4.1). True iff a stale
     * bundled copy is shadowing the elected-newest init — the exact condition
     * that fatals without the guard in hyperfields_resolve_plugin_url().
     *
     * Pure predicate (no side effects) so the trigger logic is unit-testable
     * without error_log capture or process isolation.
     *
     * @since 1.4.2
     *
     * @param string $class LibraryBootstrap FQCN (injectable for tests).
     */
    function hyperfields_is_class_shadowed(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        // Absent method → definitely shadowed (the original incident vector: a
        // < 1.4.1 class lacks resolveContentUrl entirely).
        if (!method_exists($class, 'resolveContentUrl')) {
            return true;
        }

        // Behavioral drift: the method exists but the loaded class is older
        // than the version that introduced the stable resolveContentUrl()
        // contract (1.4.1). Catches a present-but-changed method that
        // method_exists alone misses. Classes without a VERSION stamp
        // (pre-1.4.4, or test stubs) skip this check and fall through as
        // not-shadowed (the method_exists check above already governed them).
        // hasConstant() first: getConstant() on a missing constant is
        // deprecated since PHP 8.5.
        $reflection = new \ReflectionClass($class);
        if ($reflection->hasConstant('VERSION')) {
            if (version_compare((string) $reflection->getConstant('VERSION'), '1.4.1', '<')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('hyperfields_run_initialization_logic')) {
    /**
     * Initialize HyperFields with the given base file path and version.
     *
     * This function sets up all necessary constants, loads helper files, and initializes
     * core systems (Registry, Assets, TemplateLoader). It is designed for library usage
     * (Composer or direct bootstrap include).
     *
     * @since 1.0.0
     *
     * @param string $plugin_file_path Absolute path to the bootstrap file.
     * @param string $plugin_version   Semantic version string (e.g., '1.0.0').
     *
     * @return void
     */
    function hyperfields_run_initialization_logic(string $plugin_file_path, string $plugin_version): void
    {
        // Ensure this logic runs only once, but only let a NEWER or equal
        // version win once loaded. If a lower version somehow initialized first
        // (e.g. an older vendored copy whose bootstrap ran before the newer
        // copy's explicit init call), surface it loudly: we cannot undefine
        // constants or un-register hooks, so the older instance keeps serving,
        // but this log makes the stale election diagnosable instead of silent.
        if (defined('HYPERFIELDS_INSTANCE_LOADED')) {
            $loaded_version = defined('HYPERFIELDS_LOADED_VERSION') ? HYPERFIELDS_LOADED_VERSION : '0.0.0';
            if (version_compare($plugin_version, $loaded_version, '>')) {
                if (function_exists('error_log')) {
                    error_log(sprintf(
                        'HyperFields: newer version %s at %s requested init after version %s was already loaded from %s. ' .
                        'The older instance is serving. This means the multi-instance version election did not run before initialization; ' .
                        'ensure the highest-version consumer calls hyperfields_run_initialization_logic() before any other copy initializes.',
                        $plugin_version,
                        $plugin_file_path,
                        $loaded_version,
                        defined('HYPERFIELDS_INSTANCE_LOADED_PATH') ? HYPERFIELDS_INSTANCE_LOADED_PATH : '(unknown)'
                    ));
                }
            }
            return;
        }
        define('HYPERFIELDS_INSTANCE_LOADED', true);
        define('HYPERFIELDS_LOADED_VERSION', $plugin_version);
        define('HYPERFIELDS_INSTANCE_LOADED_PATH', $plugin_file_path);
        define('HYPERFIELDS_VERSION', $plugin_version);

        // Library mode: use the directory containing the bootstrap file
        $plugin_dir = dirname($plugin_file_path);
        define('HYPERFIELDS_ABSPATH', trailingslashit($plugin_dir));
        define('HYPERFIELDS_BASENAME', 'hyperfields/bootstrap.php');
        // Resolve against web-accessible content roots rather than plugins_url(),
        // which only handles files directly under WP_PLUGIN_DIR and 404s when
        // the library is vendored elsewhere (e.g. a Bedrock root composer
        // vendor outside the web document root). Empty when HTTP cannot reach
        // the directory so asset enqueue paths can bail instead of emitting
        // a broken URL. Preserve the empty sentinel (rtrim('') . '/' would
        // turn the unresolvable case into '/' and defeat downstream guards).
        // Resolve the library's public content URL, defending against class
        // shadowing (a stale bundled LibraryBootstrap lacking resolveContentUrl,
        // added in 1.4.1). Extracted to hyperfields_resolve_plugin_url() so the
        // guard is unit-testable; see ResolvePluginUrlTest.
        $resolved_url = hyperfields_resolve_plugin_url($plugin_dir, $plugin_file_path, $plugin_version);
        if (!defined('HYPERFIELDS_PLUGIN_URL')) {
            define('HYPERFIELDS_PLUGIN_URL', $resolved_url !== '' ? trailingslashit($resolved_url) : '');
        }
        define('HYPERFIELDS_PLUGIN_FILE', $plugin_file_path);

        // Load helpers after constants are defined.
        require_once HYPERFIELDS_ABSPATH . 'includes/helpers.php';
        require_once HYPERFIELDS_ABSPATH . 'includes/backward-compatibility.php';

        // Initialize the fields system
        if (class_exists('HyperFields\Registry')) {
            $fieldsRegistry = HyperFields\Registry::getInstance();
            $fieldsRegistry->init();
        }

        // Initialize the assets manager
        if (class_exists('HyperFields\Assets')) {
            $assets = new HyperFields\Assets();
            $assets->init();
        }

        // Initialize the template loader
        if (class_exists('HyperFields\TemplateLoader')) {
            HyperFields\TemplateLoader::init();
        }

        // Initialize transfer audit logger (hooks + lazy schema setup).
        if (class_exists('HyperFields\Transfer\AuditLogger')) {
            HyperFields\Transfer\AuditLogger::init();
        }
    }
}

if (!function_exists('hyperfields_select_and_load_latest')) {
    /**
     * Select and load the latest HyperFields version from registered candidates.
     *
     * Multiple instances of HyperFields may be registered across dependencies.
     * This function selects the highest version candidate and initializes it, ensuring only
     * one active instance. Called via 'after_setup_theme' action hook.
     *
     * @since 1.0.0
     *
     * @return void
     */
    function hyperfields_select_and_load_latest(): void
    {
        if (empty($GLOBALS['hyperfields_api_candidates']) || !is_array($GLOBALS['hyperfields_api_candidates'])) {
            return;
        }

        $candidates = $GLOBALS['hyperfields_api_candidates'];
        uasort($candidates, fn ($a, $b) => version_compare($b['version'], $a['version']));
        $winner = reset($candidates);

        if ($winner && isset($winner['path'], $winner['version'], $winner['init_function']) && function_exists($winner['init_function'])) {
            call_user_func($winner['init_function'], $winner['path'], $winner['version']);
        }

        unset($GLOBALS['hyperfields_api_candidates']);
    }
}

if (!function_exists('hyperfields_register_candidate_for_tests')) {
    /**
     * Test helper: re-register candidate and ensure selection hook exists.
     *
     * This function is intended for unit tests that need to simulate the bootstrap
     * candidate registration process. It reads version info and registers the
     * current instance as a candidate without relying on include/require semantics.
     *
     * @since 1.0.0
     * @internal Only for use in PHPUnit tests.
     *
     * @return void
     */
    function hyperfields_register_candidate_for_tests(): void
    {
        $current_version = HYPERFIELDS_DEFAULT_VERSION;
        $current_path = null;
        $composer_json_path = __DIR__ . '/composer.json';
        if (file_exists($composer_json_path)) {
            $composer_data = json_decode(file_get_contents($composer_json_path), true);
            if (is_array($composer_data) && isset($composer_data['version'])) {
                $current_version = (string) $composer_data['version'];
            }
        }
        $current_path = realpath(__FILE__) ?: __FILE__;

        if (!isset($GLOBALS['hyperfields_api_candidates']) || !is_array($GLOBALS['hyperfields_api_candidates'])) {
            $GLOBALS['hyperfields_api_candidates'] = [];
        }
        $GLOBALS['hyperfields_api_candidates'][$current_path] = [
            'version' => $current_version,
            'path'    => $current_path,
            'init_function' => 'hyperfields_run_initialization_logic',
        ];

        if (!has_action('after_setup_theme', 'hyperfields_select_and_load_latest')) {
            add_action('after_setup_theme', 'hyperfields_select_and_load_latest', 0);
        }
    }
}

// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERFIELDS_TESTING_MODE')) {
    return;
}

// Use a per-instance marker so each vendored copy registers its own
// candidate for version election. A global early-return here would defeat
// the multi-instance election: the first copy to load would set the flag and
// every other copy's bootstrap would bail before registering, leaving only
// the first-loaded (not necessarily highest-version) copy discoverable.
// The candidate array is path-keyed for dedup, and the nested-autoloader
// block below is guarded by $loadedFromVendorTree, so letting every copy
// run its registration is safe.
$hyperfields_bootstrap_path = realpath(__FILE__) ?: __FILE__;
if (defined('HYPERFIELDS_BOOTSTRAP_LOADED')) {
    // Another copy already ran the one-time autoloader include. Skip straight
    // to candidate registration for THIS copy so the election can see it.
} else {
    define('HYPERFIELDS_BOOTSTRAP_LOADED', true);

    // Composer autoloader.
    // When loaded from another package's /vendor tree, avoid loading nested vendor/autoload.php
    // to prevent duplicate Composer autoloader class declarations.
    $normalizedDir = str_replace('\\', '/', __DIR__);
    $loadedFromVendorTree = str_contains($normalizedDir, '/vendor/');
    if (!$loadedFromVendorTree && function_exists('wp_normalize_path') && file_exists(__DIR__ . '/vendor/autoload_packages.php')) {
        require_once __DIR__ . '/vendor/autoload_packages.php';
    }
    if (!$loadedFromVendorTree && file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    } elseif (!$loadedFromVendorTree) {
        // Display an admin notice if no autoloader is found, but continue so tests can register hooks/candidates.
        add_action('admin_notices', function () {
            echo '<div class="error"><p>' . esc_html__('HyperFields: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'hyperfields') . '</p></div>';
        });
    }
}

// Get this instance's version and real path (resolving symlinks)
$current_hyperfields_instance_version = HYPERFIELDS_DEFAULT_VERSION;
$current_hyperfields_instance_path = null;

// Library mode: try to get version from composer.json or use a fallback
$composer_json_path = __DIR__ . '/composer.json';
if (file_exists($composer_json_path)) {
    $composer_data = json_decode(file_get_contents($composer_json_path), true);
    $current_hyperfields_instance_version = $composer_data['version'] ?? HYPERFIELDS_DEFAULT_VERSION;
}
// Use bootstrap.php path as fallback for library mode
$current_hyperfields_instance_path = realpath(__FILE__);

// Ensure we have a valid path
if ($current_hyperfields_instance_path === false) {
    $current_hyperfields_instance_path = __FILE__;
}

// Register this instance as a candidate
if (!isset($GLOBALS['hyperfields_api_candidates']) || !is_array($GLOBALS['hyperfields_api_candidates'])) {
    $GLOBALS['hyperfields_api_candidates'] = [];
}

// Use path as key to prevent duplicates
$GLOBALS['hyperfields_api_candidates'][$current_hyperfields_instance_path] = [
    'version' => $current_hyperfields_instance_version,
    'path'    => $current_hyperfields_instance_path,
    'init_function' => 'hyperfields_run_initialization_logic',
];

// Use 'after_setup_theme' to ensure this runs after the theme is loaded.
if (function_exists('has_action') && function_exists('add_action')) {
    if (!has_action('after_setup_theme', 'hyperfields_select_and_load_latest')) {
        add_action('after_setup_theme', 'hyperfields_select_and_load_latest', 0);
    }
}
