<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit\Block;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Block class.
 */
class BlockTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Config before each test
        \HyperBlocks\Config::reset();
        parent::setUp();
    }

    public function testBlockCreationWithTitle(): void
    {
        $block = Block::make('Test Block');

        $this->assertEquals('Test Block', $block->title);
        $this->assertEquals('hyperblocks/test-block', $block->name);
    }

    public function testSetName(): void
    {
        $block = Block::make('Test')
            ->setName('custom/name');

        $this->assertEquals('custom/name', $block->name);
    }

    public function testSetIcon(): void
    {
        $block = Block::make('Test')
            ->setIcon('star-filled');

        $this->assertEquals('star-filled', $block->icon);
    }

    public function testAddField(): void
    {
        $field = Field::make('text', 'title', 'Title');

        $block = Block::make('Test')
            ->addFields([$field]);

        $this->assertCount(1, $block->fields);
        $this->assertSame($field, $block->fields[0]);
    }

    public function testAddMultipleFields(): void
    {
        $field1 = Field::make('text', 'title', 'Title');
        $field2 = Field::make('textarea', 'description', 'Description');

        $block = Block::make('Test')
            ->addFields([$field1])
            ->addFields([$field2]);

        $this->assertCount(2, $block->fields);
    }

    public function testAddFieldGroup(): void
    {
        $block = Block::make('Test')
            ->addFieldGroup('common-fields');

        $this->assertContains('common-fields', $block->field_groups);
    }

    public function testSetRenderTemplateString(): void
    {
        $block = Block::make('Test')
            ->setRenderTemplate('<div>{{ content }}</div>');

        $this->assertEquals('<div>{{ content }}</div>', $block->render_template);
    }

    public function testSetRenderTemplateFile(): void
    {
        $block = Block::make('Test')
            ->setRenderTemplateFile('blocks/test.hb.php');

        $this->assertEquals('file:blocks/test.hb.php', $block->render_template);
    }

    public function testFluentApiChaining(): void
    {
        $block = Block::make('Test')
            ->setName('test/block')
            ->setIcon('star')
            ->setRenderTemplateFile('test.hb.php');

        $this->assertInstanceOf(Block::class, $block);
        $this->assertEquals('test/block', $block->name);
        $this->assertEquals('star', $block->icon);
    }

    public function testToArray(): void
    {
        $field = Field::make('text', 'title', 'Title');

        $block = Block::make('Test Block')
            ->setName('test/block')
            ->setIcon('star-filled')
            ->addFields([$field])
            ->addFieldGroup('common');

        $array = $block->toArray();

        $this->assertEquals('test/block', $array['name']);
        $this->assertEquals('Test Block', $array['title']);
        $this->assertEquals('star-filled', $array['icon']);
        $this->assertIsArray($array['fields']);
        $this->assertContains('common', $array['field_groups']);
    }

    public function testGetFieldAdapters(): void
    {
        $field = Field::make('text', 'title', 'Title');

        $block = Block::make('Test')
            ->addFields([$field]);

        $adapters = $block->getFieldAdapters();

        $this->assertArrayHasKey('title', $adapters);
        $this->assertInstanceOf(\HyperFields\BlockFieldAdapter::class, $adapters['title']);
    }
}
