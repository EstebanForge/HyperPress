<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'checkbox';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? false;
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
?>

<div class="hmapi-field-wrapper">
    <div class="hmapi-field-input">
        <!-- Hidden input to ensure the field is always sent in POST data -->
        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="0">
        <label>
            <input type="checkbox" 
                   id="<?php echo esc_attr($name); ?>" 
                   name="<?php echo esc_attr($name); ?>" 
                   value="1" 
                   <?php checked($value, '1'); ?>
                   <?php echo $required ? 'required' : ''; ?>>
            <?php echo esc_html($label); ?>
            <?php if ($required): ?><span class="required">*</span><?php endif; ?>
        </label>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>