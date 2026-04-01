<?php

declare(strict_types=1);

namespace HyperFields;

class RepeaterField extends Field
{
    private array $sub_fields = [];
    private string $label_template = '{index}';
    private bool $collapsible = true;
    private bool $collapsed = false;
    private int $min_rows = 0;
    private int $max_rows = 0;

    /**
     * AddSubField.
     *
     * @return self
     */
    public function addSubField(Field $field): self
    {
        $this->sub_fields[$field->getName()] = $field;

        return $this;
    }

    /**
     * AddSubFields.
     *
     * @return self
     */
    public function addSubFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->addSubField($field);
        }

        return $this;
    }

    /**
     * SetLabelTemplate.
     *
     * @return self
     */
    public function setLabelTemplate(string $template): self
    {
        $this->label_template = $template;

        return $this;
    }

    /**
     * SetCollapsible.
     *
     * @return self
     */
    public function setCollapsible(bool $collapsible = true): self
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * SetCollapsed.
     *
     * @return self
     */
    public function setCollapsed(bool $collapsed = true): self
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    /**
     * SetMinRows.
     *
     * @return self
     */
    public function setMinRows(int $min): self
    {
        $this->min_rows = max(0, $min);

        return $this;
    }

    /**
     * SetMaxRows.
     *
     * @return self
     */
    public function setMaxRows(int $max): self
    {
        $this->max_rows = max(0, $max);

        return $this;
    }

    /**
     * GetSubFields.
     *
     * @return array
     */
    public function getSubFields(): array
    {
        return $this->sub_fields;
    }

    /**
     * GetLabelTemplate.
     *
     * @return string
     */
    public function getLabelTemplate(): string
    {
        return $this->label_template;
    }

    /**
     * IsCollapsible.
     *
     * @return bool
     */
    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * IsCollapsed.
     *
     * @return bool
     */
    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    /**
     * GetMinRows.
     *
     * @return int
     */
    public function getMinRows(): int
    {
        return $this->min_rows;
    }

    /**
     * GetMaxRows.
     *
     * @return int
     */
    public function getMaxRows(): int
    {
        return $this->max_rows;
    }

    /**
     * SanitizeValue.
     *
     * @return mixed
     */
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

    /**
     * ValidateValue.
     *
     * @return bool
     */
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

    /**
     * ToArray.
     *
     * @return array
     */
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

    /**
     * Make.
     *
     * @return self
     */
    public static function make(string $name, string $label, string $type = 'repeater'): self
    {
        return new self($type, $name, $label);
    }
}
