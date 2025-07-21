<?php

declare(strict_types=1);

/**
 * Field class for the fluent API.
 *
 * This class is now a wrapper around the universal HMApi\Fields\Field class,
 * providing backward compatibility for the HyperBlocks API.
 */

namespace HMApi\Blocks;

use HMApi\Fields\Field as UniversalField;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Represents a field within a block.
 */
class Field
{
    /**
     * Supported field types.
     */
    public const FIELD_TYPES = [
        'text',
        'textarea',
        'color',
        'image',
        'url',
    ];

    /**
     * The underlying universal field instance.
     *
     * @var UniversalField
     */
    private UniversalField $universal_field;

    /**
     * Constructor.
     *
     * @param string $type  The field type.
     * @param string $name  The field name.
     * @param string $label The field label.
     * @throws \InvalidArgumentException If the field type is not supported.
     */
    private function __construct(string $type, string $name, string $label)
    {
        if (!in_array($type, self::FIELD_TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported field type: {$type}. Supported types: " . implode(', ', self::FIELD_TYPES));
        }

        $this->universal_field = UniversalField::make($type, $name, $label);
    }

    /**
     * Create a new Field instance.
     *
     * @param string $type  The field type.
     * @param string $name  The field name.
     * @param string $label The field label.
     * @return self
     */
    public static function make(string $type, string $name, string $label): self
    {
        return new self($type, $name, $label);
    }

    /**
     * Set the default value for the field.
     *
     * @param mixed $default The default value.
     * @return self
     */
    public function setDefault($default): self
    {
        $this->universal_field->set_default($default);

        return $this;
    }

    /**
     * Set the placeholder text for the field.
     *
     * @param string $placeholder The placeholder text.
     * @return self
     */
    public function setPlaceholder(string $placeholder): self
    {
        $this->universal_field->set_placeholder($placeholder);

        return $this;
    }

    /**
     * Mark the field as required.
     *
     * @param bool $required Whether the field is required.
     * @return self
     */
    public function setRequired(bool $required = true): self
    {
        $this->universal_field->set_required($required);

        return $this;
    }

    /**
     * Set help text for the field.
     *
     * @param string $help The help text.
     * @return self
     */
    public function setHelp(string $help): self
    {
        $this->universal_field->set_help($help);

        return $this;
    }

    /**
     * Get the field configuration as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->universal_field->to_array();
    }

    /**
     * Get the underlying universal field instance.
     *
     * @return UniversalField
     */
    public function getUniversalField(): UniversalField
    {
        return $this->universal_field;
    }

    /**
     * Magic getter for backward compatibility.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'type':
                return $this->universal_field->get_type();
            case 'name':
                return $this->universal_field->get_name();
            case 'label':
                return $this->universal_field->get_label();
            case 'default':
                return $this->universal_field->get_default();
            case 'placeholder':
                return $this->universal_field->get_placeholder();
            case 'required':
                return $this->universal_field->is_required();
            case 'help':
                return $this->universal_field->get_help();
            default:
                return null;
        }
    }

    /**
     * Magic setter for backward compatibility.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        switch ($name) {
            case 'type':
            case 'name':
            case 'label':
                // These are immutable after construction
                break;
            case 'default':
                $this->universal_field->set_default($value);
                break;
            case 'placeholder':
                $this->universal_field->set_placeholder($value);
                break;
            case 'required':
                $this->universal_field->set_required((bool) $value);
                break;
            case 'help':
                $this->universal_field->set_help($value);
                break;
        }
    }
}
