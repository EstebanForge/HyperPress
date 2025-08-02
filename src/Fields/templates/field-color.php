<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'color';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
?>

<div class="hmapi-field-wrapper">
    <label for="<?php echo esc_attr($name); ?>" class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <input type="color" 
               id="<?php echo esc_attr($name); ?>" 
               name="<?php echo esc_attr($name); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               <?php echo $required ? 'required' : ''; ?>
               class="hmapi-color-picker">

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>