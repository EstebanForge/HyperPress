<?php

/**
 * Plugin Name: HyperPress: Modern Hypermedia for WordPress
 * Plugin URI: https://github.com/EstebanForge/HyperPress
 * Description: Supercharge WordPress with the power of hypermedia. Use HTMX, Alpine Ajax, and Datastar to create rich, interactive blocks and pages—all with the simplicity of PHP.
 * Version: 3.0.5
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

