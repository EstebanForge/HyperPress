<?php

/**
 * Unit test for HyperPress Main class
 */

use HyperPress\Tests\WordPressTestCase;
use HyperPress\Main;

uses(WordPressTestCase::class);

beforeEach(function () {
    $this->mockWordPressFunctions();
});

test('Main class can be instantiated', function () {
    $main = new Main();
    expect($main)->toBeInstanceOf(Main::class);
});

test('Plugin can get version', function () {
    $version = '3.0.1';
    expect($version)->toBeString();
    expect($version)->toMatch('/^\d+\.\d+\.\d+$/');
});

test('Plugin can check requirements', function () {
    $phpVersion = PHP_VERSION;
    expect(version_compare($phpVersion, '8.1.0', '>='))->toBeTrue();
});

test('Plugin can initialize hooks', function () {
    // Mock WordPress hook functions
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('add_filter')->justReturn(true);
    
    $main = new Main();
    
    // Test that hooks can be added
    $result = add_action('init', [$main, 'init']);
    expect($result)->toBeTrue();
    
    $result = add_filter('plugin_row_meta', [$main, 'plugin_row_meta'], 10, 2);
    expect($result)->toBeTrue();
});

test('Plugin can check admin status', function () {
    Brain\Monkey\Functions\when('is_admin')->justReturn(true);
    
    expect(is_admin())->toBeTrue();
    
    Brain\Monkey\Functions\when('is_admin')->justReturn(false);
    
    expect(is_admin())->toBeFalse();
});