<?php

declare(strict_types=1);

namespace HyperFields;

class TermField extends Field
{
    private int $term_id;
    private string $meta_key_prefix = '';

    public static function forTerm(int $term_id, string $type, string $name, string $label): self
    {
        $field = new self($type, $name, $label);
        $field->term_id = $term_id;
        $field->setContext('term');

        return $field;
    }

    public function setMetaKeyPrefix(string $prefix): self
    {
        $this->meta_key_prefix = $prefix;

        return $this;
    }

    public function getMetaKey(): string
    {
        $key = $this->meta_key_prefix . $this->getName();

        return apply_filters('hyperpress/fields/term_field_meta_key', $key, $this->getName(), $this->term_id);
    }

    public function getValue(): mixed
    {
        $value = get_term_meta($this->term_id, $this->getMetaKey(), true);

        if ($value === '' || $value === false) {
            $value = $this->getDefault();
        }

        return $this->sanitizeValue($value);
    }

    public function setValue(mixed $value): bool
    {
        $sanitized_value = $this->sanitizeValue($value);

        if (!$this->validateValue($sanitized_value)) {
            return false;
        }

        return update_term_meta($this->term_id, $this->getMetaKey(), $sanitized_value) !== false;
    }

    public function deleteValue(): bool
    {
        return delete_term_meta($this->term_id, $this->getMetaKey()) !== false;
    }

    public function getTermId(): int
    {
        return $this->term_id;
    }
}
