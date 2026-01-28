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
        if (defined('HYPERBLOCKS_INSTANCE_LOADED')) {
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
            $plugin_url = function_exists('plugins_url')
                ? rtrim(plugins_url('', $bootstrap_file_path), '/\\') . '/'
                : '';
            define('HYPERBLOCKS_PLUGIN_URL', $plugin_url);
        }

        if (class_exists(\HyperBlocks\WordPress\Bootstrap::class) && function_exists('add_action')) {
            \HyperBlocks\WordPress\Bootstrap::init();
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

if (defined('HYPERBLOCKS_BOOTSTRAP_LOADED')) {
    return;
}

define('HYPERBLOCKS_BOOTSTRAP_LOADED', true);

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

if (function_exists('add_action')) {
    add_action('after_setup_theme', 'hyperblocks_select_and_load_latest', 0);
}
