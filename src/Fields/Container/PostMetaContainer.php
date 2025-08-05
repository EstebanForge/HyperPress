<?php

declare(strict_types=1);

namespace HMApi\Fields\Container;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Meta Container for HyperFields
 * Handles metaboxes for post types.
 *
 * @since 2025-08-04
 */
class PostMetaContainer extends Container
{
    protected array $post_types = [];
    protected array $post_ids = [];
    protected array $post_slugs = [];
    protected int $post_id = 0;

    /**
     * Get default settings for post meta containers.
     */
    protected function getDefaultSettings(): array
    {
        return [
            'context' => 'normal',
            'priority' => 'high',
        ];
    }

    /**
     * Set post types this container should appear on.
     */
    public function where(string $post_type): self
    {
        if (!in_array($post_type, $this->post_types)) {
            $this->post_types[] = $post_type;
        }

        return $this;
    }

    /**
     * Target specific post by ID.
     */
    public function wherePostId(int $post_id): self
    {
        if (!in_array($post_id, $this->post_ids)) {
            $this->post_ids[] = $post_id;
        }

        return $this;
    }

    /**
     * Target specific post by slug.
     */
    public function wherePostSlug(string $slug): self
    {
        if (!in_array($slug, $this->post_slugs)) {
            $this->post_slugs[] = $slug;
        }

        return $this;
    }

    /**
     * Target multiple posts by IDs.
     */
    public function wherePostIds(array $post_ids): self
    {
        foreach ($post_ids as $post_id) {
            $this->wherePostId($post_id);
        }

        return $this;
    }

    /**
     * Target multiple posts by slugs.
     */
    public function wherePostSlugs(array $slugs): self
    {
        foreach ($slugs as $slug) {
            $this->wherePostSlug($slug);
        }

        return $this;
    }

    /**
     * Set metabox context (normal, side, advanced).
     */
    public function setContext(string $context): self
    {
        $this->setSetting('context', $context);

        return $this;
    }

    /**
     * Set metabox priority (high, core, default, low).
     */
    public function setPriority(string $priority): self
    {
        $this->setSetting('priority', $priority);

        return $this;
    }

    /**
     * Initialize the container.
     */
    public function init(): void
    {
        // Set post ID if we're on post edit page
        global $pagenow;
        if (in_array($pagenow, ['post.php', 'post-new.php'])) {
            $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
            if ($post_id > 0) {
                $this->setPostId($post_id);
            }
        }

        add_action('add_meta_boxes', [$this, 'attach']);
        add_action('save_post', [$this, '_save']);
        add_action('add_attachment', [$this, '_save']);
        add_action('edit_attachment', [$this, '_save']);
    }

    /**
     * Attach metabox to appropriate post types.
     */
    public function attach(): void
    {
        // If specific post IDs or slugs are targeted, we need different logic
        if (!empty($this->post_ids) || !empty($this->post_slugs)) {
            $this->attachToSpecificPosts();

            return;
        }

        // Standard post type targeting
        foreach ($this->post_types as $post_type) {
            add_meta_box(
                $this->getId(),
                $this->getTitle(),
                [$this, 'render'],
                $post_type,
                $this->settings['context'],
                $this->settings['priority']
            );
        }
    }

    /**
     * Attach metabox to specific posts by ID or slug.
     */
    protected function attachToSpecificPosts(): void
    {
        // Get current post ID
        $current_post_id = 0;
        global $pagenow;

        if ($pagenow === 'post.php' && isset($_GET['post'])) {
            $current_post_id = (int) $_GET['post'];
        } elseif ($pagenow === 'post-new.php' && isset($_GET['post_type'])) {
            // For new posts, we can only check post type
            $post_type = sanitize_text_field($_GET['post_type']);
            if (in_array($post_type, $this->post_types)) {
                add_meta_box(
                    $this->getId(),
                    $this->getTitle(),
                    [$this, 'render'],
                    $post_type,
                    $this->settings['context'],
                    $this->settings['priority']
                );
            }

            return;
        }

        if ($current_post_id <= 0) {
            return;
        }

        $should_show = false;

        // Check if current post ID matches targeted IDs
        if (!empty($this->post_ids) && in_array($current_post_id, $this->post_ids)) {
            $should_show = true;
        }

        // Check if current post slug matches targeted slugs
        if (!empty($this->post_slugs)) {
            $post = get_post($current_post_id);
            if ($post && in_array($post->post_name, $this->post_slugs)) {
                $should_show = true;
            }
        }

        // Also check post type if specified
        if (!empty($this->post_types)) {
            $post_type = get_post_type($current_post_id);
            if (!in_array($post_type, $this->post_types)) {
                $should_show = false;
            }
        }

        if ($should_show) {
            $post_type = get_post_type($current_post_id);
            add_meta_box(
                $this->getId(),
                $this->getTitle(),
                [$this, 'render'],
                $post_type,
                $this->settings['context'],
                $this->settings['priority']
            );
        }
    }

    /**
     * Set the post ID.
     */
    public function setPostId(int $post_id): void
    {
        $this->post_id = $post_id;
        $this->setObjectId($post_id);
    }

    /**
     * Check if current save request is valid.
     */
    public function isValidSave(): bool
    {
        // Check for autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        // Check nonce
        if (!$this->verifyNonce()) {
            return false;
        }

        // Check user permissions
        $post_id = isset($_POST['post_ID']) ? (int) $_POST['post_ID'] : 0;
        if ($post_id <= 0) {
            return false;
        }

        $post_type = get_post_type($post_id);
        if (!$post_type) {
            return false;
        }

        $post_type_object = get_post_type_object($post_type);
        if (!current_user_can($post_type_object->cap->edit_post, $post_id)) {
            return false;
        }

        return true;
    }

    /**
     * Save wrapper for WordPress hooks.
     */
    public function _save(int $post_id): void
    {
        $this->setPostId($post_id);

        if (!$this->isValidSave()) {
            return;
        }

        $this->save();
    }

    /**
     * Save container fields.
     */
    public function save(): void
    {
        if (!$this->verifyNonce() || $this->post_id <= 0) {
            return;
        }

        foreach ($this->fields as $field) {
            $field_name = $field->get_name();
            $value = $_POST[$field_name] ?? $field->get_default();

            // Sanitize the value
            $value = $this->sanitizeValue($value, $field->get_type());

            // Save as post meta
            update_post_meta($this->post_id, $field_name, $value);
        }

        do_action('hyperfields_post_meta_container_saved', $this->post_id, $this);
    }

    /**
     * Render the container with post meta values.
     */
    public function render(): void
    {
        if ($this->post_id <= 0) {
            global $post;
            if ($post && $post->ID > 0) {
                $this->setPostId($post->ID);
            }
        }

        echo '<div class="hyperfields-container hyperfields-post-meta-container" data-container-id="' . esc_attr($this->id) . '">';

        foreach ($this->fields as $field) {
            // Get post meta value
            $meta_value = get_post_meta($this->post_id, $field->get_name(), true);
            $value = $meta_value !== '' ? $meta_value : $field->get_default();

            // Set context and render
            $field->set_context('metabox');
            $field->render(['value' => $value]);
        }

        echo '</div>';

        // Add nonce field
        wp_nonce_field('hyperfields_metabox_' . $this->id, '_hyperfields_metabox_nonce_' . $this->id);
    }

    /**
     * Sanitize field value based on type.
     */
    protected function sanitizeValue($value, string $type)
    {
        switch ($type) {
            case 'text':
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'checkbox':
                return $value ? 1 : 0;
            case 'select':
            case 'radio':
                return sanitize_text_field($value);
            default:
                return sanitize_text_field($value);
        }
    }
}
