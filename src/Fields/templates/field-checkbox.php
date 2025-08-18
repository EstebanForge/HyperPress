<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'checkbox';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? false;
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $conditional_attr = ' data-hp-conditional-logic="' . esc_attr(json_encode($conditional_logic)) . '"';
}
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <div class="hyperpress-field-row">
        <div class="hyperpress-field-label">
            <label for="<?php echo esc_attr($name); ?>">
                <?php echo esc_html($label); ?>
            </label>
        </div>
        <div class="hyperpress-field-input-wrapper">
            <!-- Hidden input to ensure the field is always sent in POST data -->
            <input type="hidden" name="<?php echo esc_attr($name_attr); ?>" value="0">
            <label>
                <input type="checkbox" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name_attr); ?>" value="1" <?php checked($value, '1'); ?> <?php echo $required ? 'required' : ''; ?>>
                <?php if ($help): ?>
                    <span class="description"><?php echo esc_html($help); ?></span>
                <?php endif; ?>
            </label>
        </div>
    </div>
</div>