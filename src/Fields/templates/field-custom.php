<?php
// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data["conditional_logic"] ?? null;
$conditional_attr = "";
if ($conditional_logic) {
    // Encode as JSON and safely embed as a single-quoted attribute value
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
}

if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'custom';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$render_callback = $field_data['render_callback'] ?? '';
$assets = $field_data['assets'] ?? [];

// Load custom assets
if (!empty($assets)) {
    foreach ($assets as $asset) {
        if (is_string($asset)) {
            if (pathinfo($asset, PATHINFO_EXTENSION) === 'css') {
                wp_enqueue_style('hyperpress-custom-' . sanitize_key(basename($asset, '.css')), $asset);
            } elseif (pathinfo($asset, PATHINFO_EXTENSION) === 'js') {
                wp_enqueue_script('hyperpress-custom-' . sanitize_key(basename($asset, '.js')), $asset, ['jquery'], null, true);
            }
        }
    }
}

// Use custom render callback if provided
if (!empty($render_callback) && is_callable($render_callback)) {
    call_user_func($render_callback, $field_data, $value);
} else {
    // Fallback to basic input
    ?>
    <div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
        <div class="hyperpress-field-row">
            <div class="hyperpress-field-label">
                <label for="<?php echo esc_attr($name); ?>">
                    <?php echo esc_html($label); ?>
                    <?php if ($required): ?><span class="required">*</span><?php endif; ?>
                </label>
            </div>
            <div class="hyperpress-field-input-wrapper">
                <input type="text"
                       id="<?php echo esc_attr($name); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr($value); ?>"
                       <?php echo $required ? 'required' : ''; ?>
                       class="regular-text">

                <?php if ($help): ?>
                    <p class="description"><?php echo esc_html($help); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>
