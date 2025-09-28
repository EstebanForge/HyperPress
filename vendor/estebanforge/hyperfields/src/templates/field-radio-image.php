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

$type = $field_data['type'] ?? 'radio_image';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$options = $field_data['options'] ?? [];
$layout = $field_data['layout'] ?? 'horizontal';

$layout_class = 'hyperpress-radio-image-' . $layout;
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <div class="<?php echo esc_attr($layout_class); ?>">
            <?php foreach ($options as $option_value => $option_image): ?>
                <label class="hyperpress-radio-image-label">
                    <input type="radio" 
                           name="<?php echo esc_attr($name_attr); ?>" 
                           value="<?php echo esc_attr($option_value); ?>" 
                           <?php checked($value, $option_value); ?>
                           <?php echo $required ? 'required' : ''; ?>>
                    <img src="<?php echo esc_url($option_image); ?>" 
                         alt="<?php echo esc_attr($option_value); ?>" 
                         class="hyperpress-radio-image"
                         style="max-width: 100px; max-height: 100px; cursor: pointer;">
                </label>
            <?php endforeach; ?>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>