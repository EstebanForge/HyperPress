<?php

/**
 * Unit test for HyperPress HTMX Library
 */

use HyperPress\Tests\WordPressTestCase;
use HyperPress\Libraries\HTMXLib;

uses(WordPressTestCase::class);

beforeEach(function () {
    $this->mockWordPressFunctions();
});

test('HTMX library can be instantiated', function () {
    $htmx = new HTMXLib();
    expect($htmx)->toBeInstanceOf(HTMXLib::class);
});

test('HTMX can detect HTMX request', function () {
    Brain\Monkey\Functions\when('getallheaders')->justReturn([
        'HX-Request' => 'true'
    ]);
    
    $htmx = new HTMXLib();
    expect($htmx->isHtmxRequest())->toBeTrue();
    
    Brain\Monkey\Functions\when('getallheaders')->justReturn([]);
    expect($htmx->isHtmxRequest())->toBeFalse();
});

test('HTMX can get current URL', function () {
    Brain\Monkey\Functions\when('home_url')->justReturn('https://example.org');
    
    $htmx = new HTMXLib();
    expect($htmx->getCurrentUrl())->toBe('https://example.org');
});

test('HTMX can trigger response', function () {
    $htmx = new HTMXLib();
    
    $response = $htmx->triggerResponse('test-event', ['data' => 'test']);
    expect($response)->toBeArray();
    expect($response['HX-Trigger'])->toBe('test-event');
});

test('HTMX can handle partial responses', function () {
    $htmx = new HTMXLib();
    
    $partial = $htmx->renderPartial('<div>Partial content</div>');
    expect($partial)->toBe('<div>Partial content</div>');
});