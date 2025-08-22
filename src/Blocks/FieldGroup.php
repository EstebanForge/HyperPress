<?php

declare(strict_types=1);

/**
 * FieldGroup class for the fluent API.
 */

namespace HyperPress\Blocks;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Represents a reusable group of fields.
 */
class FieldGroup
{
    /**
     * The field group name.
     *
     * @var string
     */
    public string $name;

    /**
     * The field group ID.
     *
     * @var string
     */
    public string $id;

    /**
     * The fields in the group.
     *
     * @var Field[]
     */
    public array $fields = [];

    /**
     * Constructor.
     *
     * @param string $name The field group name.
     * @param string $id   The field group ID.
     */
    private function __construct(string $name, string $id)
    {
        $this->name = $name;
        $this->id = $id;
    }

    /**
     * Create a new FieldGroup instance.
     *
     * @param string $name The field group name.
     * @param string $id   The field group ID.
     * @return self
     */
    public static function make(string $name, string $id): self
    {
        return new self($name, $id);
    }

    /**
     * Add fields to the group.
     *
     * @param Field[] $fields An array of Field objects.
     * @return self
     */
    public function addFields(array $fields): self
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }
}
