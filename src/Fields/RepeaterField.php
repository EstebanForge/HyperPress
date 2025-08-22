<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class RepeaterField extends Field
{
    private array $sub_fields = [];
    private string $label_template = '{index}';
    private bool $collapsible = true;
    private bool $collapsed = false;
    private int $min_rows = 0;
    private int $max_rows = 0;

    public function add_sub_field(Field $field): self
    {
        $this->sub_fields[$field->getName()] = $field;

        return $this;
    }

    public function add_sub_fields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->add_sub_field($field);
        }

        return $this;
    }

    public function set_label_template(string $template): self
    {
        $this->label_template = $template;

        return $this;
    }

    public function set_collapsible(bool $collapsible = true): self
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    public function set_collapsed(bool $collapsed = true): self
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    public function setMinRows(int $min): self
    {
        $this->min_rows = max(0, $min);

        return $this;
    }

    public function setMaxRows(int $max): self
    {
        $this->max_rows = max(0, $max);

        return $this;
    }

    public function get_sub_fields(): array
    {
        return $this->sub_fields;
    }

    public function getLabelTemplate(): string
    {
        return $this->label_template;
    }

    public function is_collapsible(): bool
    {
        return $this->collapsible;
    }

    public function is_collapsed(): bool
    {
        return $this->collapsed;
    }

    public function get_min_rows(): int
    {
        return $this->min_rows;
    }

    public function get_max_rows(): int
    {
        return $this->max_rows;
    }

    public function sanitizeValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return [];
        }

        $sanitized = [];
        foreach ($value as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $sanitized_row = [];
            foreach ($this->sub_fields as $field_name => $field) {
                $field_value = $row[$field_name] ?? null;
                $sanitized_row[$field_name] = $field->sanitizeValue($field_value);
            }
            $sanitized[] = $sanitized_row;
        }

        return $sanitized;
    }

    public function validateValue(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $row_count = count($value);

        if ($this->min_rows > 0 && $row_count < $this->min_rows) {
            return false;
        }

        if ($this->max_rows > 0 && $row_count > $this->max_rows) {
            return false;
        }

        return true;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'sub_fields' => array_map(fn ($field) => $field->toArray(), $this->sub_fields),
            'label_template' => $this->label_template,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'min_rows' => $this->min_rows,
            'max_rows' => $this->max_rows,
        ]);
    }

    public static function make(string $name, string $label, string $type = 'repeater'): self
    {
        return new self($type, $name, $label);
    }
}
