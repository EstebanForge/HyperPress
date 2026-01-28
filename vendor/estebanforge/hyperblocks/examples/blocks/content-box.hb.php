<?php
/**
 * Content Box Block Template
 *
 * @var string $title The box title.
 * @var string $description The box description.
 * @var int $thumbnail The thumbnail image ID.
 * @var string $alignment Text alignment.
 * @var string $padding Padding size.
 * @var bool $show_border Whether to show border.
 * @var string $background_color Background color.
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

<div class="hb-content-box <?php echo esc_attr($alignment_class . ' ' . $padding_class . ' ' . $border_class); ?>"
     style="<?php echo esc_attr($style_attr); ?>">
    <div class="hb-content-box-inner">
        <?php if ($thumbnail_url): ?>
            <div class="hb-box-thumbnail">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($title); ?>">
            </div>
        <?php endif; ?>

        <?php if (!empty($title)): ?>
            <h3 class="hb-box-title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>

        <?php if (!empty($description)): ?>
            <div class="hb-box-description">
                <?php echo wp_kses_post($description); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .hb-content-box {
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .hb-has-border {
        border: 2px solid #e0e0e0;
    }

    .hb-content-box-inner {
        padding: 32px;
    }

    .hb-padding-small .hb-content-box-inner {
        padding: 16px;
    }

    .hb-padding-medium .hb-content-box-inner {
        padding: 32px;
    }

    .hb-padding-large .hb-content-box-inner {
        padding: 48px;
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

    .hb-box-thumbnail {
        margin-bottom: 20px;
        overflow: hidden;
        border-radius: 4px;
    }

    .hb-align-center .hb-box-thumbnail {
        margin-left: auto;
        margin-right: auto;
    }

    .hb-align-right .hb-box-thumbnail {
        margin-left: auto;
    }

    .hb-box-thumbnail img {
        width: 100%;
        height: auto;
        display: block;
    }

    .hb-box-title {
        margin: 0 0 16px;
        font-size: 1.75rem;
        font-weight: 700;
        color: #333333;
    }

    .hb-box-description {
        color: #666666;
        line-height: 1.7;
    }

    .hb-content-box:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
</style>
