<?php

declare(strict_types=1);

namespace HyperFields;

class BlockFieldAdapter
{
    private Field $field;
    private array $block_attributes;

    public function __construct(Field $field, array $block_attributes = [])
    {
        $this->field = $field;
        $this->block_attributes = $block_attributes;
    }

    public static function fromField(Field $field, array $blockAttributes = []): self
    {
        return new self($field, $blockAttributes);
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

    public function getField(): Field
    {
        return $this->field;
    }

    public function getAttributeName(): string
    {
        return $this->field->getName();
    }

    public function toBlockAttribute(): array
    {
        $fieldType = $this->field->getType();
        $default = $this->field->getDefault();

        // Map field types to WordPress block attribute types
        $typeMap = [
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

        $attributeType = $typeMap[$fieldType] ?? 'string';

        return [
            'type' => $attributeType,
            'default' => $default,
        ];
    }

    public function sanitizeForBlock(mixed $value): mixed
    {
        return $this->field->sanitizeValue($value);
    }

    public function validateForBlock(mixed $value): bool
    {
        return $this->field->validateValue($value);
    }
}
