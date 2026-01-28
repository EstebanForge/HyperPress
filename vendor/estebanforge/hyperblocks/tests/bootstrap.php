<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 */

// Prevent direct file access.
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../');
}

// Mock WordPress functions for unit tests
$_tests_dir = __DIR__;

// Load Composer autoloader
$autoloader = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloader)) {
    echo "Composer autoloader not found. Run 'composer install'.\n";
    exit(1);
}

require_once $autoloader;

// Load mock functions
require_once __DIR__ . '/mocks/wp-mocks.php';

// Initialize Config for testing
\HyperBlocks\Config::reset();

// Define test constants
define('HYPERBLOCKS_PATH', __DIR__ . '/..');
define('WP_DEBUG', true);
define('WP_CONTENT_DIR', sys_get_temp_dir() . '/wp-content');

// Ensure Config is initialized
\HyperBlocks\Config::init();

// Register test block path
\HyperBlocks\Config::registerBlockPath(__DIR__ . '/../examples/blocks');
