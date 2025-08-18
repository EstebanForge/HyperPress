<?php

declare(strict_types=1);

/**
 * Render template for Quote Block (block.json approach)
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
$quote_text = $attributes['quote_text'] ?? '';
$quote_author = $attributes['quote_author'] ?? '';
$author_title = $attributes['author_title'] ?? '';
$background_color = $attributes['background_color'] ?? '#f9f9f9';
$text_color = $attributes['text_color'] ?? '#333333';

?>

<blockquote class="quote-block" style="background-color: <?= esc_attr($background_color) ?>; color: <?= esc_attr($text_color) ?>; padding: 30px; margin: 20px 0; border-left: 4px solid #007cba; position: relative;">
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