<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'hidden';
$name = $field_data['name'] ?? '';
$value = $field_data['value'] ?? '';
?>

<input type="hidden" 
       id="<?php echo esc_attr($name); ?>" 
       name="<?php echo esc_attr($name); ?>" 
       value="<?php echo esc_attr($value); ?>">