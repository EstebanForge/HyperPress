<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'text';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$placeholder = $field_data['placeholder'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$options = $field_data['options'] ?? [];

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
}
?>

<div class="hyperpress-field-wrapper" <?php echo $conditional_attr; ?>>
    <div class="hyperpress-field-row">
        <div class="hyperpress-field-label">
            <label for="<?php echo esc_attr($name); ?>">
                <?php echo esc_html($label); ?>
                <?php if ($required): ?><span class="required">*</span><?php endif; ?>
            </label>
        </div>
        <div class="hyperpress-field-input-wrapper">
            <?php switch ($type):
                case 'text': ?>
                    <input type="text"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                <?php break;
                case 'textarea': ?>
                    <textarea id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="large-text"
                        rows="4"><?php echo esc_textarea($value); ?></textarea>
                <?php break;
                case 'number': ?>
                    <input type="number"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                <?php break;
                case 'email': ?>
                    <input type="email"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                <?php break;
                case 'url': ?>
                    <input type="url"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                <?php break;
                case 'color': ?>
                    <input type="color"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="hyperpress-color-picker">
                <?php break;
                case 'date': ?>
                    <input type="date"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                <?php break;
                case 'datetime': ?>
                    <input type="datetime-local"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                <?php break;
                case 'time': ?>
                    <input type="time"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                <?php break;
                case 'select': ?>
                    <select id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                        <?php foreach ($options as $option_value => $option_label): ?>
                            <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                                <?php echo esc_html($option_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php break;
                case 'multiselect': ?>
                    <select id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>[]"
                        multiple
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                        <?php foreach ($options as $option_value => $option_label): ?>
                            <option value="<?php echo esc_attr($option_value); ?>" <?php selected(is_array($value) && in_array($option_value, $value)); ?>>
                                <?php echo esc_html($option_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php break;
                case 'checkbox': ?>
                    <!-- Hidden input to ensure the field is always sent in POST data -->
                    <input type="hidden" name="<?php echo esc_attr($name_attr); ?>" value="0">
                    <label>
                        <input type="checkbox"
                            id="<?php echo esc_attr($name); ?>"
                            name="<?php echo esc_attr($name_attr); ?>"
                            value="1"
                            <?php checked($value, '1'); ?>
                            <?php echo $required ? 'required' : ''; ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                <?php break;
                case 'radio': ?>
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
                <?php break;
                case 'hidden': ?>
                    <input type="hidden"
                        id="<?php echo esc_attr($name); ?>"
                        name="<?php echo esc_attr($name_attr); ?>"
                        value="<?php echo esc_attr($value); ?>">
                <?php break;
                case 'html': ?>
                    <div class="hyperpress-html-field">
                        <?php echo wp_kses_post($field_data['html_content'] ?? ''); ?>
                    </div>
                <?php break;
                case 'rich_text':
                case 'wysiwyg':
                    wp_editor(
                        $value,
                        $name,
                        [
                            'textarea_name' => $name_attr,
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny' => true,
                        ]
                    );
                    ?>
                <?php break;
                case 'image': ?>
                    <div class="hyperpress-image-field">
                        <input type="hidden" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name_attr); ?>" value="<?php echo esc_attr($value); ?>">
                        <button type="button" class="button hyperpress-upload-button" data-field="<?php echo esc_attr($name); ?>" data-type="image">
                            <?php _e('Select Image', 'api-for-htmx'); ?>
                        </button>
                        <div class="hyperpress-image-preview">
                            <?php if ($value): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_url($value)); ?>" alt="" style="max-width: 150px; max-height: 150px;">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php break;
                case 'file': ?>
                    <div class="hyperpress-file-field">
                        <input type="url" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name_attr); ?>"
                            value="<?php echo esc_attr($value); ?>"
                            placeholder="<?php echo esc_attr($placeholder); ?>"
                            <?php echo $required ? 'required' : ''; ?>
                            class="regular-text">
                        <button type="button" class="button hyperpress-upload-button" data-field="<?php echo esc_attr($name); ?>" data-type="file">
                            <?php _e('Select File', 'api-for-htmx'); ?>
                        </button>
                    </div>
                <?php break;
                case 'set': ?>
                    <div class="hyperpress-set-field">
                        <!-- Hidden input to ensure the field is always sent in POST data even when none selected -->
                        <input type="hidden" name="<?php echo esc_attr($name_attr); ?>[]" value="__hm_empty__">
                        <?php foreach ($options as $option_value => $option_label): ?>
                            <label>
                                <input type="checkbox"
                                    name="<?php echo esc_attr($name_attr); ?>[]"
                                    value="<?php echo esc_attr($option_value); ?>"
                                    <?php checked(is_array($value) && in_array($option_value, $value)); ?>
                                    <?php echo $required ? 'required' : ''; ?>>
                                <?php echo esc_html($option_label); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
            <?php break;
            endswitch; ?>

            <?php if ($help): ?>
                <p class="description"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
    </div>
