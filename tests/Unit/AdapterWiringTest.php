<?php

declare(strict_types=1);

require_once __DIR__ . '/AdapterTestCase.php';

use HyperPress\Admin\Activation;
use Tests\Unit\AdapterTestCase;

uses(AdapterTestCase::class);

/*
 * Wiring coverage: the adapter attaches the right hooks at load time. These
 * guard against a refactor silently dropping or renaming a registration. They
 * do NOT exercise the closures' logic (Brain Monkey does not run registered
 * callbacks); that is covered by the pure-function unit tests.
 */

it('opts into the HyperPress settings menu', function (): void {
    expect(has_filter('hyperpress/admin/show_menu'))->toBeTrue();
});

it('registers the system_info filter', function (): void {
    expect(has_filter('hyperpress/about/system_info'))->toBeTrue();
});

it('registers the data tools page on admin_menu', function (): void {
    expect(has_action('admin_menu'))->toBeTrue();
});

it('wires activation and deactivation to the Activation class', function (): void {
    $activate = AdapterTestCase::lifecycleHook('activate');
    $deactivate = AdapterTestCase::lifecycleHook('deactivate');

    expect($activate)->not->toBeNull()
        ->and($activate['callback'])->toBe([Activation::class, 'activate']);

    expect($deactivate)->not->toBeNull()
        ->and($deactivate['callback'])->toBe([Activation::class, 'deactivate']);
});
