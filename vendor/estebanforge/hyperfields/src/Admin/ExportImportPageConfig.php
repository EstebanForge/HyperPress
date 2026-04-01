<?php

declare(strict_types=1);

namespace HyperFields\Admin;

/**
 * Immutable configuration for ExportImportUI.
 */
final class ExportImportPageConfig
{
    /** @var array<string, string> */
    public array $options;
    /** @var array<int, string> */
    public array $allowedImportOptions;
    /** @var array<string, string> */
    public array $optionGroups;
    public string $prefix;
    public string $title;
    public string $description;
    /** @var mixed */
    public $exporter;
    /** @var mixed */
    public $previewer;
    /** @var mixed */
    public $importer;
    public ?string $exportFormExtras;

    /**
     * @param array<string, string> $options
     * @param array<int, string>    $allowedImportOptions
     * @param array<string, string> $optionGroups
     */
    public function __construct(
        array $options = [],
        array $allowedImportOptions = [],
        array $optionGroups = [],
        string $prefix = '',
        string $title = 'Data Export / Import',
        string $description = 'Export your settings to JSON or import a previously exported file.',
        $exporter = null,
        $previewer = null,
        $importer = null,
        ?string $exportFormExtras = null,
    ) {
        $this->options = $options;
        $this->allowedImportOptions = $allowedImportOptions;
        $this->optionGroups = $optionGroups;
        $this->prefix = $prefix;
        $this->title = $title;
        $this->description = $description;
        $this->exporter = $exporter;
        $this->previewer = $previewer;
        $this->importer = $importer;
        $this->exportFormExtras = $exportFormExtras;
    }

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
