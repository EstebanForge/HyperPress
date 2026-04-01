<?php

declare(strict_types=1);

namespace HyperFields;

use HyperFields\Validation\SchemaValidator;

/**
 * Export/Import utility for WordPress options.
 *
 * Provides static methods for exporting one or more WordPress option groups to
 * JSON and re-importing them, with optional prefix filtering and whitelisting for
 * security.  No admin UI is included here – see {@see \HyperFields\Admin\ExportImportUI}
 * for a plug-and-play UI component.
 *
 * Every exported option is wrapped in a typed-node envelope:
 *   { "value": <raw>, "_schema": { "type": "<detected>", ... } }
 *
 * On import, the envelope is validated: the value must match its declared
 * _schema.type. Full schema rules (max, min, enum, pattern, format) are
 * validated when present via {@see SchemaValidator}.
 *
 * Usage:
 * ```php
 * // Export with auto-detected types
 * $json = ExportImport::exportOptions(['myplugin_options']);
 *
 * // Export with explicit schema rules per option
 * $json = ExportImport::exportOptions(['myplugin_options'], '', [
 *     'myplugin_options' => ['type' => 'array', 'fields' => [
 *         'api_key' => ['type' => 'string', 'max' => 255],
 *     ]],
 * ]);
 *
 * // Import (validates typed nodes + schema rules)
 * $result = ExportImport::importOptions($json, ['myplugin_options']);
 * ```
 */
class ExportImport
{
    /** Schema version embedded in every export payload. */
    private const SCHEMA_VERSION = '1.0';

    /** @var array<int, string> */
    private const SUPPORTED_IMPORT_MODES = ['merge', 'replace'];
    private const STRATEGY_KEY = '__strategy';
    private const SUPPORTED_NODE_STRATEGIES = [
        'merge',
        'replace',
        'override',
        'migrate',
        'create',
        'skip',
        'delete',
        'recreate',
    ];

    /**
     * Export one or more WordPress option groups to a JSON string.
     *
     * Each option value is wrapped in a typed-node envelope with a `_schema`
     * object.  When a `$schemaMap` entry exists for an option name, its full
     * schema rules are embedded; otherwise, only the detected type is recorded.
     *
     * @param array                        $optionNames Array of WP option names to export.
     * @param string                       $prefix      When non-empty, only option-value keys
     *                                                  starting with this prefix are included.
     * @param array<string, array>         $schemaMap   Optional schema rules keyed by option name.
     *                                                  Each entry is a schema rule array accepted
     *                                                  by {@see SchemaValidator::validate()}.
     * @return string JSON string ready for download / storage.
     */
    public static function exportOptions(array $optionNames, string $prefix = '', array $schemaMap = []): string
    {
        $data = [];

        foreach ($optionNames as $optionName) {
            $optionName = sanitize_text_field((string) $optionName);
            if ($optionName === '') {
                continue;
            }

            $value = get_option($optionName, []);

            // Apply prefix filter
            if ($prefix !== '' && is_array($value)) {
                $value = array_filter(
                    $value,
                    static fn($key): bool => strpos((string) $key, $prefix) === 0,
                    ARRAY_FILTER_USE_KEY
                );
            }

            $schema = $schemaMap[$optionName] ?? null;
            $node = self::wrapTypedNode($value, $schema);
            $node = self::attachExportStrategy($node, $optionName, $value);
            $data[$optionName] = $node;
        }

        $payload = [
            'version'     => self::SCHEMA_VERSION,
            'type'        => 'hyperfields_export',
            'prefix'      => $prefix,
            'exported_at' => current_time('mysql'),
            'site_url'    => get_site_url(),
            'options'     => $data,
        ];

        $encoded = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = [
            'success' => is_string($encoded),
            'message' => is_string($encoded) ? 'Options exported successfully.' : 'Failed to encode export payload.',
        ];

        /**
         * Fires after HyperFields has finished exporting options.
         *
         * @param array $result     Export result metadata.
         * @param array $payload    Decoded payload prior to JSON encoding.
         * @param array $optionNames Original requested option names.
         * @param string $prefix    Prefix filter applied.
         * @param array $schemaMap  Per-option schema map used for typed-node envelopes.
         */
        do_action('hyperfields/export/after', $result, $payload, $optionNames, $prefix, $schemaMap);

        return $encoded !== false ? $encoded : '{}';
    }

    /**
     * Import options from a previously exported JSON string.
     *
     * Every option node must be a typed-node envelope (value + _schema).
     * The value is validated against _schema rules via SchemaValidator before
     * being written to the database.
     *
     * @param string $jsonString         The JSON string produced by {@see exportOptions()}.
     * @param array  $allowedOptionNames Whitelist of WP option names that may be written.
     * @param string $prefix             When non-empty, only keys starting with this prefix
     *                                   are imported for array options.
     * @param array  $options            Optional import behavior:
     *                                   - mode: 'merge'|'replace' (default 'merge')
     * @return array{success: bool, message: string, backup_keys?: array<string, string>}
     */
    public static function importOptions(string $jsonString, array $allowedOptionNames = [], string $prefix = '', array $options = []): array
    {
        if ($jsonString === '') {
            $result = ['success' => false, 'message' => 'Empty import data.'];
            self::dispatchImportAfter($result, [], $allowedOptionNames, $prefix, $options);

            return $result;
        }

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $result = ['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()];
            self::dispatchImportAfter($result, [], $allowedOptionNames, $prefix, $options);

            return $result;
        }

        if (!is_array($decoded) || !isset($decoded['options']) || !is_array($decoded['options'])) {
            $result = ['success' => false, 'message' => 'Invalid export format. Expected a "options" key with an array value.'];
            self::dispatchImportAfter($result, is_array($decoded) ? $decoded : [], $allowedOptionNames, $prefix, $options);

            return $result;
        }

        $backupKeys    = [];
        $errors        = [];
        $importedCount = 0;
        $importMode    = self::resolveImportMode($options);

        foreach ($decoded['options'] as $optionName => $incoming) {
            $optionName = sanitize_text_field((string) $optionName);

            // Whitelist check
            if (!empty($allowedOptionNames) && !in_array($optionName, $allowedOptionNames, true)) {
                continue;
            }

            // Typed-node enforcement: every option must be a typed node.
            if (!self::isTypedNode($incoming)) {
                $errors[] = "Rejected option '{$optionName}': missing typed-node envelope (value + _schema).";
                continue;
            }

            // Validate value against _schema rules.
            $schemaError = self::validateTypedNode($optionName, $incoming);
            if ($schemaError !== null) {
                $errors[] = $schemaError;
                continue;
            }

            $nodeStrategy = self::resolveNodeStrategy($incoming);
            if ($nodeStrategy === 'skip') {
                continue;
            }

            if ($nodeStrategy === 'delete') {
                delete_option($optionName);
                $importedCount++;
                continue;
            }

            // Unwrap the typed node to get the raw value for storage.
            $incoming = $incoming['value'];

            if (!is_array($incoming) && !is_scalar($incoming) && $incoming !== null) {
                $errors[] = "Skipped option '{$optionName}': unsupported value type.";
                continue;
            }

            // Apply prefix filter on the incoming keys
            if ($prefix !== '' && is_array($incoming)) {
                $incoming = array_filter(
                    $incoming,
                    static fn($key): bool => strpos((string) $key, $prefix) === 0,
                    ARRAY_FILTER_USE_KEY
                );
            }

            if ($prefix !== '' && !is_array($incoming)) {
                $errors[] = "Skipped option '{$optionName}': scalar values cannot be prefix-filtered.";
                continue;
            }

            if (is_array($incoming) && empty($incoming)) {
                continue;
            }

            // Backup existing value using a transient so it auto-expires
            $missingMarker = self::missingMarker();
            $existing = get_option($optionName, $missingMarker);
            $hasExisting = ($existing !== $missingMarker);
            if ($existing !== null && $existing !== []) {
                $backupKey               = 'hf_backup_' . sanitize_key($optionName) . '_' . time();
                set_transient($backupKey, $existing, HOUR_IN_SECONDS);
                $backupKeys[$optionName] = $backupKey;
            }

            if ($nodeStrategy === 'create' && $hasExisting) {
                continue;
            }

            $effectiveMode = self::effectiveImportMode($importMode, $nodeStrategy);
            if ($nodeStrategy === 'recreate' && $hasExisting) {
                delete_option($optionName);
            }

            $nextValue = self::buildNextOptionValue($hasExisting ? $existing : null, $incoming, $effectiveMode);

            $updated = update_option($optionName, $nextValue);
            if ($updated || $existing === $nextValue) {
                $importedCount++;
            }
        }

        if ($importedCount === 0 && empty($errors)) {
            $result = ['success' => false, 'message' => 'No options were imported. The whitelist or prefix filter may have excluded all entries.'];
            self::dispatchImportAfter($result, $decoded, $allowedOptionNames, $prefix, $options);

            return $result;
        }

        if ($importedCount === 0) {
            $result = ['success' => false, 'message' => implode(' ', $errors)];
            self::dispatchImportAfter($result, $decoded, $allowedOptionNames, $prefix, $options);

            return $result;
        }

        $message = 'Options imported successfully.';
        if (!empty($errors)) {
            $message .= ' Note: ' . implode(' ', $errors);
        }

        $result = ['success' => true, 'message' => $message];
        if (!empty($backupKeys)) {
            $result['backup_keys'] = $backupKeys;
        }

        self::dispatchImportAfter($result, $decoded, $allowedOptionNames, $prefix, $options);

        return $result;
    }

    /**
     * Fires the import completion action for both success and failure outcomes.
     *
     * @param array<string, mixed> $result
     * @param array<string, mixed> $decoded
     * @param array<int, string> $allowedOptionNames
     * @param string $prefix
     * @param array<string, mixed> $options
     */
    private static function dispatchImportAfter(
        array $result,
        array $decoded,
        array $allowedOptionNames,
        string $prefix,
        array $options
    ): void {
        /**
         * Fires after HyperFields has finished importing options.
         *
         * @param array  $result             Import result.
         * @param array  $decoded            The full decoded JSON payload.
         * @param array  $allowedOptionNames Whitelist of option names allowed.
         * @param string $prefix             Prefix filter applied.
         * @param array  $options            Import behavior options.
         */
        do_action('hyperfields/import/after', $result, $decoded, $allowedOptionNames, $prefix, $options);
    }

    /**
     * Restore an option from a transient backup created during import.
     *
     * @param string $backupKey  The transient key from importOptions() 'backup_keys'.
     * @param string $optionName The WP option name to restore.
     * @return bool
     */
    public static function restoreBackup(string $backupKey, string $optionName): bool
    {
        $backup = get_transient($backupKey);
        if ($backup === false) {
            return false;
        }

        $existing  = get_option(sanitize_text_field($optionName));
        $restored  = update_option(sanitize_text_field($optionName), $backup);
        $unchanged = ($restored === false && $backup === $existing);
        if ($restored || $unchanged) {
            delete_transient($backupKey);
        }

        return $restored || $unchanged;
    }

    /**
     * Return a snapshot of the current stored values for a set of option names.
     *
     * @param array  $optionNames Option names to snapshot.
     * @param string $prefix      Optional prefix filter.
     * @return array
     */
    public static function snapshotOptions(array $optionNames, string $prefix = ''): array
    {
        $snapshot = [];

        foreach ($optionNames as $optionName) {
            $optionName = sanitize_text_field((string) $optionName);
            if ($optionName === '') {
                continue;
            }

            $value = get_option($optionName, []);

            if ($prefix !== '' && is_array($value)) {
                $value = array_filter(
                    $value,
                    static fn($key): bool => strpos((string) $key, $prefix) === 0,
                    ARRAY_FILTER_USE_KEY
                );
            }

            $snapshot[$optionName] = $value;
        }

        return $snapshot;
    }

    /**
     * Build a dry-run comparison for an incoming options payload.
     *
     * @param string $jsonString         JSON payload produced by exportOptions().
     * @param array  $allowedOptionNames Option write whitelist.
     * @param string $prefix             Optional array-key prefix filter.
     * @param array  $options            Optional behavior: mode 'merge'|'replace'.
     * @return array{success: bool, message: string, changes?: array, skipped?: array}
     */
    public static function diffOptions(string $jsonString, array $allowedOptionNames = [], string $prefix = '', array $options = []): array
    {
        if ($jsonString === '') {
            return ['success' => false, 'message' => 'Empty import data.'];
        }

        $decoded = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()];
        }

        if (!is_array($decoded) || !isset($decoded['options']) || !is_array($decoded['options'])) {
            return ['success' => false, 'message' => 'Invalid export format. Expected a "options" key with an array value.'];
        }

        $importMode = self::resolveImportMode($options);
        $changes = [];
        $skipped = [];

        foreach ($decoded['options'] as $optionName => $incoming) {
            $optionName = sanitize_text_field((string) $optionName);
            if ($optionName === '') {
                continue;
            }

            if (!empty($allowedOptionNames) && !in_array($optionName, $allowedOptionNames, true)) {
                $skipped[] = "Skipped '{$optionName}': not in whitelist.";
                continue;
            }

            // Typed-node enforcement.
            if (!self::isTypedNode($incoming)) {
                $skipped[] = "Skipped '{$optionName}': missing typed-node envelope (value + _schema).";
                continue;
            }

            $schemaError = self::validateTypedNode($optionName, $incoming);
            if ($schemaError !== null) {
                $skipped[] = $schemaError;
                continue;
            }

            $nodeStrategy = self::resolveNodeStrategy($incoming);
            if ($nodeStrategy === 'skip') {
                $skipped[] = "Skipped '{$optionName}': strategy=skip.";
                continue;
            }

            $incoming = $incoming['value'];

            if (!is_array($incoming) && !is_scalar($incoming) && $incoming !== null) {
                $skipped[] = "Skipped '{$optionName}': unsupported value type.";
                continue;
            }

            if ($prefix !== '' && is_array($incoming)) {
                $incoming = array_filter(
                    $incoming,
                    static fn($key): bool => strpos((string) $key, $prefix) === 0,
                    ARRAY_FILTER_USE_KEY
                );
            }

            if ($prefix !== '' && !is_array($incoming)) {
                $skipped[] = "Skipped '{$optionName}': scalar values cannot be prefix-filtered.";
                continue;
            }

            if (is_array($incoming) && empty($incoming)) {
                continue;
            }

            $missingMarker = self::missingMarker();
            $existing = get_option($optionName, $missingMarker);
            $hasExisting = ($existing !== $missingMarker);

            if ($nodeStrategy === 'delete') {
                if ($hasExisting) {
                    $changes[$optionName] = [
                        'before' => $existing,
                        'after' => null,
                        'strategy' => 'delete',
                    ];
                }
                continue;
            }

            if ($nodeStrategy === 'create' && $hasExisting) {
                $skipped[] = "Skipped '{$optionName}': strategy=create and option already exists.";
                continue;
            }

            $effectiveMode = self::effectiveImportMode($importMode, $nodeStrategy);
            $nextValue = self::buildNextOptionValue($hasExisting ? $existing : null, $incoming, $effectiveMode);
            $beforeValue = $hasExisting ? $existing : null;
            if ($beforeValue !== $nextValue) {
                $changes[$optionName] = [
                    'before' => $beforeValue,
                    'after' => $nextValue,
                    'strategy' => $nodeStrategy !== '' ? $nodeStrategy : $effectiveMode,
                ];
            }
        }

        return [
            'success' => true,
            'message' => empty($changes) ? 'No differences found.' : 'Differences found.',
            'changes' => $changes,
            'skipped' => $skipped,
        ];
    }

    /**
     * ResolveImportMode.
     *
     * @return string
     */
    private static function resolveImportMode(array $options): string
    {
        $mode = isset($options['mode']) ? sanitize_text_field((string) $options['mode']) : 'merge';
        if (!in_array($mode, self::SUPPORTED_IMPORT_MODES, true)) {
            return 'merge';
        }

        return $mode;
    }

    /**
     * BuildNextOptionValue.
     *
     * @return mixed
     */
    private static function buildNextOptionValue(mixed $existing, mixed $incoming, string $importMode): mixed
    {
        if (!is_array($incoming)) {
            return $incoming;
        }

        if ($importMode === 'replace') {
            return $incoming;
        }

        return array_merge(
            is_array($existing) ? $existing : [],
            $incoming
        );
    }

    // ──────────────────────────────────────────────────────────────────
    //  Typed-node helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * Wraps a raw option value in a typed-node envelope.
     *
     * When a schema rule array is provided, it is embedded as-is in `_schema`.
     * Otherwise, only the auto-detected type is recorded.
     *
     * @param mixed      $value  The raw option value.
     * @param array|null $schema Optional full schema rule to embed.
     * @return array{value: mixed, _schema: array}
     */
    private static function wrapTypedNode(mixed $value, ?array $schema = null): array
    {
        if ($schema !== null) {
            // Ensure the schema always includes the type.
            if (!isset($schema['type'])) {
                $schema['type'] = SchemaValidator::detectType($value);
            }

            return [
                'value'   => $value,
                '_schema' => $schema,
            ];
        }

        return [
            'value'   => $value,
            '_schema' => [
                'type' => SchemaValidator::detectType($value),
            ],
        ];
    }

    /**
     * Returns true when a value looks like a typed node (has 'value' + '_schema' keys).
     */
    private static function isTypedNode(mixed $node): bool
    {
        return is_array($node)
            && array_key_exists('value', $node)
            && isset($node['_schema'])
            && is_array($node['_schema']);
    }

    /**
     * Validates a typed node using SchemaValidator.
     *
     * Validates the value against all rules present in _schema (type, max, min,
     * pattern, enum, format, fields).
     *
     * @param string $optionName For error messages.
     * @param array  $node       A typed node with 'value' and '_schema'.
     * @return string|null       Error message on failure, null on success.
     */
    private static function validateTypedNode(string $optionName, array $node): ?string
    {
        $schema = $node['_schema'];

        if (!isset($schema['type']) || !is_string($schema['type'])) {
            return "Option '{$optionName}': _schema.type is missing or not a string.";
        }

        return SchemaValidator::validate($optionName, $node['value'], $schema);
    }

    /**
     * Returns a per-option import strategy from a typed node.
     *
     * @param array<string, mixed> $node
     * @return string
     */
    private static function resolveNodeStrategy(array $node): string
    {
        $strategy = isset($node[self::STRATEGY_KEY]) ? sanitize_key((string) $node[self::STRATEGY_KEY]) : '';
        if (!in_array($strategy, self::SUPPORTED_NODE_STRATEGIES, true)) {
            return '';
        }

        return $strategy;
    }

    /**
     * Resolves effective import mode from default mode + node strategy.
     *
     * @return string
     */
    private static function effectiveImportMode(string $importMode, string $nodeStrategy): string
    {
        return match ($nodeStrategy) {
            'replace', 'override', 'recreate' => 'replace',
            'merge', 'migrate', 'create' => 'merge',
            default => $importMode,
        };
    }

    /**
     * Returns a unique marker used to detect missing options from get_option.
     *
     * @return object
     */
    private static function missingMarker(): object
    {
        return (object) ['__hf_missing' => true];
    }

    /**
     * Attaches an optional export strategy to an option typed node.
     *
     * @param array<string, mixed> $node
     * @param string $optionName
     * @param mixed $value
     * @return array<string, mixed>
     */
    private static function attachExportStrategy(array $node, string $optionName, mixed $value): array
    {
        $strategy = apply_filters('hyperfields/export/node_strategy', 'replace', $optionName, $value);
        $strategy = sanitize_key((string) $strategy);
        if ($strategy === '' || !in_array($strategy, self::SUPPORTED_NODE_STRATEGIES, true)) {
            return $node;
        }

        $node[self::STRATEGY_KEY] = $strategy;

        return $node;
    }
}
