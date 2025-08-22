<?php

declare(strict_types=1);

/**
 * Fluent API Demo Blocks
 * 
 * This file demonstrates creating blocks using the Fluent API approach.
 * 
 * @package HyperPress
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use HyperPress\Blocks\Registry;
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Field;
use HyperPress\Blocks\FieldGroup;

$registry = Registry::getInstance();

// Create a reusable field group for common styling options
$stylingGroup = FieldGroup::make('Styling Options', 'styling-options')
    ->addFields([
        Field::make('color', 'background_color', 'Background Color')
            ->setDefault('#ffffff')
            ->setHelp('Choose a background color for the block'),
        Field::make('color', 'text_color', 'Text Color')
            ->setDefault('#333333')
            ->setHelp('Choose a text color for the block'),
    ]);

$registry->registerFieldGroup($stylingGroup);

// Demo Block 1: Hero Banner with RichText
$heroBlock = Block::make('HB Hero Banner')
    ->setIcon('format-image')
    ->addFields([
        Field::make('text', 'headline', 'Headline')
            ->setPlaceholder('Enter your headline')
            ->setRequired(true)
            ->setHelp('Main headline for the hero section'),
        Field::make('textarea', 'subtitle', 'Subtitle')
            ->setPlaceholder('Enter subtitle text')
            ->setHelp('Supporting text for the hero section'),
        Field::make('url', 'button_url', 'Button URL')
            ->setPlaceholder('https://example.com')
            ->setHelp('URL for the call-to-action button'),
        Field::make('text', 'button_text', 'Button Text')
            ->setDefault('Learn More')
            ->setHelp('Text for the call-to-action button'),
    ])
    ->addFieldGroup('styling-options')
    ->setRenderTemplate(<<<'HTML'
        <div class="hero-banner" style="background-color: <?= $background_color ?? "#ffffff" ?>; color: <?= $text_color ?? "#333333" ?>; padding: 60px 20px; text-align: center; min-height: 400px; display: flex; flex-direction: column; justify-content: center;">
            <RichText attribute="headline" tag="h1" placeholder="Enter your headline" style="font-size: 3rem; margin-bottom: 1rem; font-weight: bold;" />
            <RichText attribute="subtitle" tag="p" placeholder="Enter subtitle text" style="font-size: 1.5rem; margin-bottom: 2rem; opacity: 0.8;" />
            <?php if (!empty($button_url) && !empty($button_text)): ?>
                <div>
                    <a href="<?= esc_url($button_url) ?>" 
                       class="hero-button" 
                       style="display: inline-block; padding: 15px 30px; background-color: #007cba; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background-color 0.3s;">
                        <?= esc_html($button_text) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
HTML);

$registry->registerFluentBlock($heroBlock);

// Demo Block 2: Card with Image and InnerBlocks
$cardBlock = Block::make('HB Content Card')
    ->setIcon('table-col-before')
    ->addFields([
        Field::make('text', 'card_title', 'Card Title')
            ->setPlaceholder('Enter card title')
            ->setRequired(true)
            ->setHelp('Title for the card'),
        Field::make('url', 'image_url', 'Image URL')
            ->setPlaceholder('https://example.com/image.jpg')
            ->setHelp('URL of the image to display'),
        Field::make('text', 'image_alt', 'Image Alt Text')
            ->setPlaceholder('Descriptive alt text')
            ->setHelp('Alternative text for the image'),
    ])
    ->addFieldGroup('styling-options')
    ->setRenderTemplate(<<<'HTML'
        <div class="content-card" style="background-color: <?= $background_color ?? "#ffffff" ?>; color: <?= $text_color ?? "#333333" ?>; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 400px; margin: 20px auto;">
            <?php if (!empty($image_url)): ?>
                <div class="card-image" style="width: 100%; height: 200px; overflow: hidden;">
                    <img src="<?= esc_url($image_url) ?>" 
                         alt="<?= esc_attr($image_alt ?? $card_title) ?>" 
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            <?php endif; ?>
            <div class="card-content" style="padding: 20px;">
                <RichText attribute="card_title" tag="h3" placeholder="Enter card title" style="font-size: 1.5rem; margin-bottom: 1rem; font-weight: bold;" />
                <div class="card-inner-content" style="margin-top: 1rem;">
                    <InnerBlocks />
                </div>
            </div>
        </div>
HTML);

$registry->registerFluentBlock($cardBlock);

// Demo Block 3: Simple Quote Block
$quoteBlock = Block::make('HB Quote Block')
    ->setIcon('format-quote')
    ->addFields([
        Field::make('textarea', 'quote_text', 'Quote Text')
            ->setPlaceholder('Enter the quote text')
            ->setRequired(true)
            ->setHelp('The main quote content'),
        Field::make('text', 'quote_author', 'Quote Author')
            ->setPlaceholder('Author name')
            ->setHelp('Name of the person being quoted'),
        Field::make('text', 'author_title', 'Author Title')
            ->setPlaceholder('Job title or description')
            ->setHelp('Title or description of the quote author'),
    ])
    ->addFieldGroup('styling-options')
    ->setRenderTemplate(<<<'HTML'
        <blockquote class="quote-block" style="background-color: <?= $background_color ?? "#f9f9f9" ?>; color: <?= $text_color ?? "#333333" ?>; padding: 30px; margin: 20px 0; border-left: 4px solid #007cba; position: relative;">
            <div style="font-size: 1.2rem; line-height: 1.6; margin-bottom: 1rem; font-style: italic;">
                <RichText attribute="quote_text" tag="p" placeholder="Enter the quote text" style="margin: 0; position: relative; z-index: 1;" />
            </div>
            <?php if (!empty($quote_author)): ?>
                <footer style="font-size: 0.9rem; color: #666; margin-top: 1rem;">
                    <strong><?= esc_html($quote_author) ?></strong>
                    <?php if (!empty($author_title)): ?>
                        <span>, <?= esc_html($author_title) ?></span>
                    <?php endif; ?>
                </footer>
            <?php endif; ?>
            <div style="position: absolute; top: 10px; right: 15px; font-size: 4rem; color: rgba(0,124,186,0.1); line-height: 1; font-family: serif;">"</div>
        </blockquote>
HTML);

$registry->registerFluentBlock($quoteBlock);
