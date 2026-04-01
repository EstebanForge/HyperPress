<?php

declare(strict_types=1);

namespace HyperFields;

class UserField extends Field
{
    private int $user_id;
    private string $meta_key_prefix = '';

    /**
     * ForUser.
     *
     * @return self
     */
    public static function forUser(int $user_id, string $type, string $name, string $label): self
    {
        $field = new self($type, $name, $label);
        $field->user_id = $user_id;
        $field->setContext('user');

        return $field;
    }

    /**
     * SetMetaKeyPrefix.
     *
     * @return self
     */
    public function setMetaKeyPrefix(string $prefix): self
    {
        $this->meta_key_prefix = $prefix;

        return $this;
    }

    /**
     * GetMetaKey.
     *
     * @return string
     */
    public function getMetaKey(): string
    {
        $key = $this->meta_key_prefix . $this->getName();

        return apply_filters('hyperfields/user_field_meta_key', $key, $this->getName(), $this->user_id);
    }

    /**
     * GetValue.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        $value = get_user_meta($this->user_id, $this->getMetaKey(), true);

        if ($value === '' || $value === false) {
            $value = $this->getDefault();
        }

        return $this->sanitizeValue($value);
    }

    /**
     * SetValue.
     *
     * @return bool
     */
    public function setValue(mixed $value): bool
    {
        $sanitized_value = $this->sanitizeValue($value);

        if (!$this->validateValue($sanitized_value)) {
            return false;
        }

        return update_user_meta($this->user_id, $this->getMetaKey(), $sanitized_value) !== false;
    }

    /**
     * DeleteValue.
     *
     * @return bool
     */
    public function deleteValue(): bool
    {
        return delete_user_meta($this->user_id, $this->getMetaKey()) !== false;
    }

    /**
     * GetUserId.
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }
}
