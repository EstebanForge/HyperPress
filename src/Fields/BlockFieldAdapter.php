<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class BlockFieldAdapter
{
    private Field $field;
    private array $block_attributes;

    public function __construct(Field $field, array $block_attributes = [])
    {
        $this->field = $field;
        $this->block_attributes = $block_attributes;
    }

    public static function from_field(Field $field, array $block_attributes = []): self
    {
        return new self($field, $block_attributes);
    }

    public function getValue(): mixed
    {
        $field_name = $this->field->getName();
        $default = $this->field->getDefault();

        return $this->block_attributes[$field_name] ?? $default;
    }

    public function setValue(mixed $value): void
    {
        // Block attributes are handled by Gutenberg, not stored directly
        // This method exists for interface consistency
    }

    public function get_field(): Field
    {
        return $this->field;
    }

    public function get_attribute_name(): string
    {
        return $this->field->getName();
    }

    public function to_block_attribute(): array
    {
        $field_type = $this->field->getType();
        $default = $this->field->getDefault();

        // Map field types to WordPress block attribute types
        $type_map = [
            'text' => 'string',
            'textarea' => 'string',
            'number' => 'number',
            'email' => 'string',
            'url' => 'string',
            'color' => 'string',
            'date' => 'string',
            'datetime' => 'string',
            'image' => 'number', // Store as attachment ID
            'file' => 'string', // Store as URL or ID
            'select' => 'string',
            'checkbox' => 'boolean',
            'radio' => 'string',
            'wysiwyg' => 'string',
        ];

        $attribute_type = $type_map[$field_type] ?? 'string';

        return [
            'type' => $attribute_type,
            'default' => $default,
        ];
    }

    public function sanitize_for_block(mixed $value): mixed
    {
        return $this->field->sanitizeValue($value);
    }

    public function validate_for_block(mixed $value): bool
    {
        return $this->field->validateValue($value);
    }
}
