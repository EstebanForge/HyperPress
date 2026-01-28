<?php
/**
 * Hero Banner Block Template
 *
 * @var string $heading The main heading text.
 * @var string $subheading The subheading or description.
 * @var int $background_image The background image attachment ID.
 * @var string $overlay_color The overlay color (rgba format).
 * @var string $cta_text The CTA button text.
 * @var string $cta_link The CTA button URL.
 */

// Get image URL
$background_url = wp_get_attachment_image_url($background_image, 'full') ?: '';

// Get default overlay if not set
$overlay_style = !empty($overlay_color) ? "background-color: {$overlay_color};" : '';

// Prepare CTA button
$show_cta = !empty($cta_text) && !empty($cta_link);
?>

<section class="hb-hero-banner" style="background-image: url('<?php echo esc_url($background_url); ?>');">
    <div class="hb-hero-overlay" style="<?php echo esc_attr($overlay_style); ?>"></div>
    <div class="hb-hero-content">
        <div class="hb-hero-container">
            <h1 class="hb-hero-heading"><?php echo esc_html($heading); ?></h1>

            <?php if (!empty($subheading)): ?>
                <p class="hb-hero-subheading"><?php echo esc_html($subheading); ?></p>
            <?php endif; ?>

            <?php if ($show_cta): ?>
                <a href="<?php echo esc_url($cta_link); ?>" class="hb-hero-cta">
                    <?php echo esc_html($cta_text); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .hb-hero-banner {
        position: relative;
        min-height: 80vh;
        display: flex;
        align-items: center;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .hb-hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1;
    }

    .hb-hero-content {
        position: relative;
        z-index: 2;
        width: 100%;
    }

    .hb-hero-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 20px;
        text-align: center;
        color: #ffffff;
    }

    .hb-hero-heading {
        font-size: clamp(2.5rem, 5vw, 4.5rem);
        font-weight: 700;
        margin: 0 0 20px;
        line-height: 1.2;
    }

    .hb-hero-subheading {
        font-size: clamp(1.125rem, 2vw, 1.5rem);
        margin: 0 0 40px;
        opacity: 0.9;
        line-height: 1.6;
    }

    .hb-hero-cta {
        display: inline-block;
        padding: 16px 40px;
        background-color: #ffffff;
        color: #333333;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.125rem;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .hb-hero-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
</style>
