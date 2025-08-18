<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'hidden';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$value = $field_data['value'] ?? '';

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $conditional_attr = ' data-hp-conditional-logic="' . esc_attr(json_encode($conditional_logic)) . '"';
}
?>

<input type="hidden" 
       id="<?php echo esc_attr($name); ?>" 
       name="<?php echo esc_attr($name_attr); ?>" 
       value="<?php echo esc_attr($value); ?>"<?php echo $conditional_attr; ?>>