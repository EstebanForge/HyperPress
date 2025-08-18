<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class OptionField extends Field
{
    private string $option_name;
    private string $option_group = 'hyperpress_fields';

    public static function for_option(string $option_name, string $type, string $name, string $label): self
    {
        $field = new self($type, $name, $label);
        $field->option_name = $option_name;
        $field->set_context('option');
        $field->set_storage_type('option');

        return $field;
    }

    public function set_option_group(string $group): self
    {
        $this->option_group = $group;

        return $this;
    }

    public function get_option_name(): string
    {
        return apply_filters('hyperpress_option_field_name', $this->option_name, $this->get_name());
    }

    public function get_option_group(): string
    {
        return $this->option_group;
    }

    public function get_value(): mixed
    {
        $value = get_option($this->get_option_name());

        if ($value === false || $value === '') {
            $value = $this->get_default();
        }

        // Handle array storage for multiple fields in single option
        if (is_array($value)) {
            return $value[$this->get_name()] ?? $this->get_default();
        }

        return $this->sanitize_value($value);
    }

    public function set_value(mixed $value): bool
    {
        $sanitized_value = $this->sanitize_value($value);

        if (!$this->validate_value($sanitized_value)) {
            return false;
        }

        // Handle both single and array storage
        $current_options = get_option($this->get_option_name(), []);

        if (is_array($current_options)) {
            $current_options[$this->get_name()] = $sanitized_value;

            return update_option($this->get_option_name(), $current_options);
        }

        return update_option($this->get_option_name(), $sanitized_value);
    }

    public function delete_value(): bool
    {
        $current_options = get_option($this->get_option_name(), []);

        if (is_array($current_options) && isset($current_options[$this->get_name()])) {
            unset($current_options[$this->get_name()]);

            if (empty($current_options)) {
                return delete_option($this->get_option_name());
            }

            return update_option($this->get_option_name(), $current_options);
        }

        return delete_option($this->get_option_name());
    }
}
