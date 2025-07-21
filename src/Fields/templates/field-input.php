<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'text';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$placeholder = $field_data['placeholder'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$options = $field_data['options'] ?? [];
?>

<div class="hmapi-field-wrapper">
    <label for="<?php echo esc_attr($name); ?>" class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <?php switch ($type): ?>
            <?php case 'text': ?>
                <input type="text" 
                       id="<?php echo esc_attr($name); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       placeholder="<?php echo esc_attr($placeholder); ?>" 
                       <?php echo $required ? 'required' : ''; ?>
                       class="regular-text">
                <?php break; ?>

            <?php case 'textarea': ?>
                <textarea id="<?php echo esc_attr($name); ?>" 
                          name="<?php echo esc_attr($name); ?>" 
                          placeholder="<?php echo esc_attr($placeholder); ?>" 
                          <?php echo $required ? 'required' : ''; ?>
                          class="large-text" 
                          rows="4"><?php echo esc_textarea($value); ?></textarea>
                <?php break; ?>

            <?php case 'number': ?>
                <input type="number" 
                       id="<?php echo esc_attr($name); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       placeholder="<?php echo esc_attr($placeholder); ?>" 
                       <?php echo $required ? 'required' : ''; ?>
                       class="regular-text">
                <?php break; ?>

            <?php case 'email': ?>
                <input type="email" 
                       id="<?php echo esc_attr($name); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       placeholder="<?php echo esc_attr($placeholder); ?>" 
                       <?php echo $required ? 'required' : ''; ?>
                       class="regular-text">
                <?php break; ?>

            <?php case 'url': ?>
                <input type="url" 
                       id="<?php echo esc_attr($name); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       placeholder="<?php echo esc_attr($placeholder); ?>" 
                       <?php echo $required ? 'required' : ''; ?>
                       class="regular-text">
                <?php break; ?>

            <?php case 'color': ?>
                <input type="color" 
                       id="<?php echo esc_attr($name); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       <?php echo $required ? 'required' : ''; ?>
                       class="hmapi-color-picker">
                <?php break; ?>

            <?php case 'select': ?>
                <select id="<?php echo esc_attr($name); ?>" 
                        name="<?php echo esc_attr($name); ?>" 
                        <?php echo $required ? 'required' : ''; ?>
                        class="regular-text">
                    <?php foreach ($options as $option_value => $option_label): ?>
                        <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                            <?php echo esc_html($option_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php break; ?>

            <?php case 'checkbox': ?>
                <label>
                    <input type="checkbox" 
                           id="<?php echo esc_attr($name); ?>" 
                           name="<?php echo esc_attr($name); ?>" 
                           value="1" 
                           <?php checked($value, '1'); ?>
                           <?php echo $required ? 'required' : ''; ?>>
                    <?php echo esc_html($label); ?>
                </label>
                <?php break; ?>

            <?php case 'radio': ?>
                <?php foreach ($options as $option_value => $option_label): ?>
                    <label>
                        <input type="radio" 
                               name="<?php echo esc_attr($name); ?>" 
                               value="<?php echo esc_attr($option_value); ?>" 
                               <?php checked($value, $option_value); ?>
                               <?php echo $required ? 'required' : ''; ?>>
                        <?php echo esc_html($option_label); ?>
                    </label>
                <?php endforeach; ?>
                <?php break; ?>

            <?php case 'image': ?>
                <div class="hmapi-image-field">
                    <input type="hidden" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
                    <button type="button" class="button hmapi-upload-button" data-field="<?php echo esc_attr($name); ?>">
                        <?php _e('Select Image', 'hmapi'); ?>
                    </button>
                    <div class="hmapi-image-preview">
                        <?php if ($value): ?>
                            <img src="<?php echo esc_url(wp_get_attachment_url($value)); ?>" alt="" style="max-width: 150px; max-height: 150px;">
                        <?php endif; ?>
                    </div>
                </div>
                <?php break; ?>

            <?php case 'file': ?>
                <div class="hmapi-file-field">
                    <input type="url" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" 
                           value="<?php echo esc_attr($value); ?>" 
                           placeholder="<?php echo esc_attr($placeholder); ?>" 
                           <?php echo $required ? 'required' : ''; ?>
                           class="regular-text">
                    <button type="button" class="button hmapi-upload-button" data-field="<?php echo esc_attr($name); ?>" data-type="file">
                        <?php _e('Select File', 'hmapi'); ?>
                    </button>
                </div>
                <?php break; ?>

            <?php case 'wysiwyg': ?>
                <?php 
                wp_editor(
                    $value,
                    $name,
                    [
                        'textarea_name' => $name,
                        'textarea_rows' => 10,
                        'media_buttons' => true,
                        'teeny' => true,
                    ]
                );
                ?>
                <?php break; ?>

        <?php endswitch; ?>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>