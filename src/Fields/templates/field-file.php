<?php
<?php
// Support for conditional_logic: pass as data-hm-conditional-logic attribute for JS
$conditional_logic = $field_data["conditional_logic"] ?? null;
$conditional_attr = "";
if ($conditional_logic) {
    $conditional_attr = " data-hm-conditional-logic="" . esc_attr(json_encode($conditional_logic)) . """;
}
?>
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'file';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$placeholder = $field_data['placeholder'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
?>

<div class="hmapi-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <div class="hmapi-file-field">
            <input type="url" 
                   id="<?php echo esc_attr($name); ?>" 
                   name="<?php echo esc_attr($name_attr); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   placeholder="<?php echo esc_attr($placeholder); ?>" 
                   <?php echo $required ? 'required' : ''; ?>
                   class="regular-text hmapi-file-url">
            <button type="button" class="button hmapi-upload-button" data-field="<?php echo esc_attr($name); ?>" data-type="file">
                <?php _e('Select File', 'hmapi'); ?>
            </button>
        </div>

        <?php if ($value): ?>
            <div class="hmapi-file-preview">
                <a href="<?php echo esc_url($value); ?>" target="_blank">
                    <?php echo esc_html(basename($value)); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>