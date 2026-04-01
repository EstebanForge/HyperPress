<?php

declare(strict_types=1);

namespace HyperFields;

class OptionsSection
{
    private string $id;
    private string $title;
    private string $description;
    private string $slug;
    private bool $as_link = false;
    private bool $allow_html_description = false;
    private array $fields = [];

    /**
     *   construct.
     */
    public function __construct(string $id, string $title, string $description = '', array $args = [])
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->slug = isset($args['slug']) && is_string($args['slug']) && $args['slug'] !== ''
            ? $args['slug']
            : $this->buildSlug($title);
        $this->as_link = isset($args['as_link']) ? (bool) $args['as_link'] : false;
        $this->allow_html_description = isset($args['allow_html_description'])
            ? (bool) $args['allow_html_description']
            : false;
    }

    /**
     * BuildSlug.
     *
     * @return string
     */
    private function buildSlug(string $value): string
    {
        if (function_exists('sanitize_title')) {
            try {
                $slug = (string) sanitize_title($value);
                if ($slug !== '') {
                    return $slug;
                }
            } catch (\Throwable $e) {
                // In unit tests, WP function shims may report "exists" but throw when not mocked.
            }
        }

        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim((string) $slug, '-');
    }

    /**
     * SetDescription.
     *
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * GetId.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * GetTitle.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * GetDescription.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * GetSlug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * IsLinkSection.
     *
     * @return bool
     */
    public function isLinkSection(): bool
    {
        return $this->as_link;
    }

    /**
     * AllowsHtmlDescription.
     *
     * @return bool
     */
    public function allowsHtmlDescription(): bool
    {
        return $this->allow_html_description;
    }

    /**
     * AddField.
     *
     * @return self
     */
    public function addField(Field $field): self
    {
        $this->fields[$field->getName()] = $field;
        $field->setContext('option');

        return $this;
    }

    /**
     * GetFields.
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Render.
     *
     * @return void
     */
    public function render(): void
    {
        if ($this->description) {
            if ($this->allow_html_description) {
                echo '<p class="description">' . wp_kses_post($this->description) . '</p>';
            } else {
                echo '<p class="description">' . esc_html($this->description) . '</p>';
            }
        }
    }

    /**
     * Make.
     *
     * @return self
     */
    public static function make(string $id, string $title, string $description = '', array $args = []): self
    {
        return new self($id, $title, $description, $args);
    }
}
