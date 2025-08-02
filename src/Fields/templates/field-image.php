<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'image';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$media_library = $field_data['media_library'] ?? true;
?>

<div class="hmapi-field-wrapper">
    <label for="<?php echo esc_attr($name); ?>" class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <div class="hmapi-image-field">
            <input type="hidden" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name_attr); ?>" value="<?php echo esc_attr($value); ?>">
            
            <button type="button" class="button hmapi-upload-button" data-field="<?php echo esc_attr($name); ?>" data-type="image">
                <?php _e('Select Image', 'hmapi'); ?>
            </button>
            
            <button type="button" class="button hmapi-remove-button" data-field="<?php echo esc_attr($name); ?>" style="display: <?php echo $value ? 'inline-block' : 'none'; ?>;">
                <?php _e('Remove Image', 'hmapi'); ?>
            </button>

            <div class="hmapi-image-preview" style="margin-top: 10px;">
                <?php if ($value): ?>
                    <img src="<?php echo esc_url(wp_get_attachment_url($value)); ?>" alt="" style="max-width: 150px; max-height: 150px;">
                <?php endif; ?>
            </div>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>