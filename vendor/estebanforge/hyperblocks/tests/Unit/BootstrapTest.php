<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks\WordPress\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Global test-capture helper defined in tests/mocks/wp-mocks.php.
 * Lives in the global namespace; the use-statement imports it unqualified.
 */
use HyperBlocks_Testing_Registry;

/**
 * Unit tests for Bootstrap::registerSingleBlock().
 *
 * Verifies the wiring point where fluent Block metadata (category,
 * description, keywords, style) is threaded into register_block_type()
 * conditionally. This is the backward-compat guarantee: blocks that don't
 * set metadata must produce args identical to the pre-feature behavior.
 *
 * Uses the hand-rolled register_block_type mock in tests/mocks/wp-mocks.php
 * (HB's house convention), which records calls into HyperBlocks_Testing_Registry.
 */
class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        parent::tearDown();
    }

    /**
     * Backward-compat baseline: a block with NO metadata set must yield args
     * without category/description/keywords/style keys. This is the guarantee
     * that existing fluent blocks behave exactly as before the feature landed.
     */
    public function testRegisterSingleBlockOmitsUnsetMetadata(): void
    {
        $block = Block::make('Test Block')
            ->setName('test/no-meta')
            ->addFields([Field::make('text', 'heading', 'Heading')]);

        Bootstrap::registerSingleBlock($block);

        [$name, $args] = HyperBlocks_Testing_Registry::getLastBlockRegistration();

        $this->assertSame('test/no-meta', $name);
        $this->assertIsArray($args);
        $this->assertArrayNotHasKey('category', $args);
        $this->assertArrayNotHasKey('description', $args);
        $this->assertArrayNotHasKey('keywords', $args);
        $this->assertArrayNotHasKey('style', $args);
        // Core args still present.
        $this->assertSame('Test Block', $args['title']);
        $this->assertSame(2, $args['api_version']);
        $this->assertArrayHasKey('attributes', $args);
        $this->assertArrayHasKey('render_callback', $args);
    }

    /**
     * Each set metadata field must appear in the args with its value.
     */
    public function testRegisterSingleBlockIncludesSetMetadata(): void
    {
        $block = Block::make('Test Block')
            ->setName('test/with-meta')
            ->setCategory('widgets')
            ->setDescription('A helpful block.')
            ->setKeywords(['foo', 'bar'])
            ->setStyle('my-block-style');

        Bootstrap::registerSingleBlock($block);

        [, $args] = HyperBlocks_Testing_Registry::getLastBlockRegistration();

        $this->assertSame('widgets', $args['category']);
        $this->assertSame('A helpful block.', $args['description']);
        $this->assertSame(['foo', 'bar'], $args['keywords']);
        $this->assertSame('my-block-style', $args['style']);
    }

    /**
     * Partial metadata: setting only one field must include only that field,
     * the others stay absent. Catches a future refactor that bundles all four
     * into a single conditional.
     */
    public function testRegisterSingleBlockIncludesOnlySetFields(): void
    {
        $block = Block::make('Test Block')
            ->setName('test/partial-meta')
            ->setCategory('layout');

        Bootstrap::registerSingleBlock($block);

        [, $args] = HyperBlocks_Testing_Registry::getLastBlockRegistration();

        $this->assertArrayHasKey('category', $args);
        $this->assertSame('layout', $args['category']);
        $this->assertArrayNotHasKey('description', $args);
        $this->assertArrayNotHasKey('keywords', $args);
        $this->assertArrayNotHasKey('style', $args);
    }
}
