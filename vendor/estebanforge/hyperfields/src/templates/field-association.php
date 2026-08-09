<?php
if (!defined('ABSPATH')) {
    exit;
}

$type = $field_data['type'] ?? 'association';
$name = $field_data['name'] ?? '';
$label = $field_data['label'] ?? '';
$value = $field_data['value'] ?? [];
$required = $field_data['required'] ?? false;
$help = $field_data['help'] ?? '';
$options = $field_data['options'] ?? [];
$post_type = $options['post_type'] ?? 'post';
$multiple = $options['multiple'] ?? false;

// Support for conditional_logic: pass as data-hp-conditional-logic attribute for JS
$conditional_logic = $field_data['conditional_logic'] ?? null;
$conditional_attr = '';
if ($conditional_logic) {
    $json = wp_json_encode($conditional_logic);
    $conditional_attr = ' data-hp-conditional-logic=\'' . esc_attr((string) $json) . '\'';
}

$value = is_array($value) ? $value : [$value];
$value_ids = array_filter(array_map('intval', $value));

// Bounded query so a large post-type table cannot exhaust memory rendering
// this dropdown on every admin screen that shows the field. Term and meta
// cache warming are off because only ID and post_title are used. Sites
// needing the full set should switch to an AJAX-driven search (separate
// feature).
$posts = get_posts([
    'post_type' => $post_type,
    'posts_per_page' => 200,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
    'no_found_rows' => true,
    'update_post_term_cache' => false,
    'update_post_meta_cache' => false,
]);

// Guarantee already-stored selections always render, even when they sort
// beyond the 200-post window or are non-published (draft/private/trash).
// Without this, an associated post absent from the <select> would be silently
// cleared on the next save when the browser submits the empty default option.
$loaded_ids = array_map(static fn ($p) => (int) $p->ID, $posts);
$missing_ids = array_values(array_diff($value_ids, $loaded_ids));
if ($missing_ids !== []) {
    $extra = get_posts([
        'post_type' => $post_type,
        'post__in' => $missing_ids,
        'posts_per_page' => count($missing_ids),
        'post_status' => 'any',
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    ]);
    $posts = array_merge($posts, $extra);
    usort($posts, static fn ($a, $b) => strcasecmp($a->post_title, $b->post_title));
}
?>

<div class="hyperpress-field-wrapper" <?php echo $conditional_attr; ?>>
    <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hyperpress-field-input">
        <select id="<?php echo esc_attr($name); ?>"
            name="<?php echo esc_attr($name); ?><?php echo $multiple ? '[]' : ''; ?>"
            <?php echo $multiple ? 'multiple' : ''; ?>
            <?php echo $required ? 'required' : ''; ?>
            class="regular-text">
            <option value=""><?php _e('Select...', 'api-for-htmx'); ?></option>
            <?php foreach ($posts as $post): ?>
                <option value="<?php echo esc_attr($post->ID); ?>"
                    <?php selected(in_array($post->ID, $value)); ?>>
                    <?php echo esc_html($post->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($help): ?>
            <p class="description"><?php echo esc_html($help); ?></p>
        <?php endif; ?>
    </div>
</div>
