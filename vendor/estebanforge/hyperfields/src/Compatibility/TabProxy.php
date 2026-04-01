<?php

declare(strict_types=1);

namespace HyperFields\Compatibility;

final class TabProxy
{
    /**
     * @var array<int, SectionProxy>
     */
    private array $sections = [];
    private bool $option_level = false;

    /** @var string */
    private string $key;
    /** @var string */
    private string $label;

    /**
     *   construct.
     */
    public function __construct(
        string $key,
        string $label
    ) {
        $this->key = $key;
        $this->label = $label;
    }

    /**
     * Add section.
     *
     * @return SectionProxy
     */
    public function add_section(string $title, array $args = []): SectionProxy
    {
        $id = isset($args['id']) && is_string($args['id']) && $args['id'] !== ''
            ? $args['id']
            : sanitize_key($this->key . '_' . $title . '_' . count($this->sections));

        $section = new SectionProxy($this->key, $id, $title, $args);
        $this->sections[] = $section;

        return $section;
    }

    /**
     * Option level.
     *
     * @return self
     */
    public function option_level(bool $flag = true): self
    {
        $this->option_level = $flag;

        return $this;
    }

    /**
     * Is option level.
     *
     * @return bool
     */
    public function is_option_level(): bool
    {
        return $this->option_level;
    }

    /**
     * GetKey.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * GetLabel.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return array<int, SectionProxy>
     */
    public function getSections(): array
    {
        return $this->sections;
    }
}
