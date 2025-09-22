<?php

/**
 * Bootstrap file tests for HyperPress using WP_Mock
 * 
 * Tests the core plugin bootstrap functionality including:
 * - Plugin initialization and constants
 * - Version detection and path resolution
 * - Library vs plugin mode detection
 * - Hook registration
 * - Candidate selection logic
 */

use HyperPress\Tests\WordPressTestCase;

uses(WordPressTestCase::class);

beforeEach(function () {
    // Reset global state for each test
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

test('bootstrap defines HYPERPRESS_BOOTSTRAP_LOADED constant', function () {
    // Mock ABSPATH to prevent direct access exit
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Include bootstrap file
    require_once __DIR__ . '/../../bootstrap.php';
    
    expect(defined('HYPERPRESS_BOOTSTRAP_LOADED'))->toBeTrue();
    expect(HYPERPRESS_BOOTSTRAP_LOADED)->toBeTrue();
});

test('bootstrap runs only once due to constant check', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // First include should work
    ob_start();
    require_once __DIR__ . '/../../bootstrap.php';
    $first_output = ob_get_clean();
    
    // Second include should return early
    ob_start();
    require_once __DIR__ . '/../../bootstrap.php';
    $second_output = ob_get_clean();
    
    expect($first_output)->toBe($second_output);
});

test('bootstrap loads composer autoloader when available', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists to return true for vendor/autoload.php
    WP_Mock::userFunction('file_exists')->andReturn(true);
    
    // Mock plugin file data functions
    WP_Mock::userFunction('realpath')->andReturnFirstArg();
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Check that autoloader path was checked
    expect(defined('HYPERPRESS_BOOTSTRAP_LOADED'))->toBeTrue();
});

test('bootstrap adds admin notice when autoloader not found', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists to return false for autoloaders
    WP_Mock::userFunction('file_exists')->andReturn(false);
    
    // Mock plugin file data functions
    WP_Mock::userFunction('realpath')->andReturnFirstArg();
    
    // Expect admin notice action
    WP_Mock::expectAction('admin_notices');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Check that admin_notices was expected
    expect(defined('HYPERPRESS_BOOTSTRAP_LOADED'))->toBeTrue();
});

test('bootstrap registers plugin candidate in plugin mode', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists for autoloader and plugin files
    WP_Mock::userFunction('file_exists')->andReturn(true);
    
    // Mock plugin file data
    WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '3.0.1']);
    WP_Mock::userFunction('realpath')->andReturn('/path/to/plugin/api-for-htmx.php');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Check that candidate was registered
    expect(isset($GLOBALS['hyperpress_api_candidates']))->toBeTrue();
    expect(is_array($GLOBALS['hyperpress_api_candidates']))->toBeTrue();
    expect(count($GLOBALS['hyperpress_api_candidates']))->toBeGreaterThan(0);
    
    // Check candidate structure
    $candidate = reset($GLOBALS['hyperpress_api_candidates']);
    expect($candidate['version'])->toBe('3.0.1');
    expect($candidate['path'])->toBe('/path/to/plugin/api-for-htmx.php');
    expect($candidate['init_function'])->toBe('hyperpress_run_initialization_logic');
});

test('bootstrap registers plugin candidate in library mode', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists to simulate library mode
    WP_Mock::userFunction('file_exists')->andReturn(
        false, // vendor/autoload.php (fallback)
        false, // hyperpress.php
        false, // api-for-htmx.php
        true,  // composer.json
        true   // vendor/autoload.php fallback
    );
    
    // Mock plugin file data
    WP_Mock::userFunction('realpath')->andReturn('/path/to/library/bootstrap.php');
    WP_Mock::userFunction('file_get_contents')->andReturn('{"version": "3.0.1"}');
    WP_Mock::userFunction('json_decode')->andReturn(['version' => '3.0.1']);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Check that candidate was registered
    expect(isset($GLOBALS['hyperpress_api_candidates']))->toBeTrue();
    expect(is_array($GLOBALS['hyperpress_api_candidates']))->toBeTrue();
    
    // Check candidate structure
    $candidate = reset($GLOBALS['hyperpress_api_candidates']);
    expect($candidate['version'])->toBe('3.0.1');
    expect($candidate['path'])->toBe('/path/to/library/bootstrap.php');
});

test('bootstrap registers after_setup_theme hook', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists
    WP_Mock::userFunction('file_exists')->andReturn(true);
    
    // Mock plugin file data
    WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '3.0.1']);
    WP_Mock::userFunction('realpath')->andReturn('/path/to/plugin/api-for-htmx.php');
    
    // Expect action to be added
    WP_Mock::expectActionAdded('after_setup_theme', 'hyperpress_select_and_load_latest', 0);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Check that hook was registered
    expect(defined('HYPERPRESS_BOOTSTRAP_LOADED'))->toBeTrue();
});

test('hyperpress_run_initialization_logic function exists', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists
    WP_Mock::userFunction('file_exists')->andReturn(true);
    
    // Mock plugin file data
    WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '3.0.1']);
    WP_Mock::userFunction('realpath')->andReturn('/path/to/plugin/api-for-htmx.php');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    expect(function_exists('hyperpress_run_initialization_logic'))->toBeTrue();
});

test('hyperpress_select_and_load_latest function exists', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists
    WP_Mock::userFunction('file_exists')->andReturn(true);
    
    // Mock plugin file data
    WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '3.0.1']);
    WP_Mock::userFunction('realpath')->andReturn('/path/to/plugin/api-for-htmx.php');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    expect(function_exists('hyperpress_select_and_load_latest'))->toBeTrue();
});

test('hyperpress_register_candidate_for_tests function exists', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists
    WP_Mock::userFunction('file_exists')->andReturn(true);
    
    // Mock plugin file data
    WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '3.0.1']);
    WP_Mock::userFunction('realpath')->andReturn('/path/to/plugin/api-for-htmx.php');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    expect(function_exists('hyperpress_register_candidate_for_tests'))->toBeTrue();
});

test('hyperpress_select_and_load_latest selects highest version candidate', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists
    WP_Mock::userFunction('file_exists')->andReturn(true);
    
    // Mock plugin file data
    WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '3.0.1']);
    WP_Mock::userFunction('realpath')->andReturn('/path/to/plugin/api-for-htmx.php');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Set up test candidates
    $GLOBALS['hyperpress_api_candidates'] = [
        '/path/to/v1' => [
            'version' => '1.0.0',
            'path' => '/path/to/v1',
            'init_function' => 'hyperpress_run_initialization_logic',
        ],
        '/path/to/v3' => [
            'version' => '3.0.0',
            'path' => '/path/to/v3',
            'init_function' => 'hyperpress_run_initialization_logic',
        ],
        '/path/to/v2' => [
            'version' => '2.0.0',
            'path' => '/path/to/v2',
            'init_function' => 'hyperpress_run_initialization_logic',
        ],
    ];
    
    // Mock call_user_func to capture the winner
    $selected_candidate = null;
    WP_Mock::userFunction('call_user_func')->andReturnCallback(function ($function, $path, $version) use (&$selected_candidate) {
        $selected_candidate = [
            'function' => $function,
            'path' => $path,
            'version' => $version,
        ];
        return true;
    });
    
    // Run the selection function
    hyperpress_select_and_load_latest();
    
    // Check that the highest version was selected
    expect($selected_candidate)->not->toBeNull();
    expect($selected_candidate['version'])->toBe('3.0.0');
    expect($selected_candidate['path'])->toBe('/path/to/v3');
});

test('bootstrap handles missing plugin files gracefully', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists to simulate missing plugin files
    WP_Mock::userFunction('file_exists')->andReturn(
        true,  // vendor/autoload.php
        false, // hyperpress.php
        false, // api-for-htmx.php
        false, // composer.json
        true   // vendor/autoload.php fallback
    );
    
    // Mock plugin file data
    WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '0.0.0']);
    WP_Mock::userFunction('realpath')->andReturn('/path/to/bootstrap.php');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Should still work without throwing errors
    expect(defined('HYPERPRESS_BOOTSTRAP_LOADED'))->toBeTrue();
});

test('bootstrap handles composer.json version parsing', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists for library mode
    WP_Mock::userFunction('file_exists')->andReturn(
        false, // vendor/autoload.php (fallback)
        false, // hyperpress.php
        false, // api-for-htmx.php
        true,  // composer.json
        true   // vendor/autoload.php fallback
    );
    
    // Mock composer.json parsing
    WP_Mock::userFunction('realpath')->andReturn('/path/to/library/bootstrap.php');
    WP_Mock::userFunction('file_get_contents')->andReturn('{"version": "2.5.0", "name": "test/plugin"}');
    WP_Mock::userFunction('json_decode')->andReturn(['version' => '2.5.0', 'name' => 'test/plugin']);
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Check that candidate was registered with correct version
    expect(isset($GLOBALS['hyperpress_api_candidates']))->toBeTrue();
    $candidate = reset($GLOBALS['hyperpress_api_candidates']);
    expect($candidate['version'])->toBe('2.5.0');
});

test('bootstrap uses fallback version when none available', function () {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    
    // Mock file_exists for library mode without version
    WP_Mock::userFunction('file_exists')->andReturn(
        false, // vendor/autoload.php (fallback)
        false, // hyperpress.php
        false, // api-for-htmx.php
        false, // composer.json
        true   // vendor/autoload.php fallback
    );
    
    // Mock plugin file data
    WP_Mock::userFunction('realpath')->andReturn('/path/to/library/bootstrap.php');
    
    // Include bootstrap
    require_once __DIR__ . '/../../bootstrap.php';
    
    // Check that candidate was registered with fallback version
    expect(isset($GLOBALS['hyperpress_api_candidates']))->toBeTrue();
    $candidate = reset($GLOBALS['hyperpress_api_candidates']);
    expect($candidate['version'])->toBe('0.0.0');
});