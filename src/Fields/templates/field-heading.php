<?php
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
