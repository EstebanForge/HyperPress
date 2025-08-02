<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'html';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$html_content = $field_data['html_content'] ?? $value;
$help = $field_data['help'] ?? '';
?>

<div class="hmapi-field-wrapper">
    <?php if ($label): ?>
        <div class="hmapi-field-label">
            <strong><?php echo esc_html($label); ?></strong>
        </div>
    <?php endif; ?>

    <div class="hmapi-field-input">
        <div class="hmapi-html-content">
            <?php echo wp_kses_post($html_content); ?>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>