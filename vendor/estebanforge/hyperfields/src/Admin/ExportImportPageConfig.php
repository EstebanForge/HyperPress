<?php

declare(strict_types=1);

namespace HyperFields\Admin;

/**
 * Immutable configuration for ExportImportUI.
 */
final readonly class ExportImportPageConfig
{
    /**
     * @param array<string, string> $options
     * @param array<int, string>    $allowedImportOptions
     * @param array<string, string> $optionGroups
     */
    public function __construct(
        public array $options = [],
        public array $allowedImportOptions = [],
        public array $optionGroups = [],
        public string $prefix = '',
        public string $title = 'Data Export / Import',
        public string $description = 'Export your settings to JSON or import a previously exported file.',
        public mixed $exporter = null,
        public mixed $previewer = null,
        public mixed $importer = null,
        public ?string $exportFormExtras = null,
    ) {}

    /**
     * Returns allowed import options, defaulting to all registered option keys.
     *
     * @return array<int, string>
     */
    public function resolvedAllowedImportOptions(): array
    {
        if (!empty($this->allowedImportOptions)) {
            return $this->allowedImportOptions;
        }

        return array_keys($this->options);
    }
}
