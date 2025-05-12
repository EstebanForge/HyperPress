<?php

declare(strict_types=1);

/**
 * Plugin Name: API for HTMX
 * Plugin URI: https://github.com/EstebanForge/HTMX-API-WP
 * Description: Add an API endpoint to support HTMX powered themes or plugins on your site.
 * Version: 1.2.0
 * Author: Esteban Cuevas
 * Author URI: https://actitud.xyz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: api-for-htmx
 * Domain Path: /languages
 * Requires at least: 6.4
 * Tested up to: 6.9
 * Requires PHP: 8.1
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get this instance's version and real path (resolving symlinks)
$hxw_plugin_data_for_api_for_htmx = get_file_data(__FILE__, ['Version' => 'Version'], false);
$current_hxw_instance_version_for_api_for_htmx = $hxw_plugin_data_for_api_for_htmx['Version'] ?? '0.0.0'; // Default to 0.0.0 if not found
$current_hxw_instance_path_for_api_for_htmx = realpath(__FILE__);

// Register this instance as a candidate
// Globals, i know. But we need a fast way to do this.
if (!isset($GLOBALS['hxwp_api_for_htmx_candidates']) || !is_array($GLOBALS['hxwp_api_for_htmx_candidates'])) {
    $GLOBALS['hxwp_api_for_htmx_candidates'] = [];
}

// Use path as key to prevent duplicates from the same file if included multiple times
$GLOBALS['hxwp_api_for_htmx_candidates'][$current_hxw_instance_path_for_api_for_htmx] = [
    'version' => $current_hxw_instance_version_for_api_for_htmx,
    'path'    => $current_hxw_instance_path_for_api_for_htmx,
    'init_function' => 'hxwp_run_initialization_logic_for_api_for_htmx',
];

// Hook to decide and run the winner. This action should only be added once.
if (!has_action('plugins_loaded', 'hxwp_select_and_load_latest_api_for_htmx')) {
    add_action('plugins_loaded', 'hxwp_select_and_load_latest_api_for_htmx', 0); // Priority 0 to run very early
}

/**
 * Contains the actual plugin initialization logic.
 * This function is called only for the winning (latest version) instance.
 *
 * @param string $plugin_file_path Path to the plugin file that should run.
 * @param string $plugin_version   The version of the plugin file.
 */
if (!function_exists('hxwp_run_initialization_logic_for_api_for_htmx')) {
    function hxwp_run_initialization_logic_for_api_for_htmx(string $plugin_file_path, string $plugin_version): void
    {
        // These constants signify that the chosen instance is now loading.
        define('HXWP_INSTANCE_LOADED', true);
        define('HXWP_LOADED_VERSION', $plugin_version);
        define('HXWP_INSTANCE_LOADED_PATH', $plugin_file_path);

        // Define plugin constants using the provided path and version
        define('HXWP_VERSION', $plugin_version);
        define('HXWP_ABSPATH', plugin_dir_path($plugin_file_path));
        define('HXWP_BASENAME', plugin_basename($plugin_file_path));
        define('HXWP_PLUGIN_URL', plugin_dir_url($plugin_file_path));
        define('HXWP_ENDPOINT', 'wp-htmx');
        define('HXWP_ENDPOINT_VERSION', 'v1');
        define('HXWP_TEMPLATE_DIR', 'htmx-templates');
        define('HXWP_EXT', '.htmx.php');

        // Composer autoloader for the chosen instance.
        $autoloader_path = HXWP_ABSPATH . 'vendor/autoload.php';
        if (file_exists($autoloader_path)) {
            require_once $autoloader_path;
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
        register_activation_hook($plugin_file_path, ['HXWP\Admin\Activation', 'activate']);
        register_deactivation_hook($plugin_file_path, ['HXWP\Admin\Activation', 'deactivate']);

        // Initialize the plugin's main class.
        if (class_exists('HXWP\Main')) {
            $hxwp = new HXWP\Main();
            $hxwp->run();
        } else {
            // Log an error or handle the case where the main class is not found.
            // This might happen if the autoloader failed or classes are not correctly namespaced/located.
            if (defined('WP_DEBUG') && WP_DEBUG === true) {
                error_log('API for HTMX: HXWP\HXWP_Main class not found. Autoloader or class structure issue.');
            }
        }
    }
}

/**
 * Selects the latest version from registered candidates and runs its initialization.
 * This function is hooked to 'plugins_loaded' at priority 0.
 */
if (!function_exists('hxwp_select_and_load_latest_api_for_htmx')) {
    function hxwp_select_and_load_latest_api_for_htmx(): void
    {
        if (empty($GLOBALS['hxwp_api_for_htmx_candidates']) || !is_array($GLOBALS['hxwp_api_for_htmx_candidates'])) {
            return;
        }

        $candidates = $GLOBALS['hxwp_api_for_htmx_candidates'];

        // Sort candidates by version in descending order (latest version first).
        uasort($candidates, fn ($a, $b) => version_compare($b['version'], $a['version']));

        $winner = reset($candidates); // Get the first candidate (which is the latest version).

        if ($winner && isset($winner['path'], $winner['version'], $winner['init_function']) && function_exists($winner['init_function'])) {
            // Call the initialization function of the winning instance.
            call_user_func($winner['init_function'], $winner['path'], $winner['version']);
        } elseif ($winner && defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log('API for HTMX: Winning candidate\'s init_function ' . esc_html($winner['init_function'] ?? 'N/A') . ' not found or candidate structure invalid.');
        }

        // Clean up the global array to free memory and prevent re-processing.
        unset($GLOBALS['hxwp_api_for_htmx_candidates']);
    }
}
