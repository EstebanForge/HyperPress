<?php

declare(strict_types=1);

/**
 * HyperPress plugin adapter bootstrap.
 *
 * This wrapper keeps WordPress plugin concerns thin while loading HyperPress Core
 * from Composer-distributed libraries.
 */

if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

if (defined('HYPERPRESS_PLUGIN_ADAPTER_BOOTSTRAPPED')) {
    return;
}
define('HYPERPRESS_PLUGIN_ADAPTER_BOOTSTRAPPED', true);

if (!function_exists('hyperpress_adapter_require_once_path')) {
    /**
     * Require a file only once across this request, using normalized absolute paths.
     */
    function hyperpress_adapter_require_once_path(string $path): bool
    {
        static $loaded = [];
        $resolved = realpath($path) ?: $path;
        $normalized = str_replace('\\', '/', $resolved);
        if (isset($loaded[$normalized])) {
            return true;
        }
        if (!file_exists($resolved)) {
            return false;
        }
        require_once $resolved;
        $loaded[$normalized] = true;

        return true;
    }
}

$adapter_main_file = file_exists(__DIR__ . '/hyperpress.php')
    ? __DIR__ . '/hyperpress.php'
    : __DIR__ . '/api-for-htmx.php';

// Load Jetpack packages autoloader first when present (required by jetpack-autoloader).
if (function_exists('wp_normalize_path')) {
    $autoload_packages_candidates = [
        __DIR__ . '/vendor/autoload_packages.php',
        dirname(__DIR__) . '/HyperPress-Core/vendor/autoload_packages.php',
    ];
    foreach ($autoload_packages_candidates as $autoload_packages) {
        if (hyperpress_adapter_require_once_path($autoload_packages)) {
            break;
        }
    }
}

// Load Composer autoloader from plugin package first, local monorepo fallback second.
$autoload_candidates = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/HyperPress-Core/vendor/autoload.php',
];
foreach ($autoload_candidates as $autoload) {
    if (hyperpress_adapter_require_once_path($autoload)) {
        break;
    }
}

// Load HyperPress Core bootstrap from packaged vendor or local development folder.
$core_bootstrap_candidates = [
    __DIR__ . '/vendor/estebanforge/hyperpress-core/bootstrap.php',
    dirname(__DIR__) . '/HyperPress-Core/bootstrap.php',
];
$core_loaded = false;
foreach ($core_bootstrap_candidates as $core_bootstrap) {
    if (hyperpress_adapter_require_once_path($core_bootstrap)) {
        $core_loaded = true;
        break;
    }
}

if (!$core_loaded) {
    if (function_exists('add_action')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="error"><p>' . esc_html__('HyperPress: HyperPress Core not found. Please run "composer install" inside the plugin folder.', 'api-for-htmx') . '</p></div>';
        });
    }

    return;
}

// Ensure latest-instance selectors are registered in plugin context.
if (
    function_exists('hyperpress_select_and_load_latest')
    && function_exists('has_action')
    && function_exists('add_action')
    && !has_action('after_setup_theme', 'hyperpress_select_and_load_latest')
) {
    add_action('after_setup_theme', 'hyperpress_select_and_load_latest', 0);
}
if (
    function_exists('hyperfields_select_and_load_latest')
    && function_exists('has_action')
    && function_exists('add_action')
    && !has_action('after_setup_theme', 'hyperfields_select_and_load_latest')
) {
    add_action('after_setup_theme', 'hyperfields_select_and_load_latest', 0);
}
if (
    function_exists('hyperblocks_select_and_load_latest')
    && function_exists('has_action')
    && function_exists('add_action')
    && !has_action('after_setup_theme', 'hyperblocks_select_and_load_latest')
) {
    add_action('after_setup_theme', 'hyperblocks_select_and_load_latest', 0);
}

// Register HyperFields Export/Import UI under Tools.
if (class_exists('HyperFields\\HyperFields') && function_exists('add_action')) {
    add_action('admin_menu', static function (): void {
        \HyperFields\HyperFields::registerDataToolsPage(
            parentSlug: 'tools.php',
            pageSlug: 'hyperpress-data-tools',
            options: [
                'hyperpress_options' => __('HyperPress Settings', 'hyperpress'),
            ],
            allowedImportOptions: ['hyperpress_options'],
            prefix: '',
            title: __('HyperPress Data Tools', 'hyperpress'),
            capability: 'manage_options'
        );
    });
}

// Plugin lifecycle hooks remain in the adapter layer.
if (
    class_exists('HyperPress\\Admin\\Activation')
    && function_exists('register_activation_hook')
    && function_exists('register_deactivation_hook')
) {
    register_activation_hook($adapter_main_file, ['HyperPress\\Admin\\Activation', 'activate']);
    register_deactivation_hook($adapter_main_file, ['HyperPress\\Admin\\Activation', 'deactivate']);
}

// Enrich the About page system-info table with vendored library versions.
if (function_exists('add_filter')) {
    add_filter('hyperpress/about/system_info', static function (array $info): array {
        $library_versions = [];

        if (defined('HYPERFIELDS_VERSION')) {
            $library_versions[__('HyperFields Library', 'api-for-htmx')] = HYPERFIELDS_VERSION;
        }
        if (defined('HYPERBLOCKS_VERSION')) {
            $library_versions[__('HyperBlocks Library', 'api-for-htmx')] = HYPERBLOCKS_VERSION;
        }
        if (defined('HYPERPRESS_VERSION')) {
            $library_versions[__('HyperPress Core', 'api-for-htmx')] = HYPERPRESS_VERSION;
        }

        if (empty($library_versions)) {
            return $info;
        }

        // Insert library versions after the Plugin Version row.
        $insert_after = __('Plugin Version', 'api-for-htmx');
        $result = [];
        $inserted = false;
        foreach ($info as $key => $value) {
            $result[$key] = $value;
            if (!$inserted && $key === $insert_after) {
                foreach ($library_versions as $lib_key => $lib_value) {
                    $result[$lib_key] = $lib_value;
                }
                $inserted = true;
            }
        }

        // Fallback: append at the end if Plugin Version key was not found.
        if (!$inserted) {
            $result = array_merge($result, $library_versions);
        }

        return $result;
    });
}
