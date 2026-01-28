<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Registry;
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Registry class.
 */
class RegistryTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Config and Registry before each test
        \HyperBlocks\Config::reset();
        Registry::reset();
        parent::setUp();
    }

    public function testSingletonPattern(): void
    {
        $instance1 = Registry::getInstance();
        $instance2 = Registry::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testRegisterFluentBlock(): void
    {
        $block = Block::make('Test Block')
            ->setName('test/block');

        $registry = Registry::getInstance();
        $registry->registerFluentBlock($block);

        $this->assertTrue($registry->hasFluentBlock('test/block'));
    }

    public function testGetFluentBlock(): void
    {
        $block = Block::make('Test Block')
            ->setName('test/block');

        $registry = Registry::getInstance();
        $registry->registerFluentBlock($block);

        $retrieved = $registry->getFluentBlock('test/block');

        $this->assertInstanceOf(Block::class, $retrieved);
        $this->assertEquals('Test Block', $retrieved->title);
    }

    public function testGetFluentBlockReturnsNullWhenNotFound(): void
    {
        $registry = Registry::getInstance();
        $block = $registry->getFluentBlock('non-existent/block');

        $this->assertNull($block);
    }

    public function testHasFluentBlock(): void
    {
        $block = Block::make('Test Block')
            ->setName('test/block');

        $registry = Registry::getInstance();
        $registry->registerFluentBlock($block);

        $this->assertTrue($registry->hasFluentBlock('test/block'));
        $this->assertFalse($registry->hasFluentBlock('other/block'));
    }

    public function testGetFluentBlocks(): void
    {
        $block1 = Block::make('Block 1')->setName('test/block-1');
        $block2 = Block::make('Block 2')->setName('test/block-2');

        $registry = Registry::getInstance();
        $registry->registerFluentBlock($block1);
        $registry->registerFluentBlock($block2);

        $blocks = $registry->getFluentBlocks();

        $this->assertCount(2, $blocks);
        $this->assertArrayHasKey('test/block-1', $blocks);
        $this->assertArrayHasKey('test/block-2', $blocks);
    }

    public function testRegisterFieldGroup(): void
    {
        $group = FieldGroup::make('Common Fields', 'common');

        $registry = Registry::getInstance();
        $registry->registerFieldGroup($group);

        $retrieved = $registry->getFieldGroup('common');

        $this->assertInstanceOf(FieldGroup::class, $retrieved);
        $this->assertEquals('Common Fields', $retrieved->name);
    }

    public function testGetFieldGroupReturnsNullWhenNotFound(): void
    {
        $registry = Registry::getInstance();
        $group = $registry->getFieldGroup('non-existent');

        $this->assertNull($group);
    }

    public function testGetFieldGroups(): void
    {
        $group1 = FieldGroup::make('Group 1', 'group-1');
        $group2 = FieldGroup::make('Group 2', 'group-2');

        $registry = Registry::getInstance();
        $registry->registerFieldGroup($group1);
        $registry->registerFieldGroup($group2);

        $groups = $registry->getFieldGroups();

        $this->assertCount(2, $groups);
        $this->assertArrayHasKey('group-1', $groups);
        $this->assertArrayHasKey('group-2', $groups);
    }

    public function testGenerateBlockAttributes(): void
    {
        $field = Field::make('text', 'title', 'Title')
            ->setDefault('Default Title');

        $block = Block::make('Test Block')
            ->setName('test/block')
            ->addFields([$field]);

        $registry = Registry::getInstance();
        $attributes = $registry->generateBlockAttributes($block);

        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('type', $attributes['title']);
        $this->assertArrayHasKey('default', $attributes['title']);
    }

    public function testGetMergedFields(): void
    {
        $blockField = Field::make('text', 'block_title', 'Block Title');
        $groupField = Field::make('textarea', 'group_desc', 'Group Description');

        $block = Block::make('Test Block')
            ->setName('test/block')
            ->addFields([$blockField])
            ->addFieldGroup('content');

        $group = FieldGroup::make('Content Fields', 'content')
            ->addFields([$groupField]);

        $registry = Registry::getInstance();
        $registry->registerFieldGroup($group);

        $mergedFields = $registry->getMergedFields($block);

        $this->assertCount(2, $mergedFields);
        $this->assertArrayHasKey('block_title', $mergedFields);
        $this->assertArrayHasKey('group_desc', $mergedFields);
    }

    public function testBlockFieldsTakePrecedenceInMerge(): void
    {
        $blockField = Field::make('text', 'title', 'Block Title')
            ->setRequired(true);

        $groupField = Field::make('text', 'title', 'Group Title')
            ->setRequired(false);

        $block = Block::make('Test Block')
            ->setName('test/block')
            ->addFields([$blockField])
            ->addFieldGroup('content');

        $group = FieldGroup::make('Content Fields', 'content')
            ->addFields([$groupField]);

        $registry = Registry::getInstance();
        $registry->registerFieldGroup($group);

        $mergedFields = $registry->getMergedFields($block);

        $this->assertCount(1, $mergedFields);
        $this->assertTrue($mergedFields['title']->required);
    }

    public function testReset(): void
    {
        $block = Block::make('Test')->setName('test/block');
        $group = FieldGroup::make('Test', 'test');

        $registry = Registry::getInstance();
        $registry->registerFluentBlock($block);
        $registry->registerFieldGroup($group);

        $this->assertTrue($registry->hasFluentBlock('test/block'));
        $this->assertNotNull($registry->getFieldGroup('test'));

        $registry->reset();

        $this->assertFalse($registry->hasFluentBlock('test/block'));
        $this->assertNull($registry->getFieldGroup('test'));
    }

    public function testFindJsonBlockPathReturnsNullWhenNotFound(): void
    {
        $registry = Registry::getInstance();
        $path = $registry->findJsonBlockPath('non-existent/block');

        $this->assertNull($path);
    }
}
