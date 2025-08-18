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

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function addField(Field $field): self
    {
        $this->fields[$field->getName()] = $field;
        $field->setContext('option');

        return $this;
    }

    public function getFields(): array
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
