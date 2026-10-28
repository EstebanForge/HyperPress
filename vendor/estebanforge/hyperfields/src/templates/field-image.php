<?php
if (!defined('ABSPATH')) {
    exit;
}

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
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

<div class="hf-field-wrapper hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hf-field-label hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hf-field-input hyperpress-field-input">
        <div class="hf-image-field hyperpress-image-field">
            <input type="hidden" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name_attr); ?>" value="<?php echo esc_attr($value); ?>">

            <button type="button" class="button hf-upload-button hyperpress-upload-button" data-field="<?php echo esc_attr($name); ?>" data-type="image">
                <?php _e('Select Image', 'api-for-htmx'); ?>
            </button>

            <button type="button" class="button hf-remove-button hyperpress-remove-button" data-field="<?php echo esc_attr($name); ?>" style="display: <?php echo $value ? 'inline-block' : 'none'; ?>;">
                <?php _e('Remove Image', 'api-for-htmx'); ?>
            </button>

            <div class="hf-image-preview hyperpress-image-preview<?php echo esc_attr($value ? '' : ' is-empty'); ?>" style="margin-top: 10px;">
                <?php if ($value): ?>
                    <img src="<?php echo esc_url(wp_get_attachment_url($value)); ?>" alt="" style="max-width: 150px; max-height: 150px;">
                <?php else: ?>
                    <span class="hf-image-placeholder hyperpress-image-placeholder"><?php _e('No image selected', 'api-for-htmx'); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>
