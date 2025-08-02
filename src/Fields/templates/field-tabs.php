<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'tabs';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? [];
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$tabs = $field_data['tabs'] ?? [];
$layout = $field_data['layout'] ?? 'horizontal';
$active_tab = $field_data['active_tab'] ?? '';

// Ensure we have an active tab
if (empty($active_tab) && !empty($tabs)) {
    $active_tab = array_key_first($tabs);
}

$layout_class = 'hmapi-tabs-' . $layout;
?>

<div class="hmapi-field-wrapper hmapi-tabs-wrapper <?php echo esc_attr($layout_class); ?>">
    <?php if ($label): ?>
        <label class="hmapi-field-label">
            <?php echo esc_html($label); ?>
            <?php if ($required): ?><span class="required">*</span><?php endif; ?>
        </label>
    <?php endif; ?>

    <div class="hmapi-field-input">
        <div class="hmapi-tabs-container" data-field="<?php echo esc_attr($name); ?>">
            <div class="hmapi-tabs-nav">
                <?php foreach ($tabs as $tab_id => $tab): ?>
                    <button type="button" 
                            class="hmapi-tab-button <?php echo $tab_id === $active_tab ? 'active' : ''; ?>" 
                            data-tab="<?php echo esc_attr($tab_id); ?>"
                            data-field="<?php echo esc_attr($name); ?>">
                        <?php echo esc_html($tab['label']); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="hmapi-tabs-content">
                <?php foreach ($tabs as $tab_id => $tab): ?>
                    <div class="hmapi-tab-panel <?php echo $tab_id === $active_tab ? 'active' : ''; ?>" 
                         data-tab="<?php echo esc_attr($tab_id); ?>"
                         data-field="<?php echo esc_attr($name); ?>">
                        <?php if (!empty($tab['fields'])): ?>
                            <?php foreach ($tab['fields'] as $field): ?>
                                <?php
                                // Render sub-fields
                                $template_path = __DIR__ . '/field-' . $field['type'] . '.php';
                                if (file_exists($template_path)) {
                                    include $template_path;
                                } else {
                                    include __DIR__ . '/field-input.php';
                                }
                                ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>