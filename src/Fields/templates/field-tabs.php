<?php
if (!defined('ABSPATH')) {
    exit;
}

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data["conditional_logic"] ?? null;
$conditional_attr = "";
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
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

// Set active tab from URL if available
if (isset($_GET['tab']) && isset($tabs[$_GET['tab']])) {
    $active_tab = sanitize_text_field($_GET['tab']);
}

$layout_class = 'hyperpress-tabs-' . $layout;
?>

<div class="hyperpress-tabs-wrapper <?php echo esc_attr($layout_class); ?>">
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_id => $tab):
            $is_active = $tab_id === $active_tab;
            $tab_url = add_query_arg('tab', $tab_id);
            ?>
            <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="hyperpress-tabs-content">
        <?php
        // Render only the active tab's content
        if (isset($tabs[$active_tab])) {
            $active_tab_data = $tabs[$active_tab];
            if (!empty($active_tab_data['fields'])) {
                echo '<div class="hyperpress-tab-fields-wrapper">';
                foreach ($active_tab_data['fields'] as $field) {
                    if (is_object($field) && method_exists($field, 'render')) {
                        $field->render();
                    } else {
                        // Legacy handling for array-based fields
                        $template_path = __DIR__ . '/field-' . $field['type'] . '.php';
                        if (file_exists($template_path)) {
                            include $template_path;
                        } else {
                            include __DIR__ . '/field-input.php';
                        }
                    }
                }
                echo '</div>';
            }
        }
?>
    </div>

    <?php if ($help): ?>
        <p class="description"><?php echo esc_html($help); ?></p>
    <?php endif; ?>
</div>
