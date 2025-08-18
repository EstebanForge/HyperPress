<?php
<?php
// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data["conditional_logic"] ?? null;
$conditional_attr = "";
if ($conditional_logic) {
    $conditional_attr = " data-hp-conditional-logic="" . esc_attr(json_encode($conditional_logic)) . """;
}
?>
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'sidebar';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? '';
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';

// Get all registered sidebars
$sidebars = $GLOBALS['wp_registered_sidebars'] ?? [];

// Sort sidebars by name
uksort($sidebars, function ($a, $b) use ($sidebars) {
    return strcasecmp($sidebars[$a]['name'], $sidebars[$b]['name']);
});

// Add default option
$sidebars = ['' => ['name' => __('— Select —', 'api-for-htmx')]] + $sidebars;
?>

<div class="hyperpress-field-wrapper"<?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <select id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                <?php echo $required ? 'required' : ''; ?>
                class="regular-text">
            <?php foreach ($sidebars as $sidebar_id => $sidebar): ?>
                <option value="<?php echo esc_attr($sidebar_id); ?>" <?php selected($value, $sidebar_id); ?>>
                    <?php echo esc_html($sidebar['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>
