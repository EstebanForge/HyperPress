<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'hidden';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$value = $field_data['value'] ?? '';
?>

<input type="hidden" 
       id="<?php echo esc_attr($name); ?>" 
       name="<?php echo esc_attr($name_attr); ?>" 
       value="<?php echo esc_attr($value); ?>">