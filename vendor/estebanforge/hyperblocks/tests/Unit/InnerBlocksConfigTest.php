<?php

declare(strict_types=1);

use HyperBlocks\Block\Block;
use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks\WordPress\Bootstrap;
use HyperBlocks_Testing_Registry;

beforeEach(function (): void {
    Config::reset();
    Registry::reset();
    HyperBlocks_Testing_Registry::reset();
    $GLOBALS['__hb_test_filters'] = [];
});

afterEach(function (): void {
    Config::reset();
    Registry::reset();
    HyperBlocks_Testing_Registry::reset();
    $GLOBALS['__hb_test_filters'] = [];
});

/*
 * Fluent innerBlocks() configuration. Null = no inner-blocks area (existing
 * blocks unchanged). A config array opts the block in: native allowed_blocks
 * bridge on the server, innerBlocks payload in the editor config.
 */

it('leaves inner_blocks null by default', function (): void {
    expect(Block::make('Plain')->inner_blocks)->toBeNull();
});

it('defaults to an empty config when enabled without arguments', function (): void {
    $block = Block::make('Nested')->innerBlocks();

    expect($block->inner_blocks)->toBe([]);
    expect($block->inner_blocks)->not->toBeNull();
});

it('stores allowedBlocks, template, and templateLock config', function (): void {
    $block = Block::make('Nested')
        ->innerBlocks([
            'allowedBlocks' => ['core/paragraph', 'core/image'],
            'template'      => [['core/paragraph', ['placeholder' => 'Write...']]],
            'templateLock'  => 'insert',
        ]);

    expect($block->inner_blocks['allowedBlocks'])->toBe(['core/paragraph', 'core/image']);
    expect($block->inner_blocks['template'])->toBe([['core/paragraph', ['placeholder' => 'Write...']]]);
    expect($block->inner_blocks['templateLock'])->toBe('insert');
});

it('is chainable and exposes inner_blocks in toArray', function (): void {
    $array = Block::make('Chained')
        ->setIcon('star-filled')
        ->innerBlocks(['templateLock' => 'all'])
        ->toArray();

    expect($array['inner_blocks'])->toBe(['templateLock' => 'all']);
});

/*
 * Server registration: allowed_blocks rides the native WP bridge.
 */

it('bridges allowedBlocks to the native allowed_blocks registration argument', function (): void {
    Registry::getInstance()->registerFluentBlock(
        Block::make('Bridged')
            ->setName('acme/bridged')
            ->innerBlocks(['allowedBlocks' => ['core/paragraph', 'core/image']])
    );

    Bootstrap::registerBlocks();

    [$name, $args] = HyperBlocks_Testing_Registry::getLastBlockRegistration();
    expect($name)->toBe('acme/bridged');
    expect($args['allowed_blocks'])->toBe(['core/paragraph', 'core/image']);
});

it('omits allowed_blocks for blocks without innerBlocks config', function (): void {
    Registry::getInstance()->registerFluentBlock(
        Block::make('Plain')->setName('acme/plain')
    );

    Bootstrap::registerBlocks();

    [, $args] = HyperBlocks_Testing_Registry::getLastBlockRegistration();
    expect($args)->not->toHaveKey('allowed_blocks');
});

it('drops non-array allowedBlocks and template from the editor payload', function (): void {
    Registry::getInstance()->registerFluentBlock(
        Block::make('Bad Config')
            ->setName('acme/bad-config')
            ->innerBlocks([
                'allowedBlocks' => 'core/paragraph',
                'template'      => 'not-an-array',
                'templateLock'  => 'insert',
            ])
    );

    Bootstrap::registerBlocks();

    // Scalar templateLock rides along; malformed array-shaped keys must not
    // reach the client, where Gutenberg expects arrays and would throw.
    $inline = HyperBlocks_Testing_Registry::getLastInlineScript();
    expect($inline['data'])->toContain('templateLock');
    expect($inline['data'])->not->toContain('allowedBlocks');
    expect($inline['data'])->not->toContain('template":');
});

/*
 * Editor config: innerBlocks payload rides window.hyperBlocksConfig.
 */

it('injects innerBlocks config into the editor script payload', function (): void {
    Registry::getInstance()->registerFluentBlock(
        Block::make('Editor Nested')
            ->setName('acme/editor-nested')
            ->innerBlocks([
                'allowedBlocks' => ['core/paragraph'],
                'template'      => [['core/paragraph', []]],
                'templateLock'  => false,
            ])
    );

    Bootstrap::registerBlocks();

    $inline = HyperBlocks_Testing_Registry::getLastInlineScript();
    expect($inline['data'])->toContain('innerBlocks');

    // Decode the injected payload and assert the opt-in block's entry.
    expect(preg_match('/^window\.hyperBlocksConfig = (.*);$/s', $inline['data'], $m))->toBe(1);
    $payload = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    $entry = array_values(array_filter($payload, fn ($e) => $e['name'] === 'acme/editor-nested'));
    expect($entry)->toHaveCount(1);
    expect($entry[0]['innerBlocks'])->toBe([
        'allowedBlocks' => ['core/paragraph'],
        'template'      => [['core/paragraph', []]],
        'templateLock'  => false,
    ]);
});

it('keeps the editor payload free of innerBlocks for opt-out blocks', function (): void {
    Registry::getInstance()->registerFluentBlock(
        Block::make('Editor Plain')->setName('acme/editor-plain')
    );

    Bootstrap::registerBlocks();

    $inline = HyperBlocks_Testing_Registry::getLastInlineScript();
    expect($inline['data'])->not->toContain('innerBlocks');
});
