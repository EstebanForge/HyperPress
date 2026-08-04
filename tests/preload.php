<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/wordpress/');
}

if (!defined('HYPERPRESS_TESTING_MODE')) {
    define('HYPERPRESS_TESTING_MODE', true);
}

if (!defined('HYPERFIELDS_TESTING_MODE')) {
    define('HYPERFIELDS_TESTING_MODE', true);
}

if (!defined('HYPERBLOCKS_TESTING_MODE')) {
    define('HYPERBLOCKS_TESTING_MODE', true);
}

/*
 * Recorder stubs for the plugin lifecycle hooks. Brain Monkey does not define
 * register_activation_hook / register_deactivation_hook, and the adapter gates
 * the lifecycle block on function_exists(), so without these the wiring would
 * be skipped. They record into a global the wiring tests assert on. Defined
 * here (auto-prepended) so they exist before the adapter is ever loaded.
 */
$GLOBALS['__hp_lifecycle_hooks'] = [];

if (!function_exists('__return_true')) {
    function __return_true(): bool
    {
        return true;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, $callback): void
    {
        $GLOBALS['__hp_lifecycle_hooks']['activate'] = ['file' => $file, 'callback' => $callback];
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook(string $file, $callback): void
    {
        $GLOBALS['__hp_lifecycle_hooks']['deactivate'] = ['file' => $file, 'callback' => $callback];
    }
}
