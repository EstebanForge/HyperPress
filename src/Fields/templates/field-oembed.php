<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'oembed';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$placeholder = $field_data['placeholder'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
?>

<div class="hmapi-field-wrapper">
    <label for="<?php echo esc_attr($name); ?>" class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <input type="url" 
               id="<?php echo esc_attr($name); ?>" 
               name="<?php echo esc_attr($name_attr); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               placeholder="<?php echo esc_attr($placeholder); ?>" 
               <?php echo $required ? 'required' : ''; ?>
               class="regular-text hmapi-oembed-input">
        
        <button type="button" class="button hmapi-embed-preview-button" data-field="<?php echo esc_attr($name); ?>">
            <?php _e('Preview', 'hmapi'); ?>
        </button>

        <div class="hmapi-embed-preview" style="margin-top: 10px;">
            <?php if ($value): ?>
                <?php echo wp_oembed_get($value); ?>
            <?php endif; ?>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>