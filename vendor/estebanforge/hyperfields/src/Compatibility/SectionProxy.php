<?php

declare(strict_types=1);

namespace HyperFields\Compatibility;

final class SectionProxy
{
    private array $options = [];
    private bool $option_level = false;

    public function __construct(
        private readonly string $tabKey,
        private readonly string $id,
        private readonly string $title,
        private readonly array $args = []
    ) {
    }

    public function add_option(string $type, array $args = []): self
    {
        $this->options[] = [
            'type' => $type,
            'args' => $args,
        ];

        return $this;
    }

    public function option_level(bool $flag = true): self
    {
        $this->option_level = $flag;

        return $this;
    }

    public function is_option_level(): bool
    {
        return $this->option_level;
    }

    public function getTabKey(): string
    {
        return $this->tabKey;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
