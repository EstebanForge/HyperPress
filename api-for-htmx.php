<?php

/**
 * Plugin Name: HyperPress: Modern Hypermedia for WordPress
 * Plugin URI: https://github.com/EstebanForge/HyperPress
 * Description: Supercharge WordPress with the power of hypermedia. Use HTMX, Alpine Ajax, and Datastar to create rich, interactive blocks and pages—all with the simplicity of PHP.
 * Version: 3.6.0
 * Author: Esteban Cuevas
 * Author URI: https://actitud.xyz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: api-for-htmx
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 7.0
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

// HyperPress is the plugin adapter. HyperPress-Core ships its Settings page
// hidden by default — it is a library and must never inject admin UI into a
// consumer's WordPress install. As the plugin, opt in to showing that page.
add_filter('hyperpress/admin/show_menu', '__return_true');

// Tell the library which plugins.php row owns the Settings link. Required
// because the vendored library copy resolves to library mode (its entry file
// is not co-located), so Config::$basename is the sentinel and the library
// cannot infer this plugin's row on its own.
add_filter('hyperpress/admin/action_links_basename', static fn (): string => plugin_basename(__FILE__));

// Load the shared bootstrap file. It loads the Composer autoloader, which runs
// each bundled library's bootstrap (Composer autoload.files); the libraries
// self-initialize at after_setup_theme behind their own first-to-boot guard.
require_once __DIR__ . '/bootstrap.php';
