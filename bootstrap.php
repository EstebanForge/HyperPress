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

// Exit if accessed directly (but allow test environment to proceed).
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

// Use a unique constant to ensure this bootstrap logic runs only once.
if (defined('HYPERPRESS_BOOTSTRAP_LOADED')) {
    return;
}

define('HYPERPRESS_BOOTSTRAP_LOADED', true);

// Optional dev override: load local HyperFields/HyperBlocks copies before Composer.
$use_local_libs = defined('HYPERPRESS_USE_LOCAL_LIBS') ? (bool) HYPERPRESS_USE_LOCAL_LIBS : (getenv('HYPERPRESS_USE_LOCAL_LIBS') === '1');
if ($use_local_libs) {
    $local_libs = [
        dirname(__DIR__) . '/HyperFields',
        dirname(__DIR__) . '/HyperBlocks',
    ];
    foreach ($local_libs as $lib_path) {
        $lib_path = realpath($lib_path) ?: $lib_path;
        $autoload = $lib_path . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        $bootstrap = $lib_path . '/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }
}

// Composer autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Display an admin notice if no autoloader is found, but continue so tests can register hooks/candidates.
    add_action('admin_notices', function () {
        echo '<div class="error"><p>' . esc_html__('HyperPress: Composer autoloader not found. Please run "composer install" inside the plugin folder.', 'api-for-htmx') . '</p></div>';
    });
}

// Note: Do not initialize Blocks Registry or REST API here to avoid duplicate hooks.
// They are initialized later inside hyperpress_run_initialization_logic() once per instance.

// The logic from the original api-for-htmx.php is now here.
// This ensures that no matter how the plugin is loaded, this code runs only once.

// Get this instance's version and real path (resolving symlinks)
// Support both legacy 'api-for-htmx.php' and current 'hyperpress.php' entry points
$plugin_file_candidates = [
    __DIR__ . '/hyperpress.php',
    __DIR__ . '/api-for-htmx.php',
];
$plugin_file_path = null;
foreach ($plugin_file_candidates as $candidate) {
    if (file_exists($candidate)) {
        $plugin_file_path = $candidate;
        break;
    }
}
$current_hyperpress_instance_version = '0.0.0';
$current_hyperpress_instance_path = null;

// Check if we're running as a plugin (one of the entry files exists) or as a library
if ($plugin_file_path && file_exists($plugin_file_path)) {
    // Plugin mode: read version from the main plugin file
    if (function_exists('get_file_data')) {
        $hyperpress_plugin_data = get_file_data($plugin_file_path, ['Version' => 'Version'], false);
        $current_hyperpress_instance_version = $hyperpress_plugin_data['Version'] ?? '0.0.0';
    } else {
        // Fallback: Try composer.json first
        $composer_json_path = __DIR__ . '/composer.json';
        if (file_exists($composer_json_path)) {
            $composer_data = json_decode(file_get_contents($composer_json_path), true);
            $current_hyperpress_instance_version = $composer_data['version'] ?? '0.0.0';
        } else {
             // Fallback: Regex parse the plugin file
            $plugin_content = file_get_contents($plugin_file_path, false, null, 0, 8192);
            if ($plugin_content && preg_match('/^[ \t\/*#@]*Version:\s*(.*)$/mi', $plugin_content, $matches)) {
                $current_hyperpress_instance_version = trim($matches[1]);
            } else {
                $current_hyperpress_instance_version = '0.0.0';
            }
        }
    }
    $current_hyperpress_instance_path = realpath($plugin_file_path);
} else {
    // Library mode: try to get version from composer.json or use a fallback
    $composer_json_path = __DIR__ . '/composer.json';
    if (file_exists($composer_json_path)) {
        $composer_data = json_decode(file_get_contents($composer_json_path), true);
        $current_hyperpress_instance_version = $composer_data['version'] ?? '0.0.0';
    }
    // Use bootstrap.php path as fallback for library mode
    $current_hyperpress_instance_path = realpath(__FILE__);
}

// Ensure we have a valid path
if ($current_hyperpress_instance_path === false) {
    $current_hyperpress_instance_path = __FILE__;
}

// Register this instance as a candidate
if (!isset($GLOBALS['hyperpress_api_candidates']) || !is_array($GLOBALS['hyperpress_api_candidates'])) {
    $GLOBALS['hyperpress_api_candidates'] = [];
}

// Use path as key to prevent duplicates
$GLOBALS['hyperpress_api_candidates'][$current_hyperpress_instance_path] = [
    'version' => $current_hyperpress_instance_version,
    'path'    => $current_hyperpress_instance_path,
    'init_function' => 'hyperpress_run_initialization_logic',
];

// Use 'after_setup_theme' to ensure this runs after the theme is loaded.
if (function_exists('has_action') && function_exists('add_action')) {
    if (!has_action('after_setup_theme', 'hyperpress_select_and_load_latest')) {
        add_action('after_setup_theme', 'hyperpress_select_and_load_latest', 0);
    }
}

if (!function_exists('hyperpress_run_initialization_logic')) {
    function hyperpress_run_initialization_logic(string $plugin_file_path, string $plugin_version): void
    {
        // Ensure this logic runs only once.
        if (defined('HYPERPRESS_INSTANCE_LOADED')) {
            return;
        }
        define('HYPERPRESS_INSTANCE_LOADED', true);
        define('HYPERPRESS_LOADED_VERSION', $plugin_version);
        define('HYPERPRESS_INSTANCE_LOADED_PATH', $plugin_file_path);
        if (!defined('HYPERPRESS_VERSION')) {
            define('HYPERPRESS_VERSION', $plugin_version);
        }

        // Determine if we're in library mode vs plugin mode (support legacy entry file)
        $basename = $plugin_file_path ? basename($plugin_file_path) : '';
        $is_library_mode = !in_array($basename, ['hyperpress.php', 'api-for-htmx.php'], true);

        if ($is_library_mode) {
            // Library mode: use the directory containing the bootstrap/plugin file
            $plugin_dir = dirname($plugin_file_path);
            define('HYPERPRESS_ABSPATH', trailingslashit($plugin_dir));
            define('HYPERPRESS_BASENAME', 'hyperpress/bootstrap.php');
            if (!defined('HYPERPRESS_PLUGIN_URL')) {
                define('HYPERPRESS_PLUGIN_URL', ''); // Not applicable in library mode
            }
            define('HYPERPRESS_PLUGIN_FILE', $plugin_file_path);
        } else {
            // Plugin mode: use standard WordPress plugin functions
            define('HYPERPRESS_ABSPATH', plugin_dir_path($plugin_file_path));
            define('HYPERPRESS_BASENAME', plugin_basename($plugin_file_path));
            if (!defined('HYPERPRESS_PLUGIN_URL')) {
                define('HYPERPRESS_PLUGIN_URL', plugin_dir_url($plugin_file_path));
            }
            define('HYPERPRESS_PLUGIN_FILE', $plugin_file_path);
        }

        define('HYPERPRESS_ENDPOINT', 'wp-html');
        define('HYPERPRESS_LEGACY_ENDPOINT', 'wp-htmx');
        define('HYPERPRESS_TEMPLATE_DIR', 'hypermedia');
        define('HYPERPRESS_LEGACY_TEMPLATE_DIR', 'htmx-templates');
        define('HYPERPRESS_TEMPLATE_EXT', '.hp.php,.hm.php,.hb.php');
        define('HYPERPRESS_LEGACY_TEMPLATE_EXT', '.htmx.php,.hmedia.php');
        define('HYPERPRESS_ENDPOINT_VERSION', 'v1');

        // Load helpers and compatibility layers after constants are defined.
        require_once HYPERPRESS_ABSPATH . 'includes/helpers.php';
        require_once HYPERPRESS_ABSPATH . 'includes/backward-compatibility.php';

        // Optional: Compact input to mitigate max_input_vars on complex option pages
        if (!defined('HYPERPRESS_COMPACT_INPUT')) {
            define('HYPERPRESS_COMPACT_INPUT', false);
        }
        if (!defined('HYPERPRESS_COMPACT_INPUT_KEY')) {
            define('HYPERPRESS_COMPACT_INPUT_KEY', 'hyperpress_compact_input');
        }

        if ((defined('DOING_CRON') && DOING_CRON === true)
            || (defined('DOING_AJAX') && DOING_AJAX === true)
            || (defined('REST_REQUEST') && REST_REQUEST === true)
            || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST === true)
            || (defined('WP_CLI') && WP_CLI === true)
        ) {
            return;
        }

        // Only register activation/deactivation hooks in plugin mode
        if (!$is_library_mode) {
            register_activation_hook($plugin_file_path, ['HyperPress\Admin\Activation', 'activate']);
            register_deactivation_hook($plugin_file_path, ['HyperPress\Admin\Activation', 'deactivate']);
        }

        if (class_exists('HyperPress\Main')) {
            $router = new HyperPress\Router();
            $render = new HyperPress\Render();
            $config = new HyperPress\Config();
            $compatibility = new HyperPress\Compatibility();
            $theme_support = new HyperPress\Theme();
            $hyperpress_main = new HyperPress\Main(
                $router,
                $render,
                $config,
                $compatibility,
                $theme_support
            );
            $hyperpress_main->run();

            // Initialize the blocks system
            if (class_exists('HyperPress\Blocks\Registry')) {
                $blocksRegistry = HyperPress\Blocks\Registry::getInstance();
                $blocksRegistry->init();

                // Initialize the blocks REST API
                if (class_exists('HyperPress\Blocks\RestApi')) {
                    $blocksRestApi = HyperPress\Blocks\RestApi::getInstance();
                    $blocksRestApi->init();
                }
            }

            // Demo blocks are automatically discovered by the Registry auto-discovery system
        }
    }
}

if (!function_exists('hyperpress_select_and_load_latest')) {
    function hyperpress_select_and_load_latest(): void
    {
        if (empty($GLOBALS['hyperpress_api_candidates']) || !is_array($GLOBALS['hyperpress_api_candidates'])) {
            return;
        }

        $candidates = $GLOBALS['hyperpress_api_candidates'];
        uasort($candidates, fn ($a, $b) => version_compare($b['version'], $a['version']));
        $winner = reset($candidates);

        if ($winner && isset($winner['path'], $winner['version'], $winner['init_function']) && function_exists($winner['init_function'])) {
            call_user_func($winner['init_function'], $winner['path'], $winner['version']);
        }

        unset($GLOBALS['hyperpress_api_candidates']);
    }
}

// Test helper: re-register candidate and ensure selection hook exists, without relying on include semantics.
if (!function_exists('hyperpress_register_candidate_for_tests')) {
    function hyperpress_register_candidate_for_tests(): void
    {
        // Determine current instance path/version similarly to main bootstrap logic.
        $plugin_file_candidates = [
            __DIR__ . '/hyperpress.php',
            __DIR__ . '/api-for-htmx.php',
        ];
        $plugin_file_path = null;
        foreach ($plugin_file_candidates as $candidate) {
            if (file_exists($candidate)) { $plugin_file_path = $candidate; break; }
        }

        $current_version = '0.0.0';
        $current_path = null;
        if ($plugin_file_path && file_exists($plugin_file_path)) {
            $data = get_file_data($plugin_file_path, ['Version' => 'Version'], false);
            $current_version = $data['Version'] ?? '0.0.0';
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

        if (!isset($GLOBALS['hyperpress_api_candidates']) || !is_array($GLOBALS['hyperpress_api_candidates'])) {
            $GLOBALS['hyperpress_api_candidates'] = [];
        }
        $GLOBALS['hyperpress_api_candidates'][$current_path] = [
            'version' => $current_version,
            'path'    => $current_path,
            'init_function' => 'hyperpress_run_initialization_logic',
        ];

        if (!has_action('after_setup_theme', 'hyperpress_select_and_load_latest')) {
            add_action('after_setup_theme', 'hyperpress_select_and_load_latest', 0);
        }
    }
}
