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

$type = $field_data['type'] ?? 'rich_text';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? __('', 'api-for-htmx');

// Editor settings
$editor_settings = [
    'textarea_name' => $name_attr,
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
$editor_settings = apply_filters('hyperpress_rich_text_editor_settings', $editor_settings, $name);
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <?php wp_editor($value, $name, $editor_settings); ?>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>
