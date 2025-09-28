<?php

declare(strict_types=1);

/**
 * HyperField class for the fluent API.
 *
 * This class is now a wrapper around the hyper HyperFields\Field class,
 * providing backward compatibility for the HyperBlocks API.
 */

namespace HyperPress\Blocks;

use HyperFields\Field as HyperField;

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
     * The underlying hyper fields instance.
     *
     * @var HyperField
     */
    private HyperField $hyperField;

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
            throw new \InvalidArgumentException("Unsupported field type: {$type}. Supported types: " . esc_html(implode(', ', self::FIELD_TYPES)));
        }

        $this->hyperField = HyperField::make($type, $name, $label);
    }

    /**
     * Create a new HyperField instance.
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
        $this->hyperField->setDefault($default);

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
        $this->hyperField->setPlaceholder($placeholder);

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
        $this->hyperField->setRequired($required);

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
        $this->hyperField->setHelp($help);

        return $this;
    }

    /**
     * Get the field configuration as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->hyperField->toArray();
    }

    /**
     * Get the underlying hyper fields instance.
     *
     * @return HyperField
     */
    public function getHyperField(): HyperField
    {
        return $this->hyperField;
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
                return $this->hyperField->getType();
            case 'name':
                return $this->hyperField->getName();
            case 'label':
                return $this->hyperField->getLabel();
            case 'default':
                return $this->hyperField->getDefault();
            case 'placeholder':
                return $this->hyperField->getPlaceholder();
            case 'required':
                return $this->hyperField->isRequired();
            case 'help':
                return $this->hyperField->getHelp();
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
                $this->hyperField->setDefault($value);
                break;
            case 'placeholder':
                $this->hyperField->setPlaceholder($value);
                break;
            case 'required':
                $this->hyperField->setRequired((bool) $value);
                break;
            case 'help':
                $this->hyperField->setHelp($value);
                break;
        }
    }
}
