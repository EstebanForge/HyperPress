<?php
/**
 * Example: Using Field Groups
 *
 * This demonstrates how to create reusable field groups and use them in multiple blocks.
 *
 * @package HyperBlocks\Examples
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
 * Create a reusable content field group
 */
$contentFieldGroup = FieldGroup::make('Content Fields', 'content')
    ->setDescription('Common fields for content blocks')
    ->addFields([
        Field::make('text', 'title', 'Title')
            ->setRequired(true)
            ->setDefault('Default Title'),

        Field::make('textarea', 'description', 'Description')
            ->setDefault('Default description text')
            ->setHelp('Enter a brief description'),

        Field::make('image', 'thumbnail', 'Thumbnail Image')
            ->setHelp('Select a thumbnail image'),
    ]);

/**
 * Create another reusable field group for settings
 */
$settingsFieldGroup = FieldGroup::make('Block Settings', 'settings')
    ->setDescription('Common settings for layout blocks')
    ->addFields([
        Field::make('select', 'alignment', 'Text Alignment')
            ->setOptions([
                'left' => 'Left',
                'center' => 'Center',
                'right' => 'Right',
            ])
            ->setDefault('center'),

        Field::make('select', 'padding', 'Padding Size')
            ->setOptions([
                'small' => 'Small',
                'medium' => 'Medium',
                'large' => 'Large',
            ])
            ->setDefault('medium'),

        Field::make('checkbox', 'show_border', 'Show Border')
            ->setDefault(false),

        Field::make('color', 'background_color', 'Background Color')
            ->setDefault('#ffffff'),
    ]);

/**
 * Register the field groups
 */
Registry::getInstance()->registerFieldGroup($contentFieldGroup);
Registry::getInstance()->registerFieldGroup($settingsFieldGroup);

/**
 * Create blocks that use the field groups
 */

// Block 1: Feature Card
$featureCardBlock = Block::make('Feature Card')
    ->setName('hyperblocks-examples/feature-card')
    ->setIcon('media-text')
    ->setDescription('A feature card with content and settings.')
    ->addFieldGroup('content')
    ->addFieldGroup('settings')
    ->addFields([
        Field::make('text', 'icon', 'Icon Class')
            ->setDefault('dashicons-star-filled')
            ->setHelp('Enter a Dashicon class name'),
    ])
    ->setRenderTemplateFile('examples/blocks/feature-card.hb.php');

// Block 2: Content Box
$contentBoxBlock = Block::make('Content Box')
    ->setName('hyperblocks-examples/content-box')
    ->setIcon('box')
    ->setDescription('A simple content box with content and settings.')
    ->addFieldGroup('content')
    ->addFieldGroup('settings')
    ->setRenderTemplateFile('examples/blocks/content-box.hb.php');

/**
 * Register the blocks
 */
Registry::getInstance()->registerFluentBlock($featureCardBlock);
Registry::getInstance()->registerFluentBlock($contentBoxBlock);
