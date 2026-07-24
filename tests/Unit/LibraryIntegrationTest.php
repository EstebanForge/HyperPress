<?php

declare(strict_types=1);

/**
 * Integration smoke tests.
 *
 * Verifies the three bundled libraries autoload together, expose their
 * prefix-safe Config identity, and that the ::class references the adapter
 * bootstrap relies on all resolve. These are pure autoload + class-constant
 * checks: no WordPress mocking, so they are fast and stable.
 *
 * This is the foundation layer. The next layer to add on top is runtime
 * coverage with WordPress function mocking (Brain\Monkey): init flow
 * (Bootstrap::init fires at after_setup_theme), the /wp-html/v1/ REST route,
 * asset enqueue, helper availability (hp_/hf_/hb_), and template rendering.
 */

it('autoloads the three bundled libraries', function (): void {
    expect(class_exists(\HyperPress\Bootstrap::class))->toBeTrue()
        ->and(class_exists(\HyperPress\Config::class))->toBeTrue()
        ->and(class_exists(\HyperFields\Config::class))->toBeTrue()
        ->and(class_exists(\HyperBlocks\Config::class))->toBeTrue();
});

it('exposes a non-empty version constant on each library Config', function (): void {
    expect(\HyperPress\Config::VERSION)->toBeString()->not->toBeEmpty()
        ->and(\HyperFields\Config::VERSION)->toBeString()->not->toBeEmpty()
        ->and(\HyperBlocks\Config::VERSION)->toBeString()->not->toBeEmpty();
});

it('resolves the class references the adapter bootstrap wires against', function (): void {
    // These are the prefix-safe references bootstrap.php uses. Unprefixed here
    // is the baseline; under a Mozart build they rewrite to the prefixed FQN.
    expect(class_exists(\HyperFields\HyperFields::class))->toBeTrue()
        ->and(class_exists(\HyperPress\Admin\Activation::class))->toBeTrue();
});

it('does not redeclare the removed global election helper functions', function (): void {
    // The libraries dropped the *_select_and_load_latest election functions;
    // confirm the plugin's dependency tree no longer ships them.
    expect(function_exists('hyperpress_select_and_load_latest'))->toBeFalse()
        ->and(function_exists('hyperfields_select_and_load_latest'))->toBeFalse()
        ->and(function_exists('hyperblocks_select_and_load_latest'))->toBeFalse();
});
