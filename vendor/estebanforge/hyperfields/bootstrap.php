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

// Define global functions BEFORE early-return guards so they're always available.
// Tests that run in separate processes need these functions even when HYPERFIELDS_BOOTSTRAP_LOADED is set.
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
        // Ensure this logic runs only once.
        if (defined('HYPERFIELDS_INSTANCE_LOADED')) {
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
        $plugin_url = plugins_url('', $plugin_file_path);
        define('HYPERFIELDS_PLUGIN_URL', trailingslashit($plugin_url));
        define('HYPERFIELDS_PLUGIN_FILE', $plugin_file_path);

        // Load helpers after constants are defined.
        require_once HYPERFIELDS_ABSPATH . 'includes/helpers.php';
        require_once HYPERFIELDS_ABSPATH . 'includes/backward-compatibility.php';

        if (!defined('HYPERPRESS_PLUGIN_URL')) {
            define('HYPERPRESS_PLUGIN_URL', HYPERFIELDS_PLUGIN_URL);
        }

        if (!defined('HYPERPRESS_VERSION')) {
            define('HYPERPRESS_VERSION', $plugin_version);
        }

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
        $current_version = '1.0.0';
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

// Use a unique constant to ensure this bootstrap logic runs only once.
if (defined('HYPERFIELDS_BOOTSTRAP_LOADED')) {
    return;
}

define('HYPERFIELDS_BOOTSTRAP_LOADED', true);

// Composer autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Display an admin notice if no autoloader is found, but continue so tests can register hooks/candidates.
    add_action('admin_notices', function () {
        echo '<div class="error"><p>' . esc_html__('HyperFields: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'hyperfields') . '</p></div>';
    });
}

// Get this instance's version and real path (resolving symlinks)
$current_hyperfields_instance_version = '1.0.0';
$current_hyperfields_instance_path = null;

// Library mode: try to get version from composer.json or use a fallback
$composer_json_path = __DIR__ . '/composer.json';
if (file_exists($composer_json_path)) {
    $composer_data = json_decode(file_get_contents($composer_json_path), true);
    $current_hyperfields_instance_version = $composer_data['version'] ?? '1.0.0';
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
