<?php

declare(strict_types=1);

/**
 * Core plugin bootstrap file for HyperFields.
 *
 * This file is responsible for registering the plugin's hooks and initializing the autoloader.
 * It is designed to be loaded only once, regardless of whether the project is used as a standalone
 * plugin or as a library embedded in another project.
 *
 * @since 1.0.0
 */

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
// Support both legacy 'hyperfields.php' and current 'hyperfields.php' entry points
$plugin_file_candidates = [
    __DIR__ . '/hyperfields.php',
];
$plugin_file_path = null;
foreach ($plugin_file_candidates as $candidate) {
    if (file_exists($candidate)) {
        $plugin_file_path = $candidate;
        break;
    }
}
$current_hyperfields_instance_version = '1.0.3';
$current_hyperfields_instance_path = null;

// Check if we're running as a plugin (one of the entry files exists) or as a library
if ($plugin_file_path && file_exists($plugin_file_path)) {
    // Plugin mode: read version from the main plugin file
    $hyperfields_plugin_data = get_file_data($plugin_file_path, ['Version' => 'Version'], false);
    $current_hyperfields_instance_version = $hyperfields_plugin_data['Version'] ?? '1.0.3';
    $current_hyperfields_instance_path = realpath($plugin_file_path);
} else {
    // Library mode: try to get version from composer.json or use a fallback
    $composer_json_path = __DIR__ . '/composer.json';
    if (file_exists($composer_json_path)) {
        $composer_data = json_decode(file_get_contents($composer_json_path), true);
        $current_hyperfields_instance_version = $composer_data['version'] ?? '1.0.0';
    }
    // Use bootstrap.php path as fallback for library mode
    $current_hyperfields_instance_path = realpath(__FILE__);
}

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
if (!has_action('after_setup_theme', 'hyperfields_select_and_load_latest')) {
    add_action('after_setup_theme', 'hyperfields_select_and_load_latest', 0);
}

if (!function_exists('hyperfields_run_initialization_logic')) {
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

        // Determine if we're in library mode vs plugin mode (support legacy entry file)
        $basename = $plugin_file_path ? basename($plugin_file_path) : '';
        $is_library_mode = !in_array($basename, ['hyperfields.php'], true);

        if ($is_library_mode) {
            // Library mode: use the directory containing the bootstrap/plugin file
            $plugin_dir = dirname($plugin_file_path);
            define('HYPERFIELDS_ABSPATH', trailingslashit($plugin_dir));
            define('HYPERFIELDS_BASENAME', 'hyperfields/bootstrap.php');
            define('HYPERFIELDS_PLUGIN_URL', ''); // Not applicable in library mode
            define('HYPERFIELDS_PLUGIN_FILE', $plugin_file_path);
        
        // Load helpers after constants are defined.
        require_once HYPERFIELDS_ABSPATH . 'includes/helpers.php';
        require_once HYPERFIELDS_ABSPATH . 'includes/backward-compatibility.php';
        } else {
            // Plugin mode: use standard WordPress plugin functions
            define('HYPERFIELDS_ABSPATH', plugin_dir_path($plugin_file_path));
            define('HYPERFIELDS_BASENAME', plugin_basename($plugin_file_path));
            define('HYPERFIELDS_PLUGIN_URL', plugin_dir_url($plugin_file_path));
            define('HYPERFIELDS_PLUGIN_FILE', $plugin_file_path);
        
        // Load helpers after constants are defined.
        require_once HYPERFIELDS_ABSPATH . 'includes/helpers.php';
        require_once HYPERFIELDS_ABSPATH . 'includes/backward-compatibility.php';
        }

        // Only register activation/deactivation hooks in plugin mode
        if (!$is_library_mode) {
            register_activation_hook($plugin_file_path, ['HyperFields\Admin\Activation', 'activate']);
            register_deactivation_hook($plugin_file_path, ['HyperFields\Admin\Activation', 'deactivate']);
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

// Test helper: re-register candidate and ensure selection hook exists, without relying on include semantics.
if (!function_exists('hyperfields_register_candidate_for_tests')) {
    function hyperfields_register_candidate_for_tests(): void
    {
        // Determine current instance path/version similarly to main bootstrap logic.
        $plugin_file_candidates = [
            __DIR__ . '/hyperfields.php',
        ];
        $plugin_file_path = null;
        foreach ($plugin_file_candidates as $candidate) {
            if (file_exists($candidate)) { $plugin_file_path = $candidate; break; }
        }

        $current_version = '1.0.3';
        $current_path = null;
        if ($plugin_file_path && file_exists($plugin_file_path)) {
            $data = get_file_data($plugin_file_path, ['Version' => 'Version'], false);
            $current_version = $data['Version'] ?? '1.0.3';
            $current_path = realpath($plugin_file_path) ?: $plugin_file_path;
        } else {
            $composer_json_path = __DIR__ . '/composer.json';
            if (file_exists($composer_json_path)) {
                $composer_data = json_decode(file_get_contents($composer_json_path), true);
                if (is_array($composer_data) && isset($composer_data['version'])) {
                    $current_version = (string) $composer_data['version'];
                }
            }
            $current_path = realpath(__FILE__) ?: __FILE__;
        }

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
