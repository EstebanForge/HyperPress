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
 * Unit tests for the editor-script enqueue path.
 *
 * Covers the fix for fluent blocks never appearing in the Gutenberg inserter:
 * the editor script handle was referenced by register_block_type() but never
 * registered, so the client had no wp.blocks.registerBlockType() call. The flow
 * under test is Bootstrap::registerBlocks() -> registerFluentBlocksWithWordPress()
 * -> registerEditorScript().
 *
 * Uses the hand-rolled wp_register_script / wp_add_inline_script mocks in
 * tests/mocks/wp-mocks.php (HB's house convention), which record calls into
 * HyperBlocks_Testing_Registry.
 */
class EditorScriptTest extends TestCase
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
     * Register a single fluent block, then run the init-time registration flow
     * exactly as WordPress would on `init`.
     */
    private function registerWithBlock(string $name): void
    {
        $block = Block::make('Editor Test Block')
            ->setName($name)
            ->addFields([Field::make('text', 'heading', 'Heading')]);

        Registry::getInstance()->registerFluentBlock($block);
        Bootstrap::registerBlocks();
    }

    /**
     * With at least one fluent block present, the editor script must be
     * registered (not enqueued) under the configured handle and resolve to the
     * bundled editor.js URL. Registering rather than enqueuing is deliberate:
     * this runs on `init`, so enqueueing would leak the Gutenberg bundle onto
     * every front-end page. Core enqueues the handle in the editor only, via the
     * block type's `editor_script` argument.
     */
    public function testEditorScriptRegisteredWithCorrectHandleUrlAndDeps(): void
    {
        $this->registerWithBlock('test/editor-block');

        $registration = HyperBlocks_Testing_Registry::getLastRegisterScript();

        $this->assertSame(Config::getEditorScriptHandle(), $registration['handle']);
        $this->assertSame(
            HYPERBLOCKS_PLUGIN_URL . 'assets/js/editor.js',
            $registration['src']
        );
        $this->assertSame(
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-dom-ready', 'wp-block-editor', 'wp-server-side-render'],
            $registration['deps']
        );
        $this->assertTrue($registration['in_footer']);
    }

    /**
     * The editor script must never be enqueued directly anywhere (no enqueue
     * call expected); only registered. Guards against a regression that would
     * leak it onto the front end.
     */
    public function testEditorScriptIsNeverEnqueued(): void
    {
        $this->registerWithBlock('test/no-enqueue-block');

        $enqueue = HyperBlocks_Testing_Registry::getLastEnqueueScript();

        $this->assertSame('', $enqueue['handle']);
    }

    /**
     * Block configs must be injected before the editor script so editor.js can
     * read window.hyperBlocksConfig on load. Asserts both the handle and the
     * 'before' positioning.
     */
    public function testInlineScriptPassesBlockConfigBeforeEditorScript(): void
    {
        $this->registerWithBlock('test/inline-block');

        $inline = HyperBlocks_Testing_Registry::getLastInlineScript();

        $this->assertSame(Config::getEditorScriptHandle(), $inline['handle']);
        $this->assertSame('before', $inline['position']);
        $this->assertStringContainsString('window.hyperBlocksConfig', $inline['data']);
        $this->assertStringContainsString('inline-block', $inline['data']);
    }

    /**
     * With no fluent blocks registered, the editor script must not be
     * registered; registerFluentBlocksWithWordPress() bails out before
     * registration.
     */
    public function testEditorScriptNotRegisteredWhenNoFluentBlocks(): void
    {
        Bootstrap::registerBlocks();

        $registration = HyperBlocks_Testing_Registry::getLastRegisterScript();

        $this->assertSame('', $registration['handle']);
    }
}
