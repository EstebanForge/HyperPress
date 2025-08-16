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

$label = $field_data['label'] ?? '';
$help = $field_data['help'] ?? '';
?>

<div class="hmapi-field-wrapper hmapi-heading-wrapper">
    <?php if ($label) : ?>
        <h2 class="hmapi-heading-label"><?php echo esc_html($label); ?></h2>
    <?php endif; ?>

    <?php if ($help) : ?>
        <p class="description"><?php echo wp_kses_post($help); ?></p>
    <?php endif; ?>
</div>
