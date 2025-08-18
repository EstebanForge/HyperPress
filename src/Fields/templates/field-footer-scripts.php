<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'footer_scripts';
$name = $field_data['name'] ?? '';
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $conditional_attr = ' data-hp-conditional-logic="' . esc_attr(json_encode($conditional_logic)) . '"';
}
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <textarea id="<?php echo esc_attr($name); ?>" 
                  name="<?php echo esc_attr($name_attr); ?>" 
                  <?php echo $required ? 'required' : ''; ?>
                  class="large-text code" 
                  rows="10"
                  placeholder="&lt;script&gt;
  // Your JavaScript code here
&lt;/script&gt;"><?php echo esc_textarea($value); ?></textarea>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>