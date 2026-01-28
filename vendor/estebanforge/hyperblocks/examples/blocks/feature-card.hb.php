<?php
/**
 * Feature Card Block Template
 *
 * @var string $title The card title.
 * @var string $description The card description.
 * @var int $thumbnail The thumbnail image ID.
 * @var string $alignment Text alignment.
 * @var string $padding Padding size.
 * @var bool $show_border Whether to show border.
 * @var string $background_color Background color.
 * @var string $icon Icon class.
 */

// Get classes
$alignment_class = 'hb-align-' . $alignment;
$padding_class = 'hb-padding-' . $padding;
$border_class = $show_border ? 'hb-has-border' : '';

// Get thumbnail URL
$thumbnail_url = $thumbnail ? wp_get_attachment_image_url($thumbnail, 'medium') : '';

// Get style
$style_attr = !empty($background_color) ? "background-color: {$background_color};" : '';
?>

<div class="hb-feature-card <?php echo esc_attr($alignment_class . ' ' . $padding_class . ' ' . $border_class); ?>"
     style="<?php echo esc_attr($style_attr); ?>">
    <div class="hb-feature-card-inner">
        <?php if (!empty($icon)): ?>
            <div class="hb-feature-icon">
                <span class="<?php echo esc_attr($icon); ?>"></span>
            </div>
        <?php endif; ?>

        <?php if ($thumbnail_url): ?>
            <div class="hb-feature-thumbnail">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($title); ?>">
            </div>
        <?php endif; ?>

        <h3 class="hb-feature-title"><?php echo esc_html($title); ?></h3>

        <?php if (!empty($description)): ?>
            <p class="hb-feature-description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
    .hb-feature-card {
        border-radius: 8px;
        padding: 0;
        transition: all 0.3s ease;
    }

    .hb-has-border {
        border: 1px solid #e0e0e0;
    }

    .hb-feature-card-inner {
        padding: 24px;
    }

    .hb-padding-small .hb-feature-card-inner {
        padding: 16px;
    }

    .hb-padding-medium .hb-feature-card-inner {
        padding: 24px;
    }

    .hb-padding-large .hb-feature-card-inner {
        padding: 32px;
    }

    .hb-align-left {
        text-align: left;
    }

    .hb-align-center {
        text-align: center;
    }

    .hb-align-right {
        text-align: right;
    }

    .hb-feature-icon {
        font-size: 48px;
        color: #4a90e2;
        margin-bottom: 16px;
    }

    .hb-feature-thumbnail {
        margin-bottom: 16px;
        overflow: hidden;
        border-radius: 4px;
    }

    .hb-feature-thumbnail img {
        width: 100%;
        height: auto;
        display: block;
    }

    .hb-feature-title {
        margin: 0 0 12px;
        font-size: 1.5rem;
        font-weight: 600;
        color: #333333;
    }

    .hb-feature-description {
        margin: 0;
        color: #666666;
        line-height: 1.6;
    }

    .hb-feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
</style>
