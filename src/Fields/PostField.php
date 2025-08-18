<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class PostField extends Field
{
    private int $post_id;
    private string $meta_key_prefix = '';

    public static function for_post(int $post_id, string $type, string $name, string $label): self
    {
        $field = new self($type, $name, $label);
        $field->post_id = $post_id;
        $field->setContext('post');

        return $field;
    }

    public function set_meta_key_prefix(string $prefix): self
    {
        $this->meta_key_prefix = $prefix;

        return $this;
    }

    public function get_meta_key(): string
    {
        $key = $this->meta_key_prefix . $this->getName();

        return apply_filters('hyperpress_post_field_meta_key', $key, $this->getName(), $this->post_id);
    }

    public function getValue(): mixed
    {
        $value = get_post_meta($this->post_id, $this->get_meta_key(), true);

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

        return update_post_meta($this->post_id, $this->get_meta_key(), $sanitized_value) !== false;
    }

    public function deleteValue(): bool
    {
        return delete_post_meta($this->post_id, $this->get_meta_key()) !== false;
    }

    public function get_post_id(): int
    {
        return $this->post_id;
    }
}
