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

$type = $field_data['type'] ?? 'rich_text';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$help_is_html = $field_data['help_is_html'] ?? false;
$error = isset($field_data['error']) && is_string($field_data['error']) ? $field_data['error'] : '';
$editor_settings_overrides = isset($field_data['args']['editor_settings']) && is_array($field_data['args']['editor_settings'])
    ? $field_data['args']['editor_settings']
    : [];

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

if ($editor_settings_overrides !== []) {
    $editor_settings = array_merge($editor_settings, $editor_settings_overrides);
}

// Allow customization via filter
$editor_settings = apply_filters('hyperfields/rich_text_editor_settings', $editor_settings, $name);
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <?php wp_editor($value, $name, $editor_settings); ?>

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
