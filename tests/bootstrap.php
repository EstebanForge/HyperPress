<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap for HyperPress
 *
 * Sets up the testing environment and loads necessary dependencies.
 */

// Define testing mode constant before any autoloading
define('HYPERPRESS_TESTING_MODE', true);

// Define ABSPATH early for autoloaded files that check it
if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/wordpress/');
}

// Define WordPress constants early
if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins');
}
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

// Mock critical WordPress functions needed before Brain\Monkey loads
// These are needed by bootstrap files loaded via composer autoload
if (!function_exists('get_file_data')) {
    function get_file_data($file, $default_headers, $context = '') {
        return [];
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        return true;
    }
}

if (!function_exists('has_action')) {
    function has_action($hook, $callback = false) {
        return false;
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value = null, ...$args) {
        return $value;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        $parsed = is_array($args) ? $args : [];
        $defaults = is_array($defaults) ? $defaults : [];
        return array_merge($defaults, $parsed);
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle) {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle) {
        return true;
    }
}

if (!function_exists('wp_register_script')) {
    function wp_register_script($handle) {
        return true;
    }
}

if (!function_exists('wp_register_style')) {
    function wp_register_style($handle) {
        return true;
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        $base = 'http://localhost/wp-content/plugins/api-for-htmx';
        if ($path === '' || $path === null) {
            return $base;
        }
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/api-for-htmx/';
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = '') {
        $base = 'http://localhost/wp-json/';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite() {
        return false;
    }
}

if (!function_exists('dbDelta')) {
    function dbDelta($queries) {
        return [];
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true;
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content) {
        return $content;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text) {
        $text = preg_replace('#<script[^>]*>.*?</script>#si', '', (string) $text);
        $text = strip_tags($text);
        return trim($text);
    }
}

// Load Composer autoloader
$composer = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composer)) {
    echo "Composer autoloader not found. Please run 'composer install' in the plugin root.\n";
    exit(1);
}
require_once $composer;

// Load BrainMonkey
Brain\Monkey\setUp();

// Mock WordPress functions with BrainMonkey when needed

// Mock plugin helper functions
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        return true;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        return true;
    }
}

// HyperPress specific constants
if (!defined('HYPERPRESS_VERSION')) {
    define('HYPERPRESS_VERSION', '3.0.3');
}

if (!defined('HYPERPRESS_DIR')) {
    define('HYPERPRESS_DIR', dirname(__DIR__));
}

if (!defined('HYPERPRESS_URL')) {
    define('HYPERPRESS_URL', 'http://localhost/wp-content/plugins/api-for-htmx');
}

if (!defined('HYPERPRESS_ASSETS_URL')) {
    define('HYPERPRESS_ASSETS_URL', HYPERPRESS_URL . '/assets/');
}

// Setup for teardown
register_shutdown_function(function () {
    Brain\Monkey\tearDown();
});
