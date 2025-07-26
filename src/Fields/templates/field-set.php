<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'set';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? [];
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$options = $field_data['options'] ?? [];
$layout = $field_data['layout'] ?? 'vertical';

$value = is_array($value) ? $value : [$value];
$layout_class = 'hmapi-set-' . $layout;
?>

<div class="hmapi-field-wrapper">
    <label class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <!-- Hidden input to ensure an empty array is sent when no checkboxes are selected -->
        <input type="hidden" name="<?php echo esc_attr($name); ?>[]" value="">
        <div class="<?php echo esc_attr($layout_class); ?>">
            <?php foreach ($options as $option_value => $option_label): ?>
                <label>
                    <input type="checkbox" 
                           name="<?php echo esc_attr($name); ?>[]" 
                           value="<?php echo esc_attr($option_value); ?>" 
                           <?php checked(in_array($option_value, $value)); ?>
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