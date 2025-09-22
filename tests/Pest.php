<?php

/**
 * Pest Configuration for HyperPress WordPress Plugin with WP_Mock
 */

use HyperPress\Tests\WordPressTestCase;

/**
 * Bootstrap WP_Mock for WordPress testing
 */
beforeEach(function () {
    // WP_Mock handles its own setup in the TestCase
    // Additional setup can be done here if needed
});

/**
 * Clean up after each test
 */
afterEach(function () {
    // WP_Mock handles its own cleanup in the TestCase
    Mockery::close();
});

/**
 * Define the testing environment
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

if (!defined('WP_TESTS_DIR')) {
    define('WP_TESTS_DIR', __DIR__ . '/../vendor/wordpress/wordpress/tests/phpunit/');
}

if (!defined('PLUGIN_DIR')) {
    define('PLUGIN_DIR', __DIR__ . '/../');
}

if (!defined('PLUGIN_FILE')) {
    define('PLUGIN_FILE', PLUGIN_DIR . 'api-for-htmx.php');
}

// Define WordPress constants that might be expected
if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', PLUGIN_DIR);
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', dirname(PLUGIN_DIR));
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}