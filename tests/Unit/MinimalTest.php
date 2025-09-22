<?php

/**
 * Minimal test to check WP_Mock integration
 */

use HyperPress\Tests\WordPressTestCase;

uses(WordPressTestCase::class);

test('minimal test passes', function () {
    expect(true)->toBeTrue();
});