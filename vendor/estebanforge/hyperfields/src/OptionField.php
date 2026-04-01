<?php

declare(strict_types=1);

namespace HyperFields;

class OptionField extends Field
{
    private string $option_name;
    private string $option_group = 'hyperpress_fields';

    /**
     * ForOption.
     *
     * @return self
     */
    public static function forOption(string $option_name, string $type, string $name, string $label): self
    {
        $field = new self($type, $name, $label);
        $field->option_name = $option_name;
        $field->setContext('option');
        $field->setStorageType('option');

        return $field;
    }

    /**
     * SetOptionGroup.
     *
     * @return self
     */
    public function setOptionGroup(string $group): self
    {
        $this->option_group = $group;

        return $this;
    }

    /**
     * GetOptionName.
     *
     * @return string
     */
    public function getOptionName(): string
    {
        return apply_filters('hyperfields/option_field_name', $this->option_name, $this->getName());
    }

    /**
     * GetOptionGroup.
     *
     * @return string
     */
    public function getOptionGroup(): string
    {
        return $this->option_group;
    }

    /**
     * GetValue.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        $value = get_option($this->getOptionName());

        if ($value === false || $value === '') {
            $value = $this->getDefault();
        }

        // Handle array storage for multiple fields in single option
        if (is_array($value)) {
            return $value[$this->getName()] ?? $this->getDefault();
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

        // Handle both single and array storage
        $current_options = get_option($this->getOptionName(), []);

        if (is_array($current_options)) {
            $current_options[$this->getName()] = $sanitized_value;

            return update_option($this->getOptionName(), $current_options);
        }

        return update_option($this->getOptionName(), $sanitized_value);
    }

    /**
     * DeleteValue.
     *
     * @return bool
     */
    public function deleteValue(): bool
    {
        $current_options = get_option($this->getOptionName(), []);

        if (is_array($current_options) && isset($current_options[$this->getName()])) {
            unset($current_options[$this->getName()]);

            if (empty($current_options)) {
                return delete_option($this->getOptionName());
            }

            return update_option($this->getOptionName(), $current_options);
        }

        return delete_option($this->getOptionName());
    }
}
