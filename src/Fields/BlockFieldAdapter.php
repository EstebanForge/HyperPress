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

    public function get_value(): mixed
    {
        $field_name = $this->field->get_name();
        $default = $this->field->get_default();

        return $this->block_attributes[$field_name] ?? $default;
    }

    public function set_value(mixed $value): void
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
        return $this->field->get_name();
    }

    public function to_block_attribute(): array
    {
        $field_type = $this->field->get_type();
        $default = $this->field->get_default();

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
        return $this->field->sanitize_value($value);
    }

    public function validate_for_block(mixed $value): bool
    {
        return $this->field->validate_value($value);
    }
}
