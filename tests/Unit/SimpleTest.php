<?php

/**
 * Simple unit test for HyperPress with WP_Mock
 */

use HyperPress\Tests\WordPressTestCase;

uses(WordPressTestCase::class);

test('WP_Mock can mock WordPress functions', function () {
    // Mock a simple WordPress function
    WP_Mock::userFunction('is_admin')->andReturn(true);
    WP_Mock::passthruFunction('__');
    
    expect(is_admin())->toBeTrue();
    expect(__('test'))->toBe('test');
});

test('WP_Mock can mock option functions', function () {
    // Mock get_option
    WP_Mock::userFunction('get_option')
        ->with('test_option')
        ->andReturn('test_value');
    
    expect(get_option('test_option'))->toBe('test_value');
});

test('Basic PHP functionality works', function () {
    expect(true)->toBeTrue();
    expect(false)->toBeFalse();
    expect(1 + 1)->toBe(2);
});

test('WordPressTestCase can be instantiated', function () {
    expect($this)->toBeInstanceOf(WordPressTestCase::class);
});

test('WP_Mock can mock multiple function calls', function () {
    // Mock different behaviors for different arguments
    WP_Mock::userFunction('get_option')
        ->with('option1')->andReturn('value1')
        ->with('option2')->andReturn('value2');
    
    expect(get_option('option1'))->toBe('value1');
    expect(get_option('option2'))->toBe('value2');
});