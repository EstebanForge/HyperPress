<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class OptionsSection
{
    private string $id;
    private string $title;
    private string $description;
    private array $fields = [];

    public function __construct(string $id, string $title, string $description = '')
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }

    public function set_description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function get_id(): string
    {
        return $this->id;
    }

    public function get_title(): string
    {
        return $this->title;
    }

    public function get_description(): string
    {
        return $this->description;
    }

    public function add_field(Field $field): self
    {
        $this->fields[$field->get_name()] = $field;
        $field->set_context('option');

        return $this;
    }

    public function get_fields(): array
    {
        return $this->fields;
    }

    public function render(): void
    {
        if ($this->description) {
            echo '<p class="description">' . esc_html($this->description) . '</p>';
        }
    }

    public static function make(string $id, string $title, string $description = ''): self
    {
        return new self($id, $title, $description);
    }
}
