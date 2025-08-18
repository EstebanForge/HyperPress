<?php
if (!defined('ABSPATH')) {
    exit;
}

$label = $field_data['label'] ?? '';
$help = $field_data['help'] ?? '';

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
}
?>

<div class="hyperpress-field-wrapper hyperpress-heading-wrapper"<?php echo $conditional_attr; ?>>
    <?php if ($label) : ?>
        <h2 class="hyperpress-heading-label"><?php echo esc_html($label); ?></h2>
    <?php endif; ?>

    <?php if ($help) : ?>
        <p class="description"><?php echo wp_kses_post($help); ?></p>
    <?php endif; ?>
</div>
