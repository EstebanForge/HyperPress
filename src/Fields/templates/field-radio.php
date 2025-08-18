<?php
<?php
// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data["conditional_logic"] ?? null;
$conditional_attr = "";
if ($conditional_logic) {
    $conditional_attr = " data-hp-conditional-logic="" . esc_attr(json_encode($conditional_logic)) . """;
}
?>
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'radio';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$options = $field_data['options'] ?? [];
$layout = $field_data['layout'] ?? 'vertical';

$layout_class = 'hyperpress-radio-' . $layout;
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label class="hyperpress-field-label">
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
                           <?php checked($value, $option_value); ?>
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php echo esc_html($option_label); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>