<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Registry.
 *
 * Provides backward compatibility without extending the final class.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Config;
use HyperBlocks\Registry as HyperBlocksRegistry;
use HyperBlocks\Block\Block as HyperBlocksBlock;
use HyperBlocks\Block\FieldGroup as HyperBlocksFieldGroup;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for Registry, wrapping HyperBlocks\Registry.
 */
final class Registry
{
    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Proxy registry instance.
     *
     * @var HyperBlocksRegistry
     */
    private HyperBlocksRegistry $proxy;

    /**
     * Private constructor.
     */
    private function __construct()
    {
        $this->proxy = HyperBlocksRegistry::getInstance();
    }

    /**
     * Get the single instance of the Registry.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the registry with HyperPress-specific hooks.
     *
     * @return void
     */
    public function init(): void
    {
        if (defined('HYPERPRESS_TEMPLATE_EXT')) {
            Config::set('template_extensions', (string) HYPERPRESS_TEMPLATE_EXT);
        }

        if (class_exists(\HyperBlocks\WordPress\Bootstrap::class)) {
            \HyperBlocks\WordPress\Bootstrap::init();
        }

        $this->registerHyperPressBlocksPath();
    }

    /**
     * Register a fluent block.
     *
     * @param Block|HyperBlocksBlock $block
     * @return void
     */
    public function registerFluentBlock($block): void
    {
        if ($block instanceof Block) {
            $this->proxy->registerFluentBlock($block->getProxy());
            return;
        }

        if ($block instanceof HyperBlocksBlock) {
            $this->proxy->registerFluentBlock($block);
        }
    }

    /**
     * Get a fluent block by name.
     *
     * @param string $blockName
     * @return Block|null
     */
    public function getFluentBlock(string $blockName): ?Block
    {
        $block = $this->proxy->getFluentBlock($blockName);
        if (!$block) {
            return null;
        }

        return Block::fromProxy($block);
    }

    /**
     * Get all fluent blocks.
     *
     * @return Block[]
     */
    public function getFluentBlocks(): array
    {
        $blocks = $this->proxy->getFluentBlocks();
        $wrapped = [];
        foreach ($blocks as $block) {
            $wrapped[] = Block::fromProxy($block);
        }

        return $wrapped;
    }

    /**
     * Check if a fluent block exists.
     *
     * @param string $blockName
     * @return bool
     */
    public function hasFluentBlock(string $blockName): bool
    {
        return $this->proxy->hasFluentBlock($blockName);
    }

    /**
     * Register a field group.
     *
     * @param FieldGroup|HyperBlocksFieldGroup $group
     * @return void
     */
    public function registerFieldGroup($group): void
    {
        if ($group instanceof FieldGroup) {
            $this->proxy->registerFieldGroup($group->getProxy());
            return;
        }

        if ($group instanceof HyperBlocksFieldGroup) {
            $this->proxy->registerFieldGroup($group);
        }
    }

    /**
     * Get a field group by id.
     *
     * @param string $groupId
     * @return FieldGroup|null
     */
    public function getFieldGroup(string $groupId): ?FieldGroup
    {
        $group = $this->proxy->getFieldGroup($groupId);
        if (!$group) {
            return null;
        }

        return FieldGroup::fromProxy($group);
    }

    /**
     * Get all field groups.
     *
     * @return FieldGroup[]
     */
    public function getFieldGroups(): array
    {
        $groups = $this->proxy->getFieldGroups();
        $wrapped = [];
        foreach ($groups as $group) {
            $wrapped[] = FieldGroup::fromProxy($group);
        }

        return $wrapped;
    }

    /**
     * Proxy: generate block attributes.
     *
     * @param Block|HyperBlocksBlock $block
     * @return array
     */
    public function generateBlockAttributes($block): array
    {
        if ($block instanceof Block) {
            $block = $block->getProxy();
        }

        return $this->proxy->generateBlockAttributes($block);
    }

    /**
     * Proxy: get merged fields for a block.
     *
     * @param Block|HyperBlocksBlock $block
     * @return array
     */
    public function getMergedFields($block): array
    {
        if ($block instanceof Block) {
            $block = $block->getProxy();
        }

        return $this->proxy->getMergedFields($block);
    }

    /**
     * Proxy: discover and register JSON blocks.
     *
     * @return array
     */
    public function discoverAndRegisterJsonBlocks(): array
    {
        return $this->proxy->discoverAndRegisterJsonBlocks();
    }

    /**
     * Proxy: discover and load fluent blocks.
     *
     * @return array
     */
    public function discoverAndLoadFluentBlocks(): array
    {
        return $this->proxy->discoverAndLoadFluentBlocks();
    }

    /**
     * Proxy: discover JSON blocks for editor.
     *
     * @return array
     */
    public function discoverJsonBlocksForEditor(): array
    {
        return $this->proxy->discoverJsonBlocksForEditor();
    }

    /**
     * Proxy: find JSON block path by name.
     *
     * @param string $blockName
     * @return string|null
     */
    public function findJsonBlockPath(string $blockName): ?string
    {
        return $this->proxy->findJsonBlockPath($blockName);
    }

    /**
     * Proxy: reset registry.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->proxy->reset();
    }

    /**
     * Expose the underlying proxy for internal use.
     *
     * @return HyperBlocksRegistry
     */
    public function getProxy(): HyperBlocksRegistry
    {
        return $this->proxy;
    }

    /**
     * Register HyperPress-specific block path, if present.
     *
     * @return void
     */
    private function registerHyperPressBlocksPath(): void
    {
        if (!defined('HYPERPRESS_ABSPATH')) {
            return;
        }

        $path = rtrim(HYPERPRESS_ABSPATH, '/\\') . '/hyperblocks';
        if (is_dir($path)) {
            Config::registerBlockPath($path);
        }
    }
}
