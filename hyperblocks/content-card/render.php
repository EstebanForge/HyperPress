<?php

declare(strict_types=1);

/**
 * Render template for Content Card block (block.json approach)
 * 
 * This is the exact same template as the Fluent API version,
 * demonstrating identical output from both approaches.
 * 
 * @package HMApi
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get block attributes
$card_title = $attributes['card_title'] ?? '';
$image_url = $attributes['image_url'] ?? '';
$image_alt = $attributes['image_alt'] ?? '';
$background_color = $attributes['background_color'] ?? '#ffffff';
$text_color = $attributes['text_color'] ?? '#333333';

?>

<div class="content-card" style="background-color: <?= esc_attr($background_color) ?>; color: <?= esc_attr($text_color) ?>; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 400px; margin: 20px auto;">
    <?php if (!empty($image_url)): ?>
        <div class="card-image" style="width: 100%; height: 200px; overflow: hidden;">
            <img src="<?= esc_url($image_url) ?>" 
                 alt="<?= esc_attr($image_alt ?: $card_title) ?>" 
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