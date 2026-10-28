<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\BlockOperations;
use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks\WordPress\Bootstrap;
use HyperBlocks_Testing_Registry;
use PHPUnit\Framework\TestCase;

/**
 * Editor sidebar opt-in, inline field-schema delivery, and the sample-data
 * preview flag.
 */
class EditorSidebarTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        parent::tearDown();
    }

    public function testEditorSidebarDefaultsToNull(): void
    {
        $block = Block::make('Plain Block');

        $this->assertNull($block->toArray()['editor_sidebar']);
    }

    public function testSetEditorSidebarChainsAndSerializes(): void
    {
        $block = Block::make('Sidebar Block');

        $this->assertSame($block, $block->setEditorSidebar());
        $this->assertTrue($block->toArray()['editor_sidebar']);

        $block->setEditorSidebar(false);
        $this->assertFalse($block->toArray()['editor_sidebar']);
    }

    public function testBlockWithoutOptInOmitsSidebarSchema(): void
    {
        $block = Block::make('Plain Schema Block')
            ->setName('test/plain-schema')
            ->addFields([Field::make('text', 'heading', 'Heading')->setTab('Content')]);
        Registry::getInstance()->registerFluentBlock($block);
        Bootstrap::registerBlocks();

        $config = $this->decodedConfig();
        $entry = $config[0] ?? null;

        $this->assertIsArray($entry);
        $this->assertArrayNotHasKey('editorSidebar', $entry);
        $this->assertArrayNotHasKey('fields', $entry);
    }

    public function testOptInBlockDeliversFieldSchemaInline(): void
    {
        $block = Block::make('Sidebar Schema Block')
            ->setName('test/sidebar-schema')
            ->setEditorSidebar()
            ->addFields([
                Field::make('text', 'heading', 'Heading')
                    ->setTab('Content')
                    ->setSection('Copy')
                    ->setLive(),
                Field::make('select', 'layout', 'Layout')
                    ->setOptions(['boxed' => 'Boxed', 'full' => 'Full'])
                    ->setDefault('boxed'),
            ]);
        Registry::getInstance()->registerFluentBlock($block);
        Bootstrap::registerBlocks();

        $entry = $this->decodedConfig()[0];

        $this->assertTrue($entry['editorSidebar']);
        $this->assertCount(2, $entry['fields']);

        $heading = $entry['fields'][0];
        $this->assertSame('heading', $heading['name']);
        $this->assertSame('Content', $heading['tab']);
        $this->assertSame('Copy', $heading['section']);
        $this->assertTrue($heading['live']);

        $layout = $entry['fields'][1];
        $this->assertSame('boxed', $layout['default']);
        $this->assertNull($layout['tab']);
    }

    public function testSampleFlagReachesTemplateAsHbSample(): void
    {
        $block = Block::make('Sample Block')
            ->setName('test/sample')
            ->setRenderTemplate('<?php echo $hb_sample ? "SAMPLE" : "REAL"; ?>');
        Registry::getInstance()->registerFluentBlock($block);

        $real = BlockOperations::preview('test/sample', [], '');
        $sample = BlockOperations::preview('test/sample', [], '', true);

        $this->assertSame('ok', $real['status']);
        $this->assertSame('REAL', $real['html']);
        $this->assertSame('SAMPLE', $sample['html']);
    }

    public function testSampleFlagCannotBeSpoofedThroughAttributes(): void
    {
        $block = Block::make('Spoof Block')
            ->setName('test/spoof')
            ->setRenderTemplate('<?php echo $hb_sample ? "SAMPLE" : "REAL"; ?>');
        Registry::getInstance()->registerFluentBlock($block);

        $result = BlockOperations::preview('test/spoof', ['hb_sample' => true], '');

        $this->assertSame('REAL', $result['html']);
    }

    public function testInlineSchemaShipsOnlySidebarKeys(): void
    {
        $block = Block::make('Projected Schema Block')
            ->setName('test/projected-schema')
            ->setEditorSidebar()
            ->addFields([Field::make('text', 'heading', 'Heading')]);
        Registry::getInstance()->registerFluentBlock($block);
        Bootstrap::registerBlocks();

        $heading = $this->decodedConfig()[0]['fields'][0];

        $this->assertArrayNotHasKey('name_attr', $heading);
        $this->assertArrayNotHasKey('validation', $heading);
        $this->assertArrayNotHasKey('args', $heading);
        $this->assertSame('heading', $heading['name']);
    }

    public function testInlineConfigEncodesMarkupBreakouts(): void
    {
        $block = Block::make('Encode Block')
            ->setName('test/encode')
            ->setEditorSidebar()
            ->addFields([Field::make('text', 'heading', 'Heading </script><img src=x onerror=alert(1)>')]);
        Registry::getInstance()->registerFluentBlock($block);
        Bootstrap::registerBlocks();

        $inline = HyperBlocks_Testing_Registry::getLastInlineScript();

        $this->assertStringNotContainsString('</script>', $inline['data']);
        $this->assertStringContainsString('\u003C', $inline['data']);
    }

    /**
     * Decode the block config payload the bootstrap seeded inline.
     *
     * @return array<int, mixed>
     */
    private function decodedConfig(): array
    {
        $inline = HyperBlocks_Testing_Registry::getLastInlineScript();
        $this->assertStringContainsString('window.hyperBlocksConfig', $inline['data']);

        $json = (string) preg_replace(
            '/^\s*window\.hyperBlocksConfig\s*=\s*/',
            '',
            $inline['data']
        );
        $json = (string) preg_replace('/;\s*$/', '', $json);

        $decoded = json_decode($json, true, 512, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $this->assertIsArray($decoded);

        return $decoded;
    }
}
