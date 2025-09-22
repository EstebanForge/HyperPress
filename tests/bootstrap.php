<?php

/**
 * WordPress bootstrap functions for Pest tests with WP_Mock
 */

namespace HyperPress\tests;

/**
 * Bootstrap WordPress environment for testing
 */
function bootstrap_wp(): void
{
    // Define WordPress constants if not already defined
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/../vendor/wordpress/wordpress/');
    }

    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }

    if (!defined('WP_DEBUG_LOG')) {
        define('WP_DEBUG_LOG', true);
    }

    if (!defined('WP_DEBUG_DISPLAY')) {
        define('WP_DEBUG_DISPLAY', false);
    }

    if (!defined('SCRIPT_DEBUG')) {
        define('SCRIPT_DEBUG', true);
    }

    if (!defined('WP_TESTS_DOMAIN')) {
        define('WP_TESTS_DOMAIN', 'example.org');
    }

    if (!defined('WP_TESTS_EMAIL')) {
        define('WP_TESTS_EMAIL', 'admin@example.org');
    }

    if (!defined('WP_TESTS_TITLE')) {
        define('WP_TESTS_TITLE', 'Test Blog');
    }

    // WP_Mock will be set up by the TestCase
    // Additional setup can be done here if needed
}