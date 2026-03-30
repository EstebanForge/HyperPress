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
$help_is_html = $field_data['help_is_html'] ?? false;
$input_class = trim((string) ($field_data['input_class'] ?? ''));
$label_class = trim((string) ($field_data['label_class'] ?? ''));
$error = isset($field_data['error']) && is_string($field_data['error']) ? $field_data['error'] : '';

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
            <label for="<?php echo esc_attr($name); ?>" class="<?php echo esc_attr($label_class); ?>">
                <?php echo esc_html($label); ?>
            </label>
        </div>
        <div class="hyperpress-field-input-wrapper">
            <!-- Hidden input to ensure the field is always sent in POST data -->
            <input type="hidden" name="<?php echo esc_attr($name_attr); ?>" value="0">
            <label>
                <input type="checkbox" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name_attr); ?>" value="1" class="<?php echo esc_attr($input_class); ?>" <?php checked($value, '1'); ?> <?php echo $required ? 'required' : ''; ?>>
                <?php if ($help): ?>
                    <span class="description">
                        <?php if ($help_is_html): ?>
                            <?php echo wp_kses_post($help); ?>
                        <?php else: ?>
                            <?php echo esc_html($help); ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </label>
            <?php if ($error): ?>
                <p class="description" style="color:#d63638;"><?php echo esc_html($error); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
