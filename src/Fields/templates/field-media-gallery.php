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

$type = $field_data['type'] ?? 'media_gallery';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? [];
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$multiple = $field_data['multiple'] ?? true;

$value = is_array($value) ? $value : [$value];
$attachments = [];
foreach ($value as $attachment_id) {
    if ($attachment_id) {
        $attachments[] = get_post($attachment_id);
    }
}
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <div class="hyperpress-media-gallery-field">
            <input type="hidden" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name_attr); ?>" value="<?php echo esc_attr(implode(',', $value)); ?>">

            <button type="button" class="button hyperpress-gallery-button" data-field="<?php echo esc_attr($name); ?>" data-multiple="<?php echo $multiple ? 'true' : 'false'; ?>">
                <?php _e('Add Images', 'api-for-htmx'); ?>
            </button>

            <button type="button" class="button hyperpress-clear-gallery-button" data-field="<?php echo esc_attr($name); ?>" style="display: <?php echo !empty($attachments) ? 'inline-block' : 'none'; ?>">
                <?php _e('Clear Gallery', 'api-for-htmx'); ?>
            </button>

            <div class="hyperpress-gallery-preview" style="margin-top: 10px;">
                <?php if (!empty($attachments)): ?>
                    <?php foreach ($attachments as $attachment): ?>
                        <div class="hyperpress-gallery-item" data-id="<?php echo esc_attr($attachment->ID); ?>" style="display: inline-block; margin: 0 10px 10px 0;">
                            <?php echo wp_get_attachment_image($attachment->ID, 'thumbnail', false, ['style' => 'max-width: 100px; max-height: 100px;']); ?>
                            <button type="button" class="hyperpress-remove-image" data-id="<?php echo esc_attr($attachment->ID); ?>" style="display: block; margin-top: 5px;">
                                <?php _e('Remove', 'api-for-htmx'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>
