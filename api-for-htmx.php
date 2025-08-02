<?php

/**
 * Plugin Name: HyperPress: Modern Hypermedia for WordPress
 * Plugin URI: https://github.com/EstebanForge/HyperPress
 * Description: Adds API endpoints and integration for hypermedia libraries like HTMX, AlpineJS, and Datastar.
 * Version: 2.0.7
 * Author: Esteban Cuevas
 * Author URI: https://actitud.xyz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: api-for-htmx
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 6.9
 * Requires PHP: 8.2.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Load the shared bootstrap file.
require_once __DIR__ . '/bootstrap.php';
