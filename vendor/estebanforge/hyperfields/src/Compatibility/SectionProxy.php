<?php

declare(strict_types=1);

namespace HyperFields\Compatibility;

final class SectionProxy
{
    private array $options = [];
    private bool $option_level = false;

    /**
     *   construct.
     */
    public function __construct(
        private readonly string $tabKey,
        private readonly string $id,
        private readonly string $title,
        private readonly array $args = []
    ) {
    }

    /**
     * Add option.
     *
     * @return self
     */
    public function add_option(string $type, array $args = []): self
    {
        $this->options[] = [
            'type' => $type,
            'args' => $args,
        ];

        return $this;
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
     * GetTabKey.
     *
     * @return string
     */
    public function getTabKey(): string
    {
        return $this->tabKey;
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
     * GetArgs.
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * GetOptions.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
