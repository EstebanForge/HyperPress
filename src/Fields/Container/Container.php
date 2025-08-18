<?php

declare(strict_types=1);

namespace HyperPress\Fields\Container;

use HyperPress\Fields\Field;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Container class for HyperFields metaboxes
 * Provides common functionality for all container types.
 *
 * @since 2025-08-04
 */
abstract class Container
{
    protected string $id;
    protected string $title;
    protected array $fields = [];
    protected array $settings = [];
    protected int $object_id = 0;

    public function __construct(string $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;
        $this->settings = $this->getDefaultSettings();
    }

    /**
     * Get default settings for the container.
     */
    protected function getDefaultSettings(): array
    {
        return [];
    }

    /**
     * Add a field to the container.
     */
    public function addField(Field $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Set container setting.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * Get container ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get container title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get container fields.
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Set object ID the container is working with.
     */
    public function setObjectId(int $object_id): void
    {
        $this->object_id = $object_id;
    }

    /**
     * Render the container.
     */
    public function render(): void
    {
        echo '<div class="hyperfields-container" data-container-id="' . esc_attr($this->id) . '">';

        foreach ($this->fields as $field) {
            // Set the meta key context for the field
            $field->setContext('metabox');
            $field->render();
        }

        echo '</div>';

        // Add nonce field
        wp_nonce_field('hyperfields_metabox_' . $this->id, '_hyperfields_metabox_nonce_' . $this->id);
    }

    /**
     * Verify nonce for save operation.
     */
    protected function verifyNonce(): bool
    {
        $nonce_key = '_hyperfields_metabox_nonce_' . $this->id;

        return isset($_POST[$nonce_key]) && wp_verify_nonce($_POST[$nonce_key], 'hyperfields_metabox_' . $this->id);
    }

    /**
     * Save container fields - abstract implementation
     * Each subclass will implement its own save logic.
     */
    abstract public function save(): void;

    /**
     * Initialize the container - must be implemented by subclasses.
     */
    abstract public function init(): void;

    /**
     * Attach the container - must be implemented by subclasses.
     */
    abstract public function attach(): void;

    /**
     * Check if current save request is valid - must be implemented by subclasses.
     */
    abstract public function isValidSave(): bool;
}
