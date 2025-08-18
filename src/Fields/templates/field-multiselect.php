<?php
if (!defined('ABSPATH')) {
    exit;
}

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data["conditional_logic"] ?? null;
$conditional_attr = "";
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
}

$type = $field_data['type'] ?? 'multiselect';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? [];
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$options = $field_data['options'] ?? [];

$value = is_array($value) ? $value : [$value];
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <select id="<?php echo esc_attr($name); ?>" 
                name="<?php echo esc_attr($name_attr); ?>[]" 
                multiple
                <?php echo $required ? 'required' : ''; ?>
                class="regular-text hyperpress-multiselect"
                size="5">
            <?php foreach ($options as $option_value => $option_label): ?>
                <option value="<?php echo esc_attr($option_value); ?>" <?php selected(in_array($option_value, $value)); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>