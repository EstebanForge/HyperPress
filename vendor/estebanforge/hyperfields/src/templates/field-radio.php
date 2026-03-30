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

$type = $field_data['type'] ?? 'radio';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$help_is_html = $field_data['help_is_html'] ?? false;
$options = $field_data['options'] ?? [];
$layout = $field_data['layout'] ?? 'vertical';
$input_class = trim((string) ($field_data['input_class'] ?? ''));
$label_class = trim((string) ($field_data['label_class'] ?? ''));
$error = isset($field_data['error']) && is_string($field_data['error']) ? $field_data['error'] : '';

$layout_class = 'hyperpress-radio-' . $layout;
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label class="hyperpress-field-label <?php echo esc_attr($label_class); ?>">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <div class="<?php echo esc_attr($layout_class); ?>">
            <?php foreach ($options as $option_value => $option_label): ?>
                <label>
                    <input type="radio" 
                           name="<?php echo esc_attr($name_attr); ?>" 
                           value="<?php echo esc_attr($option_value); ?>" 
                           class="<?php echo esc_attr($input_class); ?>"
                           <?php checked($value, $option_value); ?>
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php echo esc_html($option_label); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <?php if ($help): ?>
            <p class="description">
                <?php if ($help_is_html): ?>
                    <?php echo wp_kses_post($help); ?>
                <?php else: ?>
                    <?php echo esc_html($help); ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="description" style="color:#d63638;"><?php echo esc_html($error); ?></p>
        <?php endif; ?>
    </div>
</div>
