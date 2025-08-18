<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class TermField extends Field
{
    private int $term_id;
    private string $meta_key_prefix = '';

    public static function for_term(int $term_id, string $type, string $name, string $label): self
    {
        $field = new self($type, $name, $label);
        $field->term_id = $term_id;
        $field->set_context('term');

        return $field;
    }

    public function set_meta_key_prefix(string $prefix): self
    {
        $this->meta_key_prefix = $prefix;

        return $this;
    }

    public function get_meta_key(): string
    {
        $key = $this->meta_key_prefix . $this->get_name();

        return apply_filters('hyperpress_term_field_meta_key', $key, $this->get_name(), $this->term_id);
    }

    public function get_value(): mixed
    {
        $value = get_term_meta($this->term_id, $this->get_meta_key(), true);

        if ($value === '' || $value === false) {
            $value = $this->get_default();
        }

        return $this->sanitize_value($value);
    }

    public function set_value(mixed $value): bool
    {
        $sanitized_value = $this->sanitize_value($value);

        if (!$this->validate_value($sanitized_value)) {
            return false;
        }

        return update_term_meta($this->term_id, $this->get_meta_key(), $sanitized_value) !== false;
    }

    public function delete_value(): bool
    {
        return delete_term_meta($this->term_id, $this->get_meta_key()) !== false;
    }

    public function get_term_id(): int
    {
        return $this->term_id;
    }
}
