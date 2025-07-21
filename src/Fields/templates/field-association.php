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

// Get posts based on post type
$posts = get_posts([
    'post_type' => $post_type,
    'posts_per_page' => -1,
    'post_status' => 'publish',
]);

$value = is_array($value) ? $value : [$value];
?>

<div class="hmapi-field-wrapper">
    <label for="<?php echo esc_attr($name); ?>" class="hmapi-field-label">
        <?php echo esc_html($label); ?>
        <?php if ($required): ?><span class="required">*</span><?php endif; ?>
    </label>

    <div class="hmapi-field-input">
        <select id="<?php echo esc_attr($name); ?>" 
                name="<?php echo esc_attr($name); ?><?php echo $multiple ? '[]' : ''; ?>" 
                <?php echo $multiple ? 'multiple' : ''; ?>
                <?php echo $required ? 'required' : ''; ?>
                class="regular-text">
            <option value=""><?php _e('Select...', 'hmapi'); ?></option>
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