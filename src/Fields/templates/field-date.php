<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'date';
$name = $field_data['name'] ?? '';
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
        <input type="date" 
               id="<?php echo esc_attr($name); ?>" 
               name="<?php echo esc_attr($name); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               placeholder="<?php echo esc_attr($placeholder); ?>" 
               <?php echo $required ? 'required' : ''; ?>
               class="regular-text hmapi-date-picker">

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>