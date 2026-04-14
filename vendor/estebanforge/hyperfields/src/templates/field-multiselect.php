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

$type = $field_data['type'] ?? 'multiselect';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? [];
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$help_is_html = $field_data['help_is_html'] ?? false;
$options = $field_data['options'] ?? [];
$input_class = trim((string) ($field_data['input_class'] ?? ''));
$label_class = trim((string) ($field_data['label_class'] ?? ''));
$error = isset($field_data['error']) && is_string($field_data['error']) ? $field_data['error'] : '';

// Check if enhanced multiselect is enabled (default true)
$enhanced_enabled = isset($field_data['enhanced']) ? (bool) $field_data['enhanced'] : true;

$value = is_array($value) ? $value : [$value];
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label <?php echo esc_attr($label_class); ?>">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <?php if ($enhanced_enabled): ?>
        <!-- Enhanced Multiselect Interface -->
        <div class="hf-multiselect-container" data-field-name="<?php echo esc_attr($name); ?>">
            <input type="text"
                   class="hf-multiselect-search regular-text"
                   placeholder="<?php esc_attr_e('Search options...', 'hyperfields'); ?>"
                   autocomplete="off" />

            <div class="hf-multiselect-selected">
                <?php
                $selected_count = 0;
            foreach ($options as $option_value => $option_label):
                if (in_array($option_value, $value)):
                    $selected_count++;
                    ?>
                    <span class="hf-multiselect-tag" data-value="<?php echo esc_attr($option_value); ?>">
                        <?php echo esc_html($option_label); ?>
                        <span class="hf-multiselect-remove">&times;</span>
                    </span>
                <?php
                endif;
            endforeach;

            if ($selected_count === 0): ?>
                    <span class="hf-multiselect-placeholder"><?php esc_html_e('No items selected', 'hyperfields'); ?></span>
                <?php endif; ?>
            </div>

            <div class="hf-multiselect-options" style="display: none;">
                <?php foreach ($options as $option_value => $option_label): ?>
                    <div class="hf-multiselect-option <?php echo in_array($option_value, $value) ? 'selected' : ''; ?>"
                         data-value="<?php echo esc_attr($option_value); ?>">
                        <?php echo esc_html($option_label); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Standard HTML Multiselect -->
        <select id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name_attr); ?>[]"
                multiple
                <?php echo $required ? 'required' : ''; ?>
                class="regular-text hyperpress-multiselect <?php echo esc_attr($input_class); ?>"
                size="5">
            <?php foreach ($options as $option_value => $option_label): ?>
                <option value="<?php echo esc_attr($option_value); ?>" <?php selected(in_array($option_value, $value)); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

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
