<?php

declare(strict_types=1);

namespace HyperPress\Fields\Container;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Meta Container for HyperFields
 * Handles metaboxes for user profiles.
 *
 * @since 2025-08-04
 */
class UserMetaContainer extends Container
{
    protected array $user_roles = [];
    protected array $user_ids = [];
    protected int $user_id = 0;

    /**
     * Set user roles this container should appear for.
     */
    public function where(string $role): self
    {
        if (!in_array($role, $this->user_roles)) {
            $this->user_roles[] = $role;
        }

        return $this;
    }

    /**
     * Target specific user by ID.
     */
    public function whereUserId(int $user_id): self
    {
        if (!in_array($user_id, $this->user_ids)) {
            $this->user_ids[] = $user_id;
        }

        return $this;
    }

    /**
     * Target multiple users by IDs.
     */
    public function whereUserIds(array $user_ids): self
    {
        foreach ($user_ids as $user_id) {
            $this->whereUserId($user_id);
        }

        return $this;
    }

    /**
     * Initialize the container.
     */
    public function init(): void
    {
        // Set user ID if we're on user edit page
        global $pagenow;
        if ($pagenow === 'profile.php') {
            $this->setUserId(get_current_user_id());
        } elseif ($pagenow === 'user-edit.php' && isset($_GET['user_id'])) {
            $this->setUserId((int) $_GET['user_id']);
        }

        add_action('show_user_profile', [$this, 'render']);
        add_action('edit_user_profile', [$this, 'render']);
        add_action('personal_options_update', [$this, '_save']);
        add_action('edit_user_profile_update', [$this, '_save']);
        add_action('user_register', [$this, '_save']);
    }

    /**
     * Attach is not used for user containers.
     */
    public function attach(): void
    {
        // User containers don't use meta boxes, they add to profile forms
        // Attachment is handled in init()
    }

    /**
     * Set the user ID.
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
        $this->setObjectId($user_id);
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

        // Check if we have a valid user ID
        if ($this->user_id <= 0) {
            return false;
        }

        // Check user permissions
        if (!current_user_can('edit_user', $this->user_id)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user has required role (if roles are specified).
     */
    protected function userHasRequiredRole(int $user_id): bool
    {
        // If specific user IDs are targeted, check those first
        if (!empty($this->user_ids)) {
            return in_array($user_id, $this->user_ids);
        }

        // If no role restriction and no specific IDs, show for all users
        if (empty($this->user_roles)) {
            return true;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return !empty(array_intersect($this->user_roles, $user->roles));
    }

    /**
     * Save wrapper for WordPress hooks.
     */
    public function _save(int $user_id): void
    {
        $this->setUserId($user_id);

        if (!$this->isValidSave()) {
            return;
        }

        // Check role restriction
        if (!$this->userHasRequiredRole($user_id)) {
            return;
        }

        $this->save();
    }

    /**
     * Save container fields.
     */
    public function save(): void
    {
        if (!$this->verifyNonce() || $this->user_id <= 0) {
            return;
        }

        foreach ($this->fields as $field) {
            $field_name = $field->getName();
            $value = $_POST[$field_name] ?? $field->getDefault();

            // Sanitize the value
            $value = $field->sanitizeValue($value);

            // Save as user meta
            update_user_meta($this->user_id, $field_name, $value);
        }

        do_action('hyperfields_user_meta_container_saved', $this->user_id, $this);
    }

    /**
     * Render the container with user meta values.
     */
    public function render($user = null): void
    {
        if ($user && isset($user->ID)) {
            $this->setUserId($user->ID);
        }

        // Check role restriction
        if (!$this->userHasRequiredRole($this->user_id)) {
            return;
        }

        echo '<h2>' . esc_html($this->getTitle()) . '</h2>';
        echo '<table class="form-table hyperfields-container hyperfields-user-meta-container" data-container-id="' . esc_attr($this->id) . '">';

        foreach ($this->fields as $field) {
            // Get user meta value
            $meta_value = get_user_meta($this->user_id, $field->getName(), true);
            $value = $meta_value !== '' ? $meta_value : $field->getDefault();

            echo '<tr>';
            echo '<th><label for="' . esc_attr($field->getName()) . '">' . esc_html($field->getLabel()) . '</label></th>';
            echo '<td>';

            // Set context and render
            $field->setContext('metabox');
            $field->render(['value' => $value]);

            if ($field->getHelp()) {
                echo '<p class="description">' . esc_html($field->getHelp()) . '</p>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';

        // Add nonce field
        wp_nonce_field('hyperfields_metabox_' . $this->id, '_hyperfields_metabox_nonce_' . $this->id);
    }
}
