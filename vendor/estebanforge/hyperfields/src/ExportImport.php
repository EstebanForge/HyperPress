<?php

declare(strict_types=1);

namespace HyperFields;

/**
 * Export/Import utility for WordPress options.
 *
 * Provides static methods for exporting one or more WordPress option groups to
 * JSON and re-importing them, with optional prefix filtering and whitelisting for
 * security.  No admin UI is included here – see {@see \HyperFields\Admin\ExportImportUI}
 * for a plug-and-play UI component.
 *
 * Usage example:
 * ```php
 * // Export
 * $json = ExportImport::exportOptions(['myplugin_options', 'wpseo'], 'myplugin_');
 *
 * // Import (only allow writing back to the plugin's own option)
 * $result = ExportImport::importOptions($json, ['myplugin_options'], 'myplugin_');
 * if ($result['success']) { ... }
 * ```
 */
class ExportImport
{
    /** Schema version embedded in every export payload. */
    private const SCHEMA_VERSION = '1.0';

    /** @var array<int, string> */
    private const SUPPORTED_IMPORT_MODES = ['merge', 'replace'];

    /**
     * Export one or more WordPress option groups to a JSON string.
     *
     * @param array  $optionNames Array of WP option names to export
     *                            (e.g. ['myplugin_options', 'wpseo']).
     * @param string $prefix      When non-empty, only option-value keys that start
     *                            with this prefix are included in the export.
     *                            Default '' exports every key.
     * @return string JSON string ready for download / storage.
     */
    public static function exportOptions(array $optionNames, string $prefix = ''): string
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

            $data[$optionName] = $value;
        }

        $encoded = wp_json_encode(
            [
                'version'     => self::SCHEMA_VERSION,
                'type'        => 'hyperfields_export',
                'prefix'      => $prefix,
                'exported_at' => current_time('mysql'),
                'site_url'    => get_site_url(),
                'options'     => $data,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        return $encoded !== false ? $encoded : '{}';
    }

    /**
     * Import options from a previously exported JSON string.
     *
     * The import is **additive**: existing keys that are not present in the
     * imported data are preserved.  Only the keys present in the JSON payload
     * are merged into the stored option.
     *
     * @param string $jsonString         The JSON string produced by {@see exportOptions()}.
     * @param array  $allowedOptionNames Whitelist of WP option names that may be
     *                                   written.  Empty array means every option name
     *                                   present in the payload is allowed.
     * @param string $prefix             When non-empty, only keys starting with this
     *                                   prefix are imported for array options (others are skipped).
     *                                   Scalar option values are imported only when prefix is empty.
     * @param array  $options            Optional import behavior:
     *                                   - mode: 'merge'|'replace' (default 'merge')
     * @return array{success: bool, message: string, backup_keys?: array<string, string>}
     */
    public static function importOptions(string $jsonString, array $allowedOptionNames = [], string $prefix = '', array $options = []): array
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
            $existing = get_option($optionName, null);
            if ($existing !== null && $existing !== []) {
                $backupKey               = 'hf_backup_' . sanitize_key($optionName) . '_' . time();
                set_transient($backupKey, $existing, HOUR_IN_SECONDS);
                $backupKeys[$optionName] = $backupKey;
            }

            $nextValue = self::buildNextOptionValue($existing, $incoming, $importMode);

            // update_option returns false both on DB failure and when the value is unchanged.
            // Count unchanged values as a successful import since the data matches intent.
            $updated = update_option($optionName, $nextValue);
            if ($updated || $existing === $nextValue) {
                $importedCount++;
            }
        }

        if ($importedCount === 0 && empty($errors)) {
            return ['success' => false, 'message' => 'No options were imported. The whitelist or prefix filter may have excluded all entries.'];
        }

        if ($importedCount === 0) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $message = 'Options imported successfully.';
        if (!empty($errors)) {
            $message .= ' Note: ' . implode(' ', $errors);
        }

        $result = ['success' => true, 'message' => $message];
        if (!empty($backupKeys)) {
            $result['backup_keys'] = $backupKeys;
        }

        return $result;
    }

    /**
     * Restore an option from a transient backup created during import.
     *
     * @param string $backupKey  The transient key returned in the 'backup_keys' result of importOptions().
     * @param string $optionName The WP option name to restore.
     * @return bool True when the option was successfully restored.
     */
    public static function restoreBackup(string $backupKey, string $optionName): bool
    {
        $backup = get_transient($backupKey);
        if ($backup === false) {
            return false;
        }

        $existing  = get_option(sanitize_text_field($optionName));
        $restored  = update_option(sanitize_text_field($optionName), $backup);
        // update_option returns false both on DB failure and when the value is unchanged.
        // A backup restoring the exact same data is still a success; detect it by comparing.
        $unchanged = ($restored === false && $backup === $existing);
        if ($restored || $unchanged) {
            delete_transient($backupKey);
        }

        return $restored || $unchanged;
    }

    /**
     * Return a snapshot of the current stored values for a set of option names.
     *
     * The snapshot is returned as an associative array (not encoded to JSON) so
     * that it can be diffed by {@see \HyperFields\Admin\ExportImportUI} without
     * exposing the data to the end-user's browser before the diff is calculated.
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
     * @param string $jsonString JSON payload produced by exportOptions().
     * @param array  $allowedOptionNames Option write whitelist.
     * @param string $prefix Optional array-key prefix filter.
     * @param array  $options Optional behavior:
     *                        - mode: 'merge'|'replace' (default 'merge')
     * @return array{
     *   success: bool,
     *   message: string,
     *   changes?: array<string, array{before: mixed, after: mixed}>,
     *   skipped?: array<int, string>
     * }
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

            $existing = get_option($optionName, null);
            $nextValue = self::buildNextOptionValue($existing, $incoming, $importMode);
            if ($existing !== $nextValue) {
                $changes[$optionName] = [
                    'before' => $existing,
                    'after' => $nextValue,
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

    private static function resolveImportMode(array $options): string
    {
        $mode = isset($options['mode']) ? sanitize_text_field((string) $options['mode']) : 'merge';
        if (!in_array($mode, self::SUPPORTED_IMPORT_MODES, true)) {
            return 'merge';
        }

        return $mode;
    }

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
}
