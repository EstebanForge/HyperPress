<?php

/**
 * Plugin Name: HyperPress: Modern Hypermedia for WordPress
 * Plugin URI: https://github.com/EstebanForge/HyperPress
 * Description: Supercharge WordPress with the power of hypermedia. Use HTMX, Alpine Ajax, and Datastar to create rich, interactive blocks and pages—all with the simplicity of PHP.
 * Version: 3.2.4
 * Author: Esteban Cuevas
 * Author URI: https://actitud.xyz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hyperpress
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 6.9
 * Requires PHP: 8.2
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

// Version of the WordPress plugin adapter. Always reflects the installed plugin version,
// regardless of which HyperPress-Core library version is active underneath.
if (!defined('HYPERPRESS_PLUGIN_VERSION')) {
    if (function_exists('get_file_data')) {
        $hyperpress_file_data = get_file_data(__FILE__, ['Version' => 'Version'], false);
        define('HYPERPRESS_PLUGIN_VERSION', $hyperpress_file_data['Version'] ?? '0.0.0');
    } else {
        define('HYPERPRESS_PLUGIN_VERSION', '0.0.0');
    }
    unset($hyperpress_file_data);
}

// Load Jetpack packages autoloader first when present.
if (function_exists('wp_normalize_path') && file_exists(__DIR__ . '/vendor/autoload_packages.php')) {
    require_once __DIR__ . '/vendor/autoload_packages.php';
}

// Load the shared bootstrap file.
require_once __DIR__ . '/bootstrap.php';

// Ensure the initialization hook is registered.
// This handles the case where bootstrap.php was loaded early (e.g., via Composer)
// and couldn't register the hook because WordPress wasn't ready.
if (function_exists('hyperpress_select_and_load_latest') && !has_action('after_setup_theme', 'hyperpress_select_and_load_latest')) {
    add_action('after_setup_theme', 'hyperpress_select_and_load_latest', 0);
}

// Ensure HyperFields is also initialized if it's being used as a library
if (function_exists('hyperfields_select_and_load_latest') && !has_action('after_setup_theme', 'hyperfields_select_and_load_latest')) {
    add_action('after_setup_theme', 'hyperfields_select_and_load_latest', 0);
}

// Ensure HyperBlocks is also initialized if it's being used as a library
if (function_exists('hyperblocks_select_and_load_latest') && !has_action('after_setup_theme', 'hyperblocks_select_and_load_latest')) {
    add_action('after_setup_theme', 'hyperblocks_select_and_load_latest', 0);
}
