<?php

declare(strict_types=1);

/**
 * Core plugin bootstrap file.
 *
 * This file is responsible for registering the plugin's hooks and initializing the autoloader.
 * It is designed to be loaded only once, regardless of whether the project is used as a standalone
 * plugin or as a library embedded in another project.
 *
 * @since 2.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Use a unique constant to ensure this bootstrap logic runs only once.
if (defined('HMAPI_BOOTSTRAP_LOADED')) {
    return;
}

define('HMAPI_BOOTSTRAP_LOADED', true);

// Composer autoloader for prefixed dependencies.
if (file_exists(__DIR__ . '/vendor-dist/autoload.php')) {
    require_once __DIR__ . '/vendor-dist/autoload.php';
    require_once __DIR__ . '/includes/helpers.php';
} else {
    // Display an admin notice if the autoloader is missing.
    add_action('admin_notices', function () {
        echo '<div class="error"><p>' . esc_html__('Hypermedia API: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'api-for-htmx') . '</p></div>';
    });

    return;
}

// The logic from the original api-for-htmx.php is now here.
// This ensures that no matter how the plugin is loaded, this code runs only once.

// Get this instance's version and real path (resolving symlinks)
$plugin_file_path = __DIR__ . '/api-for-htmx.php';
$current_hmapi_instance_version = '0.0.0';
$current_hmapi_instance_path = null;

// Check if we're running as a plugin (api-for-htmx.php exists) or as a library
if (file_exists($plugin_file_path)) {
    // Plugin mode: read version from the main plugin file
    $hmapi_plugin_data = get_file_data($plugin_file_path, ['Version' => 'Version'], false);
    $current_hmapi_instance_version = $hmapi_plugin_data['Version'] ?? '0.0.0';
    $current_hmapi_instance_path = realpath($plugin_file_path);
} else {
    // Library mode: try to get version from composer.json or use a fallback
    $composer_json_path = __DIR__ . '/composer.json';
    if (file_exists($composer_json_path)) {
        $composer_data = json_decode(file_get_contents($composer_json_path), true);
        $current_hmapi_instance_version = $composer_data['version'] ?? '0.0.0';
    }
    // Use bootstrap.php path as fallback for library mode
    $current_hmapi_instance_path = realpath(__FILE__);
}

// Ensure we have a valid path
if ($current_hmapi_instance_path === false) {
    $current_hmapi_instance_path = __FILE__;
}

// Register this instance as a candidate
if (!isset($GLOBALS['hmapi_api_candidates']) || !is_array($GLOBALS['hmapi_api_candidates'])) {
    $GLOBALS['hmapi_api_candidates'] = [];
}

// Use path as key to prevent duplicates
$GLOBALS['hmapi_api_candidates'][$current_hmapi_instance_path] = [
    'version' => $current_hmapi_instance_version,
    'path'    => $current_hmapi_instance_path,
    'init_function' => 'hmapi_run_initialization_logic',
];

// Hook to decide and run the winner. This action should only be added once.
if (!has_action('plugins_loaded', 'hmapi_select_and_load_latest')) {
    add_action('plugins_loaded', 'hmapi_select_and_load_latest', 0);
}

if (!function_exists('hmapi_run_initialization_logic')) {
    function hmapi_run_initialization_logic(string $plugin_file_path, string $plugin_version): void
    {
        define('HMAPI_INSTANCE_LOADED', true);
        define('HMAPI_LOADED_VERSION', $plugin_version);
        define('HMAPI_INSTANCE_LOADED_PATH', $plugin_file_path);
        define('HMAPI_VERSION', $plugin_version);

        // Determine if we're in library mode vs plugin mode
        $is_library_mode = !str_ends_with($plugin_file_path, 'api-for-htmx.php');

        if ($is_library_mode) {
            // Library mode: use the directory containing the bootstrap/plugin file
            $plugin_dir = dirname($plugin_file_path);
            define('HMAPI_ABSPATH', trailingslashit($plugin_dir));
            define('HMAPI_BASENAME', 'hypermedia-api-wordpress/bootstrap.php');
            define('HMAPI_PLUGIN_URL', ''); // Not applicable in library mode
            define('HMAPI_PLUGIN_FILE', $plugin_file_path);
        } else {
            // Plugin mode: use standard WordPress plugin functions
            define('HMAPI_ABSPATH', plugin_dir_path($plugin_file_path));
            define('HMAPI_BASENAME', plugin_basename($plugin_file_path));
            define('HMAPI_PLUGIN_URL', plugin_dir_url($plugin_file_path));
            define('HMAPI_PLUGIN_FILE', $plugin_file_path);
        }

        define('HMAPI_ENDPOINT', 'wp-html');
        define('HMAPI_LEGACY_ENDPOINT', 'wp-htmx');
        define('HMAPI_TEMPLATE_DIR', 'hypermedia');
        define('HMAPI_LEGACY_TEMPLATE_DIR', 'htmx-templates');
        define('HMAPI_TEMPLATE_EXT', '.hm.php');
        define('HMAPI_LEGACY_TEMPLATE_EXT', '.htmx.php');
        define('HMAPI_ENDPOINT_VERSION', 'v1');

        if ((defined('DOING_CRON') && DOING_CRON === true) ||
             (defined('DOING_AJAX') && DOING_AJAX === true) ||
             (defined('REST_REQUEST') && REST_REQUEST === true) ||
             (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST === true) ||
             (defined('WP_CLI') && WP_CLI === true)) {
            return;
        }

        // Only register activation/deactivation hooks in plugin mode
        if (!$is_library_mode) {
            register_activation_hook($plugin_file_path, ['HMApi\Admin\Activation', 'activate']);
            register_deactivation_hook($plugin_file_path, ['HMApi\Admin\Activation', 'deactivate']);
        }

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
            
            // Initialize the blocks system
            if (class_exists('HMApi\Blocks\Registry')) {
                $blocksRegistry = HMApi\Blocks\Registry::getInstance();
                $blocksRegistry->init();
                
                // Initialize the blocks REST API
                if (class_exists('HMApi\Blocks\RestApi')) {
                    $blocksRestApi = new HMApi\Blocks\RestApi();
                    $blocksRestApi->init();
                }
            }
            
            // Demo blocks are automatically discovered by the Registry auto-discovery system
        }
    }
}

if (!function_exists('hmapi_select_and_load_latest')) {
    function hmapi_select_and_load_latest(): void
    {
        if (empty($GLOBALS['hmapi_api_candidates']) || !is_array($GLOBALS['hmapi_api_candidates'])) {
            return;
        }

        $candidates = $GLOBALS['hmapi_api_candidates'];
        uasort($candidates, fn ($a, $b) => version_compare($b['version'], $a['version']));
        $winner = reset($candidates);

        if ($winner && isset($winner['path'], $winner['version'], $winner['init_function']) && function_exists($winner['init_function'])) {
            call_user_func($winner['init_function'], $winner['path'], $winner['version']);
        }

        unset($GLOBALS['hmapi_api_candidates']);
    }
}
