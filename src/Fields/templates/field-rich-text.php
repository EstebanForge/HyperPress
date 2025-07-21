<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'rich_text';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';

// Editor settings
$editor_settings = [
    'textarea_name' => $name,
    'textarea_rows' => 10,
    'media_buttons' => true,
    'teeny' => false,
    'quicktags' => true,
    'tinymce' => [
        'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,pastetext,removeformat',
        'toolbar2' => 'formatselect,|,outdent,indent,|,undo,redo',
        'toolbar3' => '',
    ],
];

// Allow customization via filter
$editor_settings = apply_filters('hmapi_rich_text_editor_settings', $editor_settings, $name);
?>

<div class="hmapi-field-wrapper">
    <label for="<?php echo esc_attr($name); ?>" class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <?php wp_editor($value, $name, $editor_settings); ?>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>