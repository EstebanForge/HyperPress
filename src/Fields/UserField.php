<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class UserField extends Field
{
    private int $user_id;
    private string $meta_key_prefix = '';

    public static function forUser(int $user_id, string $type, string $name, string $label): self
    {
        $field = new self($type, $name, $label);
        $field->user_id = $user_id;
        $field->setContext('user');

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

        return apply_filters('hyperpress/fields/user_field_meta_key', $key, $this->getName(), $this->user_id);
    }

    public function getValue(): mixed
    {
        $value = get_user_meta($this->user_id, $this->getMetaKey(), true);

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

        return update_user_meta($this->user_id, $this->getMetaKey(), $sanitized_value) !== false;
    }

    public function deleteValue(): bool
    {
        return delete_user_meta($this->user_id, $this->getMetaKey()) !== false;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }
}
