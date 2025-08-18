<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'date';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$placeholder = $field_data['placeholder'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
}
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <div class="hyperpress-field-row">
        <div class="hyperpress-field-label">
            <label for="<?php echo esc_attr($name); ?>">
                <?php echo esc_html($label); ?>
                <?php if ($required): ?><span class="required">*</span><?php endif; ?>
            </label>
        </div>
        <div class="hyperpress-field-input-wrapper">
            <input type="date" 
                   id="<?php echo esc_attr($name); ?>" 
                   name="<?php echo esc_attr($name_attr); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   placeholder="<?php echo esc_attr($placeholder); ?>" 
                   <?php echo $required ? 'required' : ''; ?>
                   class="regular-text hyperpress-date-picker">

            <?php if ($help): ?>
                <p class="description"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>