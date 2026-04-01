<?php

declare(strict_types=1);

namespace HyperFields\Transfer;

use HyperFields\Log;

/**
 * Generic transfer audit logger for HyperFields export/import APIs.
 */
final class AuditLogger
{
    private const LOG_SOURCE = 'hyperfields-transfer-audit';

    private static bool $initialized = false;

    /**
     * Register transfer event hooks once per request.
     */
    public static function init(): void
    {
        if (self::$initialized || !function_exists('add_action')) {
            return;
        }

        self::$initialized = true;

        add_action('hyperfields/export/after', [self::class, 'onOptionsExport'], 10, 5);
        add_action('hyperfields/import/after', [self::class, 'onOptionsImport'], 10, 5);
        add_action('hyperfields/content_export/after', [self::class, 'onContentExport'], 10, 4);
        add_action('hyperfields/content_import/after', [self::class, 'onContentImport'], 10, 3);
        add_action('hyperfields/transfer_manager/export/after', [self::class, 'onManagerExport'], 10, 3);
        add_action('hyperfields/transfer_manager/import/after', [self::class, 'onManagerImport'], 10, 3);
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $payload
     * @param array<int, string> $optionNames
     * @param array<string, mixed> $schemaMap
     */
    public static function onOptionsExport(array $result, array $payload, array $optionNames, string $prefix, array $schemaMap): void
    {
        if (AuditContext::isInsideManager()) {
            return;
        }

        $records = isset($payload['options']) && is_array($payload['options']) ? count($payload['options']) : 0;
        self::writeEvent(
            operation: 'export',
            status: self::statusFromResult($result),
            api: 'export_import_options',
            objectKeys: array_values(array_map('strval', $optionNames)),
            payload: $payload,
            recordsCount: $records,
            errorSummary: self::errorSummary($result),
            context: [
                'prefix' => $prefix,
                'schema_option_count' => count($schemaMap),
            ]
        );
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $decoded
     * @param array<int, string> $allowedOptionNames
     * @param array<string, mixed> $options
     */
    public static function onOptionsImport(array $result, array $decoded, array $allowedOptionNames, string $prefix, array $options): void
    {
        if (AuditContext::isInsideManager()) {
            return;
        }

        $records = 0;
        if (isset($decoded['options']) && is_array($decoded['options'])) {
            $records = count($decoded['options']);
        }

        self::writeEvent(
            operation: 'import',
            status: self::statusFromResult($result),
            api: 'export_import_options',
            objectKeys: array_values(array_map('strval', $allowedOptionNames)),
            payload: $decoded,
            recordsCount: $records,
            errorSummary: self::errorSummary($result),
            context: [
                'prefix' => $prefix,
                'mode' => sanitize_key((string) ($options['mode'] ?? 'merge')),
            ]
        );
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     */
    public static function onContentExport(array $result, array $payload, array $postTypes, array $options): void
    {
        if (AuditContext::isInsideManager()) {
            return;
        }

        $records = 0;
        if (isset($payload['content']['posts']) && is_array($payload['content']['posts'])) {
            $records = count($payload['content']['posts']);
        }

        self::writeEvent(
            operation: 'export',
            status: self::statusFromResult($result),
            api: 'content_export_import',
            objectKeys: array_values(array_map('strval', $postTypes)),
            payload: $payload,
            recordsCount: $records,
            errorSummary: self::errorSummary($result),
            context: [
                'include_meta' => (bool) ($options['include_meta'] ?? true),
                'post_type_count' => count($postTypes),
            ]
        );
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $decoded
     * @param array<string, mixed> $options
     */
    public static function onContentImport(array $result, array $decoded, array $options): void
    {
        if (AuditContext::isInsideManager()) {
            return;
        }

        $records = 0;
        if (isset($decoded['content']['posts']) && is_array($decoded['content']['posts'])) {
            $records = count($decoded['content']['posts']);
        }

        $allowedPostTypes = [];
        if (isset($options['allowed_post_types']) && is_array($options['allowed_post_types'])) {
            $allowedPostTypes = array_values(array_map('strval', $options['allowed_post_types']));
        }

        self::writeEvent(
            operation: 'import',
            status: self::statusFromResult($result),
            api: 'content_export_import',
            objectKeys: $allowedPostTypes,
            payload: $decoded,
            recordsCount: $records,
            errorSummary: self::errorSummary($result),
            context: [
                'dry_run' => !empty($options['dry_run']),
                'create_missing' => !isset($options['create_missing']) || (bool) $options['create_missing'],
                'update_existing' => !isset($options['update_existing']) || (bool) $options['update_existing'],
            ]
        );
    }

    /**
     * @param array<string, mixed> $result
     * @param array<int, string> $moduleKeys
     * @param array<string, mixed> $context
     */
    public static function onManagerExport(array $result, array $moduleKeys, array $context): void
    {
        $modules = isset($result['modules']) && is_array($result['modules']) ? array_keys($result['modules']) : [];
        self::writeEvent(
            operation: 'export',
            status: empty($result['errors']) ? 'success' : 'failed',
            api: 'transfer_manager',
            objectKeys: array_values(array_map('strval', !empty($moduleKeys) ? $moduleKeys : $modules)),
            payload: $result,
            recordsCount: count($modules),
            errorSummary: self::errorsSummary($result['errors'] ?? []),
            context: [
                'context_keys' => array_values(array_map('strval', array_keys($context))),
            ]
        );
    }

    /**
     * @param array<string, mixed> $result
     * @param array<int, string> $moduleKeys
     * @param array<string, mixed> $context
     */
    public static function onManagerImport(array $result, array $moduleKeys, array $context): void
    {
        $modules = isset($result['modules']) && is_array($result['modules']) ? array_keys($result['modules']) : [];
        self::writeEvent(
            operation: 'import',
            status: empty($result['errors']) ? 'success' : 'failed',
            api: 'transfer_manager',
            objectKeys: array_values(array_map('strval', !empty($moduleKeys) ? $moduleKeys : $modules)),
            payload: $result,
            recordsCount: count($modules),
            errorSummary: self::errorsSummary($result['errors'] ?? []),
            context: [
                'dry_run' => !empty($context['dry_run']),
                'context_keys' => array_values(array_map('strval', array_keys($context))),
            ]
        );
    }

    /**
     * @param array<int, string> $objectKeys
     * @param array<string, mixed> $context
     */
    private static function writeEvent(
        string $operation,
        string $status,
        string $api,
        array $objectKeys,
        mixed $payload,
        int $recordsCount,
        string $errorSummary,
        array $context = [],
    ): void {
        $metrics = self::payloadMetrics($payload);
        $event = [
            'operation' => $operation,
            'status' => $status,
            'api' => $api,
            'source' => self::source(),
            'user_id' => function_exists('get_current_user_id') ? (int) get_current_user_id() : 0,
            'object_keys' => $objectKeys,
            'records_count' => max(0, $recordsCount),
            'payload_bytes' => $metrics['bytes'],
            'payload_hash' => $metrics['hash'],
            'error_summary' => $errorSummary,
            'context' => $context,
        ];

        if (AuditLogStorage::insert($event)) {
            return;
        }

        // Fallback path: keep at least one trail if DB audit storage is unavailable.
        Log::log(
            $status === 'failed' ? Log::LOG_LEVEL_ERROR : Log::LOG_LEVEL_INFO,
            sprintf(
                'Transfer audit fallback: operation=%s status=%s api=%s records=%d source=%s',
                $operation,
                $status,
                $api,
                max(0, $recordsCount),
                $event['source']
            ),
            ['source' => self::LOG_SOURCE]
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function statusFromResult(array $result): string
    {
        return !empty($result['success']) ? 'success' : 'failed';
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function errorSummary(array $result): string
    {
        if (!empty($result['success']) && empty($result['errors'])) {
            return '';
        }

        $parts = [];
        if (isset($result['message']) && is_string($result['message']) && $result['message'] !== '') {
            $parts[] = $result['message'];
        }

        if (isset($result['errors']) && is_array($result['errors'])) {
            $parts[] = self::errorsSummary($result['errors']);
        }

        $summary = implode(' | ', array_filter($parts, static fn (string $part): bool => $part !== ''));

        return self::truncate($summary, 1000);
    }

    /**
     * @param mixed $errors
     */
    private static function errorsSummary(mixed $errors): string
    {
        if (!is_array($errors) || $errors === []) {
            return '';
        }

        $limited = array_slice(array_values(array_map('strval', $errors)), 0, 5);
        $summary = implode(' | ', $limited);

        return self::truncate($summary, 1000);
    }

    /**
     * @return array{bytes: int, hash: string}
     */
    private static function payloadMetrics(mixed $payload): array
    {
        $encoded = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($encoded)) {
            return ['bytes' => 0, 'hash' => ''];
        }

        return [
            'bytes' => strlen($encoded),
            'hash' => hash('sha256', $encoded),
        ];
    }

    /**
     * Detects the runtime source for the current transfer operation.
     *
     * @return string One of: cli, cron, ajax, admin, frontend.
     */
    private static function source(): string
    {
        if (defined('WP_CLI') && WP_CLI) {
            return 'cli';
        }

        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return 'cron';
        }

        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return 'ajax';
        }

        if (function_exists('is_admin') && is_admin()) {
            return 'admin';
        }

        return 'frontend';
    }

    /**
     * Truncates text with multibyte support when available.
     *
     * @param string $text Input text.
     * @param int    $maxLength Maximum output length.
     * @return string
     */
    private static function truncate(string $text, int $maxLength): string
    {
        if ($maxLength <= 0) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $maxLength);
        }

        return substr($text, 0, $maxLength);
    }
}
