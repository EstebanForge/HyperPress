<?php

declare(strict_types=1);

namespace HyperFields\Admin;

/**
 * Immutable configuration for ExportImportUI.
 *
 * Keep this as a normal class (not `readonly class`) to satisfy WordPress.org plugin
 * directory commit-hook parsing requirements; per-property readonly keeps immutability.
 */
final class ExportImportPageConfig
{
    /**
     * @param array<string, string> $options
     * @param array<int, string>    $allowedImportOptions
     * @param array<string, string> $optionGroups
     */
    public function __construct(
        public readonly array $options = [],
        public readonly array $allowedImportOptions = [],
        public readonly array $optionGroups = [],
        public readonly string $prefix = '',
        public readonly string $title = 'Data Export / Import',
        public readonly string $description = 'Export your settings to JSON or import a previously exported file.',
        public readonly mixed $exporter = null,
        public readonly mixed $previewer = null,
        public readonly mixed $importer = null,
        public readonly ?string $exportFormExtras = null,
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
