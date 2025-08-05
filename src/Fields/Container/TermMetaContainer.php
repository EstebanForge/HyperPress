<?php

declare(strict_types=1);

namespace HMApi\Fields\Container;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Term Meta Container for HyperFields
 * Handles metaboxes for taxonomy terms.
 *
 * @since 2025-08-04
 */
class TermMetaContainer extends Container
{
    protected array $taxonomies = [];
    protected array $term_ids = [];
    protected array $term_slugs = [];
    protected int $term_id = 0;
    protected string $taxonomy = '';

    /**
     * Set taxonomies this container should appear on.
     */
    public function where(string $taxonomy): self
    {
        if (!in_array($taxonomy, $this->taxonomies)) {
            $this->taxonomies[] = $taxonomy;
        }

        return $this;
    }

    /**
     * Target specific term by ID.
     */
    public function whereTermId(int $term_id): self
    {
        if (!in_array($term_id, $this->term_ids)) {
            $this->term_ids[] = $term_id;
        }

        return $this;
    }

    /**
     * Target specific term by slug.
     */
    public function whereTermSlug(string $slug): self
    {
        if (!in_array($slug, $this->term_slugs)) {
            $this->term_slugs[] = $slug;
        }

        return $this;
    }

    /**
     * Target multiple terms by IDs.
     */
    public function whereTermIds(array $term_ids): self
    {
        foreach ($term_ids as $term_id) {
            $this->whereTermId($term_id);
        }

        return $this;
    }

    /**
     * Target multiple terms by slugs.
     */
    public function whereTermSlugs(array $slugs): self
    {
        foreach ($slugs as $slug) {
            $this->whereTermSlug($slug);
        }

        return $this;
    }

    /**
     * Initialize the container.
     */
    public function init(): void
    {
        // Set term ID if we're on term edit page
        if (isset($_GET['tag_ID'])) {
            $this->setTermId((int) $_GET['tag_ID']);
        }

        add_action('admin_init', [$this, 'attach']);

        // Hook to taxonomy save actions
        foreach ($this->taxonomies as $taxonomy) {
            add_action("{$taxonomy}_edit_form_fields", [$this, 'render']);
            add_action("edited_{$taxonomy}", [$this, '_save'], 10, 2);
            add_action("created_{$taxonomy}", [$this, '_save'], 10, 2);
        }
    }

    /**
     * Attach is handled by init for term containers.
     */
    public function attach(): void
    {
        // Term containers use form fields instead of meta boxes
        // Attachment is handled in init()
    }

    /**
     * Set the term ID.
     */
    public function setTermId(int $term_id): void
    {
        $this->term_id = $term_id;
        $this->setObjectId($term_id);

        $term = get_term($term_id);
        if ($term && !is_wp_error($term)) {
            $this->taxonomy = $term->taxonomy;
        }
    }

    /**
     * Check if current save request is valid.
     */
    public function isValidSave(): bool
    {
        // Check nonce
        if (!$this->verifyNonce()) {
            return false;
        }

        // Check if we have a valid term ID
        if ($this->term_id <= 0) {
            return false;
        }

        // Check user permissions
        if (!current_user_can('edit_term', $this->term_id)) {
            return false;
        }

        return true;
    }

    /**
     * Save wrapper for WordPress hooks.
     */
    public function _save(int $term_id, int $tt_id = null): void
    {
        $this->setTermId($term_id);

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
        if (!$this->verifyNonce() || $this->term_id <= 0) {
            return;
        }

        foreach ($this->fields as $field) {
            $field_name = $field->get_name();
            $value = $_POST[$field_name] ?? $field->get_default();

            // Sanitize the value
            $value = $this->sanitizeValue($value, $field->get_type());

            // Save as term meta
            update_term_meta($this->term_id, $field_name, $value);
        }

        do_action('hyperfields_term_meta_container_saved', $this->term_id, $this);
    }

    /**
     * Render the container with term meta values.
     */
    public function render($term = null): void
    {
        if ($term && isset($term->term_id)) {
            $this->setTermId($term->term_id);
        }

        // Check if this container should be shown for this specific term
        if (!$this->shouldShowForTerm()) {
            return;
        }

        if ($this->term_id <= 0) {
            // For new terms, we don't have meta yet
            return;
        }

        echo '<tr class="form-field hyperfields-container hyperfields-term-meta-container" data-container-id="' . esc_attr($this->id) . '">';
        echo '<th scope="row"><label>' . esc_html($this->getTitle()) . '</label></th>';
        echo '<td>';

        foreach ($this->fields as $field) {
            // Get term meta value
            $meta_value = get_term_meta($this->term_id, $field->get_name(), true);
            $value = $meta_value !== '' ? $meta_value : $field->get_default();

            // Set context and render
            $field->set_context('metabox');
            $field->render(['value' => $value]);
        }

        // Add nonce field
        wp_nonce_field('hyperfields_metabox_' . $this->id, '_hyperfields_metabox_nonce_' . $this->id);

        echo '</td>';
        echo '</tr>';
    }

    /**
     * Check if this container should be shown for the current term.
     */
    protected function shouldShowForTerm(): bool
    {
        // If no specific targeting, show for all terms in specified taxonomies
        if (empty($this->term_ids) && empty($this->term_slugs)) {
            return true;
        }

        // If we don't have a term ID yet, we can't check specific targeting
        if ($this->term_id <= 0) {
            return true; // Allow for new terms
        }

        // Check if current term ID matches targeted IDs
        if (!empty($this->term_ids) && in_array($this->term_id, $this->term_ids)) {
            return true;
        }

        // Check if current term slug matches targeted slugs
        if (!empty($this->term_slugs)) {
            $term = get_term($this->term_id);
            if ($term && !is_wp_error($term) && in_array($term->slug, $this->term_slugs)) {
                return true;
            }
        }

        return false;
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
