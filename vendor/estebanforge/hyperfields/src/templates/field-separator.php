<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php
// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
}
?>
<div class="hyperpress-field-wrapper hyperpress-separator-wrapper">
    <hr class="hyperpress-separator" />
</div>