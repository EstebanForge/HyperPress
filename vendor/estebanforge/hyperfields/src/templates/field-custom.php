<?php
// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
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
$name_attr = $field_data['name_attr'] ?? $name;
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$help_is_html = $field_data['help_is_html'] ?? false;
$error = isset($field_data['error']) && is_string($field_data['error']) ? $field_data['error'] : '';
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
                       name="<?php echo esc_attr($name_attr); ?>"
                       value="<?php echo esc_attr($value); ?>"
                       <?php echo $required ? 'required' : ''; ?>
                       class="regular-text">

                <?php if ($help): ?>
                    <p class="description">
                        <?php if ($help_is_html): ?>
                            <?php echo wp_kses_post($help); ?>
                        <?php else: ?>
                            <?php echo esc_html($help); ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p class="description" style="color:#d63638;"><?php echo esc_html($error); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>
