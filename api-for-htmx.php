<?php

declare(strict_types=1);

/**
 * Plugin Name: Hypermedia API for WordPress
 * Plugin URI: https://github.com/EstebanForge/Hypermedia-API-WordPress
 * Description: Adds API endpoints and integration for hypermedia libraries like HTMX, AlpineJS, and Datastar.
 * Version: 2.0.0
 * Author: Esteban Cuevas
 * Author URI: https://actitud.xyz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: api-for-htmx
 * Domain Path: /languages
 * Requires at least: 6.4
 * Tested up to: 6.9
 * Requires PHP: 8.1.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get this instance's version and real path (resolving symlinks)
$hmapi_plugin_data = get_file_data(__FILE__, ['Version' => 'Version'], false);
$current_hmapi_instance_version = $hmapi_plugin_data['Version'] ?? '0.0.0'; // Default to 0.0.0 if not found
$current_hmapi_instance_path = realpath(__FILE__);

// Register this instance as a candidate
// Globals, i know. But we need a fast way to do this.
if (!isset($GLOBALS['hmapi_api_candidates']) || !is_array($GLOBALS['hmapi_api_candidates'])) {
    $GLOBALS['hmapi_api_candidates'] = [];
}

// Use path as key to prevent duplicates from the same file if included multiple times
$GLOBALS['hmapi_api_candidates'][$current_hmapi_instance_path] = [
    'version' => $current_hmapi_instance_version,
    'path'    => $current_hmapi_instance_path,
    'init_function' => 'hmapi_run_initialization_logic',
];

// Hook to decide and run the winner. This action should only be added once.
if (!has_action('plugins_loaded', 'hmapi_select_and_load_latest')) {
    add_action('plugins_loaded', 'hmapi_select_and_load_latest', 0); // Priority 0 to run very early
}

/*
 * Contains the actual plugin initialization logic.
 * This function is called only for the winning (latest version) instance.
 *
 * @param string $plugin_file_path Path to the plugin file that should run.
 * @param string $plugin_version   The version of the plugin file.
 */
if (!function_exists('hmapi_run_initialization_logic')) {
    function hmapi_run_initialization_logic(string $plugin_file_path, string $plugin_version): void
    {
        // These constants signify that the chosen instance is now loading.
        define('HMAPI_INSTANCE_LOADED', true);
        define('HMAPI_LOADED_VERSION', $plugin_version);
        define('HMAPI_INSTANCE_LOADED_PATH', $plugin_file_path);

        // Define plugin constants using the provided path and version
        define('HMAPI_VERSION', $plugin_version);
        define('HMAPI_ABSPATH', plugin_dir_path($plugin_file_path));
        define('HMAPI_BASENAME', plugin_basename($plugin_file_path));
        define('HMAPI_PLUGIN_URL', plugin_dir_url($plugin_file_path));
        define('HMAPI_PLUGIN_FILE', $plugin_file_path);
        define('HMAPI_ENDPOINT', 'wp-html'); // New primary endpoint
        define('HMAPI_LEGACY_ENDPOINT', 'wp-htmx');
        define('HMAPI_TEMPLATE_DIR', 'hypermedia'); // Default template directory in theme
        define('HMAPI_LEGACY_TEMPLATE_DIR', 'htmx-templates'); // Legacy template directory in theme
        define('HMAPI_TEMPLATE_EXT', '.hm.php'); // Default template file extension
        define('HMAPI_LEGACY_TEMPLATE_EXT', '.htmx.php'); // Legacy template file extension
        define('HMAPI_ENDPOINT_VERSION', 'v1');

        // --- Backward Compatibility Aliases for Constants ---
        if (!defined('HXWP_VERSION')) {
            define('HXWP_VERSION', HMAPI_VERSION);
            define('HXWP_ABSPATH', HMAPI_ABSPATH);
            define('HXWP_BASENAME', HMAPI_BASENAME);
            define('HXWP_PLUGIN_URL', HMAPI_PLUGIN_URL);
            define('HXWP_ENDPOINT', HMAPI_LEGACY_ENDPOINT);
            define('HXWP_ENDPOINT_VERSION', HMAPI_ENDPOINT_VERSION);
            define('HXWP_TEMPLATE_DIR', HMAPI_TEMPLATE_DIR);
        }
        // --- End Backward Compatibility Aliases ---

        // Composer autoloader
        if (file_exists(HMAPI_ABSPATH . 'vendor/autoload.php')) {
            require_once HMAPI_ABSPATH . 'vendor/autoload.php';
            // Helpers
            require_once HMAPI_ABSPATH . 'includes/helpers.php';
        } else {
            // Log error or display admin notice
            add_action('admin_notices', function () {
                echo '<div class="error"><p>' . __('Hypermedia API: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'api-for-htmx') . '</p></div>';
            });

            return;
        }

        // "Don't run when..." check, moved here to allow class loading for library use cases.
        // Ensures that boolean true is checked, not just definition.
        if ((defined('DOING_CRON') && DOING_CRON === true) ||
             (defined('DOING_AJAX') && DOING_AJAX === true) ||
             (defined('REST_REQUEST') && REST_REQUEST === true) ||
             (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST === true) ||
             (defined('WP_CLI') && WP_CLI === true)) {
            // The plugin's runtime (hooks, etc.) is skipped, but classes are available via autoloader.
            return;
        }

        // Activation and deactivation hooks, tied to the specific plugin file.
        register_activation_hook($plugin_file_path, ['HMApi\Admin\Activation', 'activate']);
        register_deactivation_hook($plugin_file_path, ['HMApi\Admin\Activation', 'deactivate']);

        // Initialize the plugin's main class.
        if (class_exists('HMApi\Main')) {
            $router = new HMApi\Router();
            $render = new HMApi\Render();
            $config = new HMApi\Config();
            $compatibility = new HMApi\Compatibility();
            $theme_support = new HMApi\Theme();
            $hmapi_main = new HMApi\Main(
                $router,
                $render,
                $config,
                $compatibility,
                $theme_support
            );
            $hmapi_main->run();
        } else {
            // Log an error or handle the case where the main class is not found.
            // This might happen if the autoloader failed or classes are not correctly namespaced/located.
            if (defined('WP_DEBUG') && WP_DEBUG === true) {
                error_log('Hypermedia API for WordPress: HMApi\Main class not found. Autoloader or class structure issue.');
            }
        }
    }
}

/*
 * Selects the latest version from registered candidates and runs its initialization.
 * This function is hooked to 'plugins_loaded' at priority 0.
 */
if (!function_exists('hmapi_select_and_load_latest')) {
    function hmapi_select_and_load_latest(): void
    {
        if (empty($GLOBALS['hmapi_api_candidates']) || !is_array($GLOBALS['hmapi_api_candidates'])) {
            return;
        }

        $candidates = $GLOBALS['hmapi_api_candidates'];

        // Sort candidates by version in descending order (latest version first).
        uasort($candidates, fn ($a, $b) => version_compare($b['version'], $a['version']));

        $winner = reset($candidates); // Get the first candidate (which is the latest version).

        if ($winner && isset($winner['path'], $winner['version'], $winner['init_function']) && function_exists($winner['init_function'])) {
            // Call the initialization function of the winning instance.
            call_user_func($winner['init_function'], $winner['path'], $winner['version']);
        } elseif ($winner && defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('Hypermedia API for WordPress: Winning candidate\'s init_function ' . esc_html($winner['init_function'] ?? 'N/A') . ' not found or candidate structure invalid.');
        }

        // Clean up the global array to free memory and prevent re-processing.
        unset($GLOBALS['hmapi_api_candidates']);
    }
}
