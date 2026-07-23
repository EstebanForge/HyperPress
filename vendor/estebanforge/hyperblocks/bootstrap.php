<?php

declare(strict_types=1);

/**
 * Core library bootstrap file for HyperBlocks.
 *
 * This file registers the library instance as a candidate and ensures the newest
 * version is selected and initialized when multiple copies are loaded.
 */
if (!function_exists('hyperblocks_run_initialization_logic')) {
    /**
     * Initialize HyperBlocks with the given base file path and version.
     *
     * @param string $bootstrap_file_path Absolute path to the bootstrap file.
     * @param string $version             Semantic version string (e.g., '1.0.0').
     * @return void
     */
    function hyperblocks_run_initialization_logic(string $bootstrap_file_path, string $version): void
    {
        // Ensure this logic runs only once, but surface a stale election loudly
        // when a newer version requests init after an older one already loaded.
        // We cannot undefine constants or un-register hooks, so the older
        // instance keeps serving, but this log makes the problem diagnosable
        // instead of silent.
        if (defined('HYPERBLOCKS_INSTANCE_LOADED')) {
            $loaded_version = defined('HYPERBLOCKS_LOADED_VERSION') ? HYPERBLOCKS_LOADED_VERSION : '0.0.0';
            if (version_compare($version, $loaded_version, '>')) {
                if (function_exists('error_log')) {
                    error_log(sprintf(
                        'HyperBlocks: newer version %s at %s requested init after version %s was already loaded from %s. '
                        . 'The older instance is serving. This means the multi-instance version election did not run before initialization; '
                        . 'ensure the highest-version consumer calls hyperblocks_run_initialization_logic() before any other copy initializes.',
                        $version,
                        $bootstrap_file_path,
                        $loaded_version,
                        defined('HYPERBLOCKS_INSTANCE_LOADED_PATH') ? HYPERBLOCKS_INSTANCE_LOADED_PATH : '(unknown)'
                    ));
                }
            }

            return;
        }

        define('HYPERBLOCKS_INSTANCE_LOADED', true);
        define('HYPERBLOCKS_LOADED_VERSION', $version);
        define('HYPERBLOCKS_INSTANCE_LOADED_PATH', $bootstrap_file_path);
        define('HYPERBLOCKS_VERSION', $version);

        $base_dir = rtrim(dirname($bootstrap_file_path), '/\\') . '/';

        if (!defined('HYPERBLOCKS_ABSPATH')) {
            define('HYPERBLOCKS_ABSPATH', $base_dir);
        }
        if (!defined('HYPERBLOCKS_PATH')) {
            define('HYPERBLOCKS_PATH', $base_dir);
        }
        if (!defined('HYPERBLOCKS_PLUGIN_FILE')) {
            define('HYPERBLOCKS_PLUGIN_FILE', $bootstrap_file_path);
        }

        if (!defined('HYPERBLOCKS_PLUGIN_URL')) {
            // Resolve against web-accessible content roots rather than
            // plugins_url(), which only handles files directly under
            // WP_PLUGIN_DIR and 404s when HyperBlocks is vendored elsewhere
            // (e.g. a Bedrock root composer vendor outside the web document
            // root). Empty when HTTP cannot reach the directory; the editor
            // asset registration bails in that case instead of enqueuing a
            // broken URL. Preserve the empty sentinel: rtrim('') . '/' would
            // turn the unresolvable case into '/', defeating that guard.
            $resolved = function_exists('hyperblocks_resolve_content_url')
                ? hyperblocks_resolve_content_url($base_dir)
                : (function_exists('plugins_url')
                    ? plugins_url('', $bootstrap_file_path)
                    : '');
            $plugin_url = $resolved !== '' ? rtrim($resolved, '/\\') . '/' : '';
            define('HYPERBLOCKS_PLUGIN_URL', $plugin_url);
        }

        if (class_exists(HyperBlocks\WordPress\Bootstrap::class) && function_exists('add_action')) {
            HyperBlocks\WordPress\Bootstrap::init();
        }
    }
}

if (!function_exists('hyperblocks_select_and_load_latest')) {
    /**
     * Select and load the latest HyperBlocks version from registered candidates.
     *
     * @return void
     */
    function hyperblocks_select_and_load_latest(): void
    {
        if (empty($GLOBALS['hyperblocks_api_candidates']) || !is_array($GLOBALS['hyperblocks_api_candidates'])) {
            return;
        }

        $candidates = $GLOBALS['hyperblocks_api_candidates'];
        uasort($candidates, static fn ($a, $b) => version_compare($b['version'], $a['version']));
        $winner = reset($candidates);

        if ($winner && isset($winner['path'], $winner['version'], $winner['init_function']) && function_exists($winner['init_function'])) {
            call_user_func($winner['init_function'], $winner['path'], $winner['version']);
        }

        unset($GLOBALS['hyperblocks_api_candidates']);
    }
}

// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
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
//
// Computed unconditionally: it is a property of THIS file's location, needed
// both inside the autoloader-include block and by the HyperFields dependency
// bootstrap below. Defining it only inside the else branch left the post-
// if/else read undefined on the second and subsequent copies to load.
$loadedFromVendorTree = str_contains(str_replace('\\', '/', __DIR__), '/vendor/');

if (defined('HYPERBLOCKS_BOOTSTRAP_LOADED')) {
    // Another copy already ran the one-time autoloader include. Skip straight
    // to candidate registration for THIS copy so the election can see it.
} else {
    define('HYPERBLOCKS_BOOTSTRAP_LOADED', true);

    // Composer autoloader.
    // When loaded from another package's /vendor tree, avoid loading nested vendor/autoload.php
    // to prevent duplicate Composer autoloader class declarations.
    $normalizedDir = str_replace('\\', '/', __DIR__);
    if (!$loadedFromVendorTree && function_exists('wp_normalize_path') && file_exists(__DIR__ . '/vendor/autoload_packages.php')) {
        require_once __DIR__ . '/vendor/autoload_packages.php';
    }
    if (!$loadedFromVendorTree && file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    } elseif (!$loadedFromVendorTree && function_exists('add_action')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="error"><p>' . esc_html__('HyperBlocks: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'hyperblocks') . '</p></div>';
        });
    }
}

// Bootstrap HyperFields dependency.
// When HyperBlocks is loaded standalone (not alongside the HyperFields plugin), we must
// trigger HyperFields' candidate registration so its after_setup_theme initialisation runs.
// The guard inside HyperFields' bootstrap.php prevents double-initialisation.
if (!$loadedFromVendorTree) {
    $hyperfields_bootstrap = __DIR__ . '/vendor/estebanforge/hyperfields/bootstrap.php';
    if (file_exists($hyperfields_bootstrap)) {
        require_once $hyperfields_bootstrap;
    }
}

// Determine version from composer.json for candidate registration.
$current_version = '0.0.0';
$composer_json_path = __DIR__ . '/composer.json';
if (file_exists($composer_json_path)) {
    $composer_data = json_decode((string) file_get_contents($composer_json_path), true);
    if (is_array($composer_data) && isset($composer_data['version'])) {
        $current_version = (string) $composer_data['version'];
    }
}

$current_path = realpath(__FILE__) ?: __FILE__;

if (!isset($GLOBALS['hyperblocks_api_candidates']) || !is_array($GLOBALS['hyperblocks_api_candidates'])) {
    $GLOBALS['hyperblocks_api_candidates'] = [];
}

$GLOBALS['hyperblocks_api_candidates'][$current_path] = [
    'version' => $current_version,
    'path' => $current_path,
    'init_function' => 'hyperblocks_run_initialization_logic',
];

if (function_exists('add_action') && function_exists('has_action')) {
    if (!has_action('after_setup_theme', 'hyperblocks_select_and_load_latest')) {
        add_action('after_setup_theme', 'hyperblocks_select_and_load_latest', 0);
    }
}
