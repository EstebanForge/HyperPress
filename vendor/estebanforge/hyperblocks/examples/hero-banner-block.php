<?php
/**
 * Example: Hero Banner Block
 *
 * This demonstrates how to create a HyperBlocks block using the fluent API.
 *
 * @package HyperBlocks\Examples
 */

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the Hero Banner block
 */
$heroBannerBlock = Block::make('Hero Banner')
    ->setName('hyperblocks-examples/hero-banner')
    ->setIcon('cover-image')
    ->setDescription('A hero banner with heading, subheading, and background image.')
    ->addFields([
        Field::make('text', 'heading', 'Heading')
            ->setDefault('Welcome to Our Website')
            ->setPlaceholder('Enter your heading text')
            ->setRequired(true),

        Field::make('textarea', 'subheading', 'Subheading')
            ->setDefault('Create amazing blocks with HyperBlocks')
            ->setPlaceholder('Enter a subheading or description'),

        Field::make('image', 'background_image', 'Background Image')
            ->setHelp('Select an image for the hero background'),

        Field::make('color', 'overlay_color', 'Overlay Color')
            ->setDefault('rgba(0,0,0,0.5)')
            ->setHelp('Choose an overlay color for better text readability'),

        Field::make('text', 'cta_text', 'CTA Button Text')
            ->setDefault('Learn More')
            ->setPlaceholder('Enter button text'),

        Field::make('url', 'cta_link', 'CTA Button Link')
            ->setPlaceholder('https://example.com'),
    ])
    ->setRenderTemplateFile('examples/blocks/hero-banner.hb.php');

// Register with HyperBlocks
Registry::getInstance()->registerFluentBlock($heroBannerBlock);
