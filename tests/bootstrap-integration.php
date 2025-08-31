<?php

declare(strict_types=1);

use Yoast\WPTestUtils\WPIntegration;

// Ensure we use SQLite for DB engine if supported by the drop-in.
if (!defined('DB_ENGINE')) {
    define('DB_ENGINE', 'sqlite');
}

// Compute project root and key paths.
$pluginDir = dirname(__DIR__);
$projectRoot = dirname($pluginDir, 3); // repo root: .../docker-wp-hyperpress

// Define WP_PLUGIN_DIR to point to our local src/plugins directory so WordPress
// can load plugins from the repository during integration tests.
if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', $projectRoot . '/src/plugins');
}

// Load Yoast WP Test Utils bootstrap helpers.
require_once $pluginDir . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

// Resolve the wp-phpunit WordPress content directory used by the test install.
// With wp-phpunit/wp-phpunit, WordPress is set up under the PLUGIN vendor dir.
$wpContentDir = $pluginDir . '/vendor/wp-phpunit/wp-phpunit/wordpress/wp-content';
// Repo core content directory (used when ABSPATH points to src/wp-core).
$repoContentDir = $projectRoot . '/src/wp-core/wp-content';

// Ensure the SQLite drop-in exists before bootstrapping WordPress, as db.php is loaded very early.
if (!is_dir($wpContentDir)) {
    @mkdir($wpContentDir, 0777, true);
}
if (!is_dir($repoContentDir)) {
    @mkdir($repoContentDir, 0777, true);
}

// Ensure database directories exist for SQLite drop-in default path.
$wpDbDir = $wpContentDir . '/database';
if (!is_dir($wpDbDir)) {
    @mkdir($wpDbDir, 0777, true);
}
$repoDbDir = $repoContentDir . '/database';
if (!is_dir($repoDbDir)) {
    @mkdir($repoDbDir, 0777, true);
}

$dropInTargets = [
    $wpContentDir . '/db.php',
    $repoContentDir . '/db.php',
];
// If either target is missing, copy to all targets.
$needCopy = false;
foreach ($dropInTargets as $t) {
    if (!file_exists($t)) { $needCopy = true; break; }
}
if ($needCopy) {
    // Preferred: vendor-provided drop-in via Composer package aaemnnosttv/wp-sqlite-db.
    $vendorSqliteDb = $pluginDir . '/vendor/aaemnnosttv/wp-sqlite-db/db.php';
    $straussSqliteDb = $pluginDir . '/wp-content/wp-sqlite-db/src/db.php';

    // Fallback: local plugin path if present in repo.
    $sqlitePluginDir = $projectRoot . '/src/plugins/sqlite-database-integration';
    $candidates = [
        $vendorSqliteDb,
        $straussSqliteDb,
        $sqlitePluginDir . '/db.php',
        $sqlitePluginDir . '/drop-in/db.php',
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            foreach ($dropInTargets as $t) {
                @copy($candidate, $t);
            }
            break;
        }
    }
}

// Tell the WP test bootstrap which plugins should be active.
$GLOBALS['wp_tests_options'] = [
    'active_plugins' => [
        'api-for-htmx/api-for-htmx.php',
    ],
];

// Finally, bootstrap WordPress (loads WP, Composer autoload, polyfills, and test cases).
WPIntegration\bootstrap_it();
