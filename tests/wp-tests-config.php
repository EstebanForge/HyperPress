<?php
// WordPress PHPUnit test configuration for HyperPress plugin.
// This file is loaded via vendor/wp-phpunit/wp-phpunit/wp-tests-config.php based on WP_PHPUNIT__TESTS_CONFIG.

// Ensure ABSPATH points to this repository's WordPress core installation (src/wp-core).
if (!defined('ABSPATH')) {
    $pluginDir = realpath(__DIR__ . '/..');
    $projectRoot = realpath($pluginDir . '/../../..');
    define('ABSPATH', $projectRoot . '/src/wp-core/');
}

// Force SQLite engine and ensure MySQL is disabled for tests.
if (!defined('DB_ENGINE')) {
    define('DB_ENGINE', 'sqlite');
}
if (!defined('USE_MYSQL')) {
    define('USE_MYSQL', false);
}

// Make sure WordPress knows where to find wp-content and plugins.
// Use vendor-managed wp-phpunit WordPress content directory (keeps test files under vendor/).
if (!defined('WP_CONTENT_DIR')) {
    $pluginDir = realpath(__DIR__ . '/..');
    define('WP_CONTENT_DIR', $pluginDir . '/vendor/wp-phpunit/wp-phpunit/wordpress/wp-content');
}
if (!defined('WP_PLUGIN_DIR')) {
    $pluginDir = realpath(__DIR__ . '/..');
    $projectRoot = realpath($pluginDir . '/../../..');
    define('WP_PLUGIN_DIR', $projectRoot . '/src/plugins');
}

// Database settings for tests. When SQLite drop-in is present, these are
// used by the drop-in; values other than DB_NAME are mostly ignored.
if (!defined('DB_NAME')) {
    define('DB_NAME', 'wp_tests');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', '');
}
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8');
}
if (!defined('DB_COLLATE')) {
    define('DB_COLLATE', '');
}

// For aaemnnosttv/wp-sqlite-db, optionally configure database file path.
if (!defined('DB_DIR')) {
    define('DB_DIR', WP_CONTENT_DIR . '/database');
}
if (!defined('DB_FILE')) {
    define('DB_FILE', 'tests.sqlite');
}

// Table prefix for tests
if (!isset($table_prefix)) {
    $table_prefix = 'wp_';
}

// Site configuration
if (!defined('WP_TESTS_DOMAIN')) {
    define('WP_TESTS_DOMAIN', getenv('WP_TESTS_DOMAIN') ?: 'example.org');
}
if (!defined('WP_TESTS_EMAIL')) {
    define('WP_TESTS_EMAIL', getenv('WP_TESTS_EMAIL') ?: 'admin@example.org');
}
if (!defined('WP_TESTS_TITLE')) {
    define('WP_TESTS_TITLE', getenv('WP_TESTS_TITLE') ?: 'WordPress Test Site');
}

// PHP binary used by the test suite for subprocesses
if (!defined('WP_PHP_BINARY')) {
    define('WP_PHP_BINARY', getenv('WP_PHP_BINARY') ?: 'php');
}

// DB settings (SQLite drop-in will handle the actual connection)
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'wordpress_tests');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
}
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8');
}
if (!defined('DB_COLLATE')) {
    define('DB_COLLATE', '');
}

// Prevent external HTTP requests during tests
if (!defined('WP_HTTP_BLOCK_EXTERNAL')) {
    define('WP_HTTP_BLOCK_EXTERNAL', true);
}
