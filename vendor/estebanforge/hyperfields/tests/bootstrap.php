<?php

declare(strict_types=1);

// Define testing mode constant before any autoloading
define('HYPERFIELDS_TESTING_MODE', true);

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

// Mock WP_Error class
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = [];
        public $error_data = [];

        public function __construct($code = '', $message = '', $data = '') {
            if (empty($code)) {
                return;
            }
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }
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

// Mock WordPress functions with BrainMonkey
Brain\Monkey\Functions\when('apply_filters')->returnArg();
Brain\Monkey\Functions\when('__')->returnArg();
Brain\Monkey\Functions\when('_e')->justReturn();
Brain\Monkey\Functions\when('esc_html')->returnArg();
Brain\Monkey\Functions\when('esc_attr')->returnArg();
Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();
Brain\Monkey\Functions\when('wp_nonce_field')->justReturn();
Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
Brain\Monkey\Functions\when('update_post_meta')->justReturn(true);
Brain\Monkey\Functions\when('update_user_meta')->justReturn(true);
Brain\Monkey\Functions\when('update_term_meta')->justReturn(true);
Brain\Monkey\Functions\when('get_post_type')->justReturn('post');
Brain\Monkey\Functions\when('get_term')->alias(function($term_id, $taxonomy = '') {
    if ($term_id instanceof \stdClass) {
        return $term_id;
    }
    return (object) ['term_id' => $term_id, 'taxonomy' => $taxonomy, 'slug' => 'test-term'];
});

// Setup for teardown
register_shutdown_function(function () {
    Brain\Monkey\tearDown();
});