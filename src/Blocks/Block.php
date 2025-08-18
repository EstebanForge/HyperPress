<?php

declare(strict_types=1);

/**
 * Block class for the fluent API.
 */

namespace HyperPress\Blocks;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Represents a Gutenberg block created with the fluent PHP API.
 */
class Block
{
    /**
     * The block's full name (e.g., namespace/block-name).
     *
     * @var string
     */
    public string $name;

    /**
     * The block's title.
     *
     * @var string
     */
    public string $title;

    /**
     * The block's icon.
     *
     * @var string
     */
    public string $icon = 'block-default';

    /**
     * The fields for the block.
     *
     * @var Field[]
     */
    public array $fields = [];

    /**
     * The field groups attached to this block.
     *
     * @var string[]
     */
    public array $field_groups = [];

    /**
     * The render template for the block.
     *
     * @var string
     */
    public string $render_template = '';

    /**
     * Constructor.
     *
     * @param string $title The block title.
     */
    private function __construct(string $title)
    {
        $this->title = $title;
        // Generate a default name from the title if not set later
        $this->name = 'hyperblocks/' . sanitize_title($title);
    }

    /**
     * Create a new Block instance.
     *
     * @param string $title The block title.
     * @return self
     */
    public static function make(string $title): self
    {
        return new self($title);
    }

    /**
     * Set the block name.
     *
     * @param string $name The block name.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the block icon.
     *
     * @param string $iconName A dashicon slug.
     * @return self
     */
    public function setIcon(string $iconName): self
    {
        $this->icon = $iconName;

        return $this;
    }

    /**
     * Add fields to the block.
     *
     * @param Field[] $fields An array of Field objects.
     * @return self
     */
    public function addFields(array $fields): self
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    /**
     * Attach a reusable field group.
     *
     * @param string $groupName The name of the field group.
     * @return self
     */
    public function addFieldGroup(string $groupName): self
    {
        $this->field_groups[] = $groupName;

        return $this;
    }

    /**
     * Set the render template for the block.
     *
     * @param string $templateString The template string or path to a template file.
     * @return self
     */
    public function setRenderTemplate(string $templateString): self
    {
        $this->render_template = $templateString;

        return $this;
    }
}
