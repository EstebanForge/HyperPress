<?php

/**
 * Integration test for HyperPress plugin activation
 */

use HyperPress\Tests\WordPressTestCase;
use HyperPress\Admin\Activation;

uses(WordPressTestCase::class);

beforeEach(function () {
    $this->mockWordPressFunctions();
});

test('Plugin activation runs successfully', function () {
    Brain\Monkey\Functions\when('get_option')->justReturn(false);
    Brain\Monkey\Functions\when('add_option')->justReturn(true);
    Brain\Monkey\Functions\when('update_option')->justReturn(true);
    Brain\Monkey\Functions\when('flush_rewrite_rules')->justReturn(true);
    
    $activation = new Activation();
    $result = $activation->activate();
    
    expect($result)->toBeTrue();
});

test('Plugin deactivation runs successfully', function () {
    Brain\Monkey\Functions\when('delete_option')->justReturn(true);
    Brain\Monkey\Functions\when('flush_rewrite_rules')->justReturn(true);
    
    $activation = new Activation();
    $result = $activation->deactivate();
    
    expect($result)->toBeTrue();
});

test('Plugin can check version compatibility', function () {
    Brain\Monkey\Functions\when('get_bloginfo')->justReturn('6.8.2');
    
    $activation = new Activation();
    $compatible = $activation->checkWordPressVersion();
    
    expect($compatible)->toBeTrue();
});

test('Plugin can create database tables', function () {
    // Mock database operations
    Brain\Monkey\Functions\when('dbDelta')->justReturn([]);
    
    $activation = new Activation();
    $result = $activation->createTables();
    
    expect($result)->toBeTrue();
});