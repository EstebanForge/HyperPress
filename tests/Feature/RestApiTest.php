<?php

/**
 * Feature test for HyperPress REST API endpoints
 */

use HyperPress\Tests\WordPressTestCase;
use HyperPress\Router;

uses(WordPressTestCase::class);

beforeEach(function () {
    $this->mockWordPressFunctions();
});

test('REST API endpoints are registered', function () {
    Brain\Monkey\Functions\when('register_rest_route')->justReturn(true);
    
    $router = new Router();
    $result = $router->registerRoutes();
    
    expect($result)->toBeTrue();
});

test('HTML endpoint can handle requests', function () {
    $router = new Router();
    
    $request = [
        'params' => ['template' => 'test']
    ];
    
    $response = $router->handleHtmlRequest($request);
    expect($response)->toBeArray();
});

test('REST API permissions work correctly', function () {
    Brain\Monkey\Functions\when('current_user_can')->justReturn(true);
    
    $router = new Router();
    $result = $router->checkPermissions();
    
    expect($result)->toBeTrue();
});

test('REST API can handle errors gracefully', function () {
    $router = new Router();
    
    $error = $router->handleError(new \Exception('Test error'));
    expect($error)->toBeArray();
    expect($error['code'])->toBe(500);
});