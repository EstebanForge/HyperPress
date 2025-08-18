<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr(json_encode($conditional_logic)) . '\'';
}

$type = $field_data['type'] ?? 'html';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$html_content = $field_data['html_content'] ?? $value;
$help = $field_data['help'] ?? '';
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <?php if ($label): ?>
        <div class="hyperpress-field-label">
            <strong><?php echo esc_html($label); ?></strong>
        </div>
    <?php endif; ?>

    <div class="hyperpress-field-input">
        <div class="hyperpress-html-content">
            <?php
            // For HTML fields, we allow unescaped content since it's intended to be raw HTML
            // This is safe because HTML fields are only used by developers who control the content
            echo $html_content;
?>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>
