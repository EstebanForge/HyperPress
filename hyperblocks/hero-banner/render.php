<?php

declare(strict_types=1);

/**
 * Render template for Hero Banner block (block.json approach)
 * 
 * This is the exact same template as the Fluent API version,
 * demonstrating identical output from both approaches.
 * 
 * @package HyperPress
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block attributes
$headline = $attributes['headline'] ?? '';
$subtitle = $attributes['subtitle'] ?? '';
$button_url = $attributes['button_url'] ?? '';
$button_text = $attributes['button_text'] ?? 'Learn More';
$background_color = $attributes['background_color'] ?? '#ffffff';
$text_color = $attributes['text_color'] ?? '#333333';

?>

<div class="hero-banner" style="background-color: <?= esc_attr($background_color) ?>; color: <?= esc_attr($text_color) ?>; padding: 60px 20px; text-align: center; min-height: 400px; display: flex; flex-direction: column; justify-content: center;">
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