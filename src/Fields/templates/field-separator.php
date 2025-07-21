<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'separator';
$label = $field_data['label'] ?? '';
$help = $field_data['help'] ?? '';
?>

<div class="hmapi-field-wrapper hmapi-separator-wrapper">
    <hr class="hmapi-separator">
    
    <?php if ($label): ?>
        <h4 class="hmapi-separator-label"><?php echo esc_html($label); ?></h4>
    <?php endif; ?>

    <?php if ($help): ?>
        <p class="description"><?php echo esc_html($help); ?></p>
    <?php endif; ?>
</div>