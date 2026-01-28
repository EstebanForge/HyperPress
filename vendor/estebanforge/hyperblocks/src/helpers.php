<?php

declare(strict_types=1);

/**
 * HyperBlocks - Helper Functions
 *
 * This file provides convenience functions for working with HyperBlocks.
 *
 * @package HyperBlocks
 */

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\Registry;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create a new Block instance.
 *
 * @param string $title The block title.
 * @return Block
 */
function hyperblocks_block(string $title): Block
{
    return Block::make($title);
}

/**
 * Create a new Field instance.
 *
 * @param string $type  The field type.
 * @param string $name  The field name.
 * @param string $label The field label.
 * @return Field
 */
function hyperblocks_field(string $type, string $name, string $label): Field
{
    return Field::make($type, $name, $label);
}

/**
 * Create a new FieldGroup instance.
 *
 * @param string $name The field group name.
 * @param string $id   The field group ID.
 * @return FieldGroup
 */
function hyperblocks_field_group(string $name, string $id): FieldGroup
{
    return FieldGroup::make($name, $id);
}

/**
 * Get the Registry instance.
 *
 * @return Registry
 */
function hyperblocks_registry(): Registry
{
    return Registry::getInstance();
}

/**
 * Register a block.
 *
 * @param Block $block The block to register.
 * @return void
 */
function hyperblocks_register_block(Block $block): void
{
    Registry::getInstance()->registerFluentBlock($block);
}

/**
 * Register a field group.
 *
 * @param FieldGroup $group The field group to register.
 * @return void
 */
function hyperblocks_register_field_group(FieldGroup $group): void
{
    Registry::getInstance()->registerFieldGroup($group);
}

/**
 * Register a block discovery path.
 *
 * @param string $path The path to register.
 * @return void
 */
function hyperblocks_register_path(string $path): void
{
    \HyperBlocks\Config::registerBlockPath($path);
}

/**
 * Get a configuration value.
 *
 * @param string $key     The configuration key.
 * @param mixed  $default The default value.
 * @return mixed
 */
function hyperblocks_config(string $key, mixed $default = null): mixed
{
    return \HyperBlocks\Config::get($key, $default);
}

/**
 * Render a block template.
 *
 * @param string $template   The template path or string.
 * @param array  $attributes The block attributes.
 * @return string The rendered HTML.
 */
function hyperblocks_render(string $template, array $attributes = []): string
{
    $renderer = new \HyperBlocks\Renderer();
    return $renderer->render($template, $attributes);
}

/**
 * Check if a block is registered.
 *
 * @param string $blockName The block name.
 * @return bool
 */
function hyperblocks_has_block(string $blockName): bool
{
    return Registry::getInstance()->hasFluentBlock($blockName);
}

/**
 * Get a registered block.
 *
 * @param string $blockName The block name.
 * @return Block|null
 */
function hyperblocks_get_block(string $blockName): ?Block
{
    return Registry::getInstance()->getFluentBlock($blockName);
}
