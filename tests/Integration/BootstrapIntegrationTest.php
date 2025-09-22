<?php

/**
 * Integration test for HyperPress plugin activation and deactivation
 * 
 * Tests the plugin lifecycle including:
 * - Plugin activation hooks
 * - Plugin deactivation hooks
 * - Library mode vs plugin mode behavior
 * - Version and path constants definition
 */

use HyperPress\Tests\WordPressTestCase;

uses(WordPressTestCase::class);

beforeEach(function () {
    $this->mockWordPressFunctions();
    
    // Reset global state
    unset($GLOBALS['hyperpress_api_candidates']);
    $GLOBALS['hyperpress_api_candidates'] = [];
    
    // Reset constants
    $constants_to_reset = [
        'HYPERPRESS_BOOTSTRAP_LOADED',
        'HYPERPRESS_INSTANCE_LOADED',
        'HYPERPRESS_LOADED_VERSION',
        'HYPERPRESS_VERSION',
        'HYPERPRESS_ABSPATH',
        'HYPERPRESS_BASENAME',
        'HYPERPRESS_PLUGIN_URL',
        'HYPERPRESS_PLUGIN_FILE',
        'HYPERPRESS_INSTANCE_LOADED_PATH',
        'HYPERPRESS_ENDPOINT',
        'HYPERPRESS_LEGACY_ENDPOINT',
        'HYPERPRESS_TEMPLATE_DIR',
        'HYPERPRESS_LEGACY_TEMPLATE_DIR',
        'HYPERPRESS_TEMPLATE_EXT',
        'HYPERPRESS_LEGACY_TEMPLATE_EXT',
        'HYPERPRESS_ENDPOINT_VERSION',
        'HYPERPRESS_COMPACT_INPUT',
        'HYPERPRESS_COMPACT_INPUT_KEY'
    ];
    
    foreach ($constants_to_reset as $constant) {
        if (defined($constant)) {
            remove_constant($constant);
        }
    }
});

afterEach(function () {
    // Clean up after each test
    unset($GLOBALS['hyperpress_api_candidates']);
    $GLOBALS['hyperpress_api_candidates'] = [];
});

test('plugin mode activation defines correct constants', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock WordPress functions for plugin mode
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('get_file_data')->return(['Version' => '3.0.1']);
    Brain\Monkey\Functions\when('realpath')->returnArg(0);
    Brain\Monkey\Functions\when('has_action')->justReturn(false);
    Brain\Monkey\Functions\when('plugin_dir_path')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_basename')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_dir_url')->returnArg(0);
    Brain\Monkey\Functions\when('trailingslashit')->returnArg(0);
    Brain\Monkey\Functions\when('register_activation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('register_deactivation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    
    // Mock class_exists to simulate loaded classes
    Brain\Monkey\Functions\when('class_exists')->justReturn(true);
    
    // Mock helper files existence
    Brain\Monkey\Functions\expect('require_once')->times(2);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Simulate plugin initialization
    hyperpress_run_initialization_logic('/path/to/plugin/api-for-htmx.php', '3.0.1');
    
    // Check plugin mode constants
    expect(defined('HYPERPRESS_INSTANCE_LOADED'))->toBeTrue();
    expect(defined('HYPERPRESS_LOADED_VERSION'))->toBeTrue();
    expect(defined('HYPERPRESS_VERSION'))->toBeTrue();
    expect(defined('HYPERPRESS_ABSPATH'))->toBeTrue();
    expect(defined('HYPERPRESS_BASENAME'))->toBeTrue();
    expect(defined('HYPERPRESS_PLUGIN_URL'))->toBeTrue();
    expect(defined('HYPERPRESS_PLUGIN_FILE'))->toBeTrue();
    
    expect(HYPERPRESS_LOADED_VERSION)->toBe('3.0.1');
    expect(HYPERPRESS_VERSION)->toBe('3.0.1');
    expect(HYPERPRESS_INSTANCE_LOADED_PATH)->toBe('/path/to/plugin/api-for-htmx.php');
});

test('library mode defines correct constants', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock WordPress functions for library mode
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('get_file_data')->return(['Version' => '3.0.1']);
    Brain\Monkey\Functions\when('realpath')->returnArg(0);
    Brain\Monkey\Functions\when('has_action')->justReturn(false);
    Brain\Monkey\Functions\when('trailingslashit')->returnArg(0);
    Brain\Monkey\Functions\when('dirname')->returnArg(0);
    Brain\Monkey\Functions\when('register_activation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('register_deactivation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('class_exists')->justReturn(true);
    
    // Mock helper files existence
    Brain\Monkey\Functions\expect('require_once')->times(2);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Simulate library initialization (path that doesn't match plugin files)
    hyperpress_run_initialization_logic('/path/to/library/bootstrap.php', '3.0.1');
    
    // Check library mode constants
    expect(defined('HYPERPRESS_INSTANCE_LOADED'))->toBeTrue();
    expect(defined('HYPERPRESS_LOADED_VERSION'))->toBeTrue();
    expect(defined('HYPERPRESS_VERSION'))->toBeTrue();
    expect(defined('HYPERPRESS_ABSPATH'))->toBeTrue();
    expect(defined('HYPERPRESS_BASENAME'))->toBeTrue();
    expect(defined('HYPERPRESS_PLUGIN_URL'))->toBeTrue();
    expect(defined('HYPERPRESS_PLUGIN_FILE'))->toBeTrue();
    
    expect(HYPERPRESS_LOADED_VERSION)->toBe('3.0.1');
    expect(HYPERPRESS_VERSION)->toBe('3.0.1');
    expect(HYPERPRESS_INSTANCE_LOADED_PATH)->toBe('/path/to/library/bootstrap.php');
    expect(HYPERPRESS_BASENAME)->toBe('hyperpress/bootstrap.php');
    expect(HYPERPRESS_PLUGIN_URL)->toBe(''); // Empty in library mode
});

test('plugin registers activation and deactivation hooks in plugin mode', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Track hook registrations
    $activation_hooks = [];
    $deactivation_hooks = [];
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('get_file_data')->return(['Version' => '3.0.1']);
    Brain\Monkey\Functions\when('realpath')->returnArg(0);
    Brain\Monkey\Functions\when('has_action')->justReturn(false);
    Brain\Monkey\Functions\when('plugin_dir_path')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_basename')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_dir_url')->returnArg(0);
    Brain\Monkey\Functions\when('trailingslashit')->returnArg(0);
    Brain\Monkey\Functions\when('class_exists')->justReturn(true);
    
    // Mock hook registration
    Brain\Monkey\Functions\when('register_activation_hook')->returnCallback(function ($file, $callback) use (&$activation_hooks) {
        $activation_hooks[] = ['file' => $file, 'callback' => $callback];
        return true;
    });
    
    Brain\Monkey\Functions\when('register_deactivation_hook')->returnCallback(function ($file, $callback) use (&$deactivation_hooks) {
        $deactivation_hooks[] = ['file' => $file, 'callback' => $callback];
        return true;
    });
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    
    // Mock helper files existence
    Brain\Monkey\Functions\expect('require_once')->times(2);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Simulate plugin initialization
    hyperpress_run_initialization_logic('/path/to/plugin/api-for-htmx.php', '3.0.1');
    
    // Check that hooks were registered
    expect(count($activation_hooks))->toBe(1);
    expect(count($deactivation_hooks))->toBe(1);
    
    expect($activation_hooks[0]['callback'])->toBe(['HyperPress\Admin\Activation', 'activate']);
    expect($deactivation_hooks[0]['callback'])->toBe(['HyperPress\Admin\Activation', 'deactivate']);
});

test('library mode does not register activation/deactivation hooks', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Track hook registrations
    $activation_hooks = [];
    $deactivation_hooks = [];
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('get_file_data')->return(['Version' => '3.0.1']);
    Brain\Monkey\Functions\when('realpath')->returnArg(0);
    Brain\Monkey\Functions\when('has_action')->justReturn(false);
    Brain\Monkey\Functions\when('trailingslashit')->returnArg(0);
    Brain\Monkey\Functions\when('dirname')->returnArg(0);
    Brain\Monkey\Functions\when('class_exists')->justReturn(true);
    
    // Mock hook registration
    Brain\Monkey\Functions\when('register_activation_hook')->returnCallback(function ($file, $callback) use (&$activation_hooks) {
        $activation_hooks[] = ['file' => $file, 'callback' => $callback];
        return true;
    });
    
    Brain\Monkey\Functions\when('register_deactivation_hook')->returnCallback(function ($file, $callback) use (&$deactivation_hooks) {
        $deactivation_hooks[] = ['file' => $file, 'callback' => $callback];
        return true;
    });
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    
    // Mock helper files existence
    Brain\Monkey\Functions\expect('require_once')->times(2);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Simulate library initialization
    hyperpress_run_initialization_logic('/path/to/library/bootstrap.php', '3.0.1');
    
    // Check that no hooks were registered in library mode
    expect(count($activation_hooks))->toBe(0);
    expect(count($deactivation_hooks))->toBe(0);
});

test('initialization defines endpoint and template constants', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('get_file_data')->return(['Version' => '3.0.1']);
    Brain\Monkey\Functions\when('realpath')->returnArg(0);
    Brain\Monkey\Functions\when('has_action')->justReturn(false);
    Brain\Monkey\Functions\when('plugin_dir_path')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_basename')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_dir_url')->returnArg(0);
    Brain\Monkey\Functions\when('trailingslashit')->returnArg(0);
    Brain\Monkey\Functions\when('register_activation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('register_deactivation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('class_exists')->justReturn(true);
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    
    // Mock helper files existence
    Brain\Monkey\Functions\expect('require_once')->times(2);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Simulate initialization
    hyperpress_run_initialization_logic('/path/to/plugin/api-for-htmx.php', '3.0.1');
    
    // Check endpoint constants
    expect(defined('HYPERPRESS_ENDPOINT'))->toBeTrue();
    expect(defined('HYPERPRESS_LEGACY_ENDPOINT'))->toBeTrue();
    expect(defined('HYPERPRESS_TEMPLATE_DIR'))->toBeTrue();
    expect(defined('HYPERPRESS_LEGACY_TEMPLATE_DIR'))->toBeTrue();
    expect(defined('HYPERPRESS_TEMPLATE_EXT'))->toBeTrue();
    expect(defined('HYPERPRESS_LEGACY_TEMPLATE_EXT'))->toBeTrue();
    expect(defined('HYPERPRESS_ENDPOINT_VERSION'))->toBeTrue();
    
    expect(HYPERPRESS_ENDPOINT)->toBe('wp-html');
    expect(HYPERPRESS_LEGACY_ENDPOINT)->toBe('wp-htmx');
    expect(HYPERPRESS_TEMPLATE_DIR)->toBe('hypermedia');
    expect(HYPERPRESS_LEGACY_TEMPLATE_DIR)->toBe('htmx-templates');
    expect(HYPERPRESS_TEMPLATE_EXT)->toBe('.hp.php,.hm.php,.hb.php');
    expect(HYPERPRESS_LEGACY_TEMPLATE_EXT)->toBe('.htmx.php,.hmedia.php');
    expect(HYPERPRESS_ENDPOINT_VERSION)->toBe('v1');
});

test('initialization skips in special WordPress modes', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Test each special mode
    $special_modes = [
        'DOING_CRON' => true,
        'DOING_AJAX' => true,
        'REST_REQUEST' => true,
        'XMLRPC_REQUEST' => true,
        'WP_CLI' => true,
    ];
    
    foreach ($special_modes as $constant => $value) {
        // Reset state
        unset($GLOBALS['hyperpress_api_candidates']);
        $GLOBALS['hyperpress_api_candidates'] = [];
        
        if (defined($constant)) {
            remove_constant($constant);
        }
        
        // Define the constant to test special mode
        define($constant, $value);
        
        Brain\Monkey\Functions\when('file_exists')->returnArg(1);
        Brain\Monkey\Functions\when('add_action')->justReturn(true);
        Brain\Monkey\Functions\when('get_file_data')->return(['Version' => '3.0.1']);
        Brain\Monkey\Functions\when('realpath')->returnArg(0);
        Brain\Monkey\Functions\when('has_action')->justReturn(false);
        Brain\Monkey\Functions\when('plugin_dir_path')->returnArg(0);
        Brain\Monkey\Functions\when('plugin_basename')->returnArg(0);
        Brain\Monkey\Functions\when('plugin_dir_url')->returnArg(0);
        Brain\Monkey\Functions\when('trailingslashit')->returnArg(0);
        
        // Include bootstrap
        require_once __DIR__ . '/../../bootstrap.php';
        
        // Track if register_activation_hook was called
        $hook_called = false;
        Brain\Monkey\Functions\when('register_activation_hook')->returnCallback(function () use (&$hook_called) {
            $hook_called = true;
            return true;
        });
        
        Brain\Monkey\Functions\when('register_deactivation_hook')->justReturn(true);
        Brain\Monkey\Functions\when('class_exists')->justReturn(false); // Prevent Main class from being instantiated
        
        // Simulate initialization
        hyperpress_run_initialization_logic('/path/to/plugin/api-for-htmx.php', '3.0.1');
        
        // In special modes, the main initialization should be skipped
        expect($hook_called)->toBeFalse();
    }
});

test('initialization sets default compact input constants', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('get_file_data')->return(['Version' => '3.0.1']);
    Brain\Monkey\Functions\when('realpath')->returnArg(0);
    Brain\Monkey\Functions\when('has_action')->justReturn(false);
    Brain\Monkey\Functions\when('plugin_dir_path')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_basename')->returnArg(0);
    Brain\Monkey\Functions\when('plugin_dir_url')->returnArg(0);
    Brain\Monkey\Functions\when('trailingslashit')->returnArg(0);
    Brain\Monkey\Functions\when('register_activation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('register_deactivation_hook')->justReturn(true);
    Brain\Monkey\Functions\when('class_exists')->justReturn(false); // Prevent Main class from being instantiated
    
    Brain\Monkey\Functions\when('file_exists')->returnArg(1);
    
    // Mock helper files existence
    Brain\Monkey\Functions\expect('require_once')->times(2);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Simulate initialization
    hyperpress_run_initialization_logic('/path/to/plugin/api-for-htmx.php', '3.0.1');
    
    // Check compact input constants with default values
    expect(defined('HYPERPRESS_COMPACT_INPUT'))->toBeTrue();
    expect(defined('HYPERPRESS_COMPACT_INPUT_KEY'))->toBeTrue();
    expect(HYPERPRESS_COMPACT_INPUT)->toBeFalse();
    expect(HYPERPRESS_COMPACT_INPUT_KEY)->toBe('hyperpress_compact_input');
});