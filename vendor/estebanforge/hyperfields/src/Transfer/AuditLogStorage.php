<?php

declare(strict_types=1);

namespace HyperFields\Transfer;

use wpdb;

/**
 * Stores HyperFields transfer audit metadata in a dedicated WP table.
 *
 * Schema setup is lazy and idempotent:
 * - Request guard: runs at most once per request.
 * - Version guard: skips migration when schema version is current.
 * - Lock guard: short-lived transient lock to reduce concurrent migration work.
 */
final class AuditLogStorage
{
    private const TABLE_SLUG = 'hyperfields_transfer_logs';
    private const SCHEMA_VERSION = '1';
    private const SCHEMA_VERSION_OPTION = 'hyperfields_transfer_logs_schema_version';
    private const SCHEMA_LOCK_KEY = 'hyperfields_transfer_logs_schema_lock';
    private const SCHEMA_LOCK_TTL = 30;
    private const PRUNE_LAST_RUN_OPTION = 'hyperfields_transfer_logs_prune_last_run';
    private const DEFAULT_RETENTION_DAYS = 180;
    private const DEFAULT_PRUNE_INTERVAL_SECONDS = 86400;

    private static bool $schemaCheckedThisRequest = false;
    private static bool $schemaReady = false;
    private static bool $prunedThisRequest = false;

    /**
     * Ensures the audit table exists and schema version is up to date.
     */
    public static function ensureSchema(): bool
    {
        if (self::$schemaCheckedThisRequest) {
            return self::$schemaReady;
        }

        self::$schemaCheckedThisRequest = true;

        if (!function_exists('get_option') || !function_exists('set_transient') || !function_exists('delete_transient')) {
            self::$schemaReady = false;

            return false;
        }

        $installedVersion = (string) get_option(self::SCHEMA_VERSION_OPTION, '');
        if ($installedVersion === self::SCHEMA_VERSION && self::tableExists()) {
            self::$schemaReady = true;

            return true;
        }

        if (get_transient(self::SCHEMA_LOCK_KEY)) {
            self::$schemaReady = self::tableExists();

            return self::$schemaReady;
        }

        set_transient(self::SCHEMA_LOCK_KEY, '1', self::SCHEMA_LOCK_TTL);
        try {
            self::runMigration();
            if (self::tableExists()) {
                update_option(self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION, false);
                self::$schemaReady = true;

                return true;
            }
        } catch (\Throwable) {
            self::$schemaReady = false;

            return false;
        } finally {
            delete_transient(self::SCHEMA_LOCK_KEY);
        }

        self::$schemaReady = false;

        return false;
    }

    /**
     * Inserts one audit event row.
     *
     * @param array{
     *   operation: string,
     *   status: string,
     *   api: string,
     *   source: string,
     *   user_id: int,
     *   object_keys?: array<int, string>,
     *   records_count?: int,
     *   payload_bytes?: int,
     *   payload_hash?: string,
     *   error_summary?: string,
     *   context?: array<string, mixed>
     * } $event
     */
    public static function insert(array $event): bool
    {
        if (!self::ensureSchema()) {
            return false;
        }

        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return false;
        }

        $table = self::tableName();
        $objectKeys = isset($event['object_keys']) && is_array($event['object_keys']) ? $event['object_keys'] : [];
        $context = isset($event['context']) && is_array($event['context']) ? $event['context'] : [];

        $inserted = $wpdb->insert(
            $table,
            [
                'operation' => sanitize_key($event['operation']),
                'status' => sanitize_key($event['status']),
                'api' => sanitize_key($event['api']),
                'source' => sanitize_key($event['source']),
                'user_id' => max(0, (int) $event['user_id']),
                'object_keys' => wp_json_encode(array_values(array_map('strval', $objectKeys))),
                'records_count' => max(0, (int) ($event['records_count'] ?? 0)),
                'payload_bytes' => max(0, (int) ($event['payload_bytes'] ?? 0)),
                'payload_hash' => sanitize_text_field((string) ($event['payload_hash'] ?? '')),
                'error_summary' => sanitize_textarea_field((string) ($event['error_summary'] ?? '')),
                'context' => wp_json_encode($context),
                // Store in site-local WordPress time so operators see timezone-consistent logs.
                'created_at' => self::siteDateTime(),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        return $inserted === 1;
    }

    /**
     * Lazy cleanup of expired rows based on retention policy.
     *
     * Intended to run from read paths, not every request.
     */
    public static function maybePruneExpired(): void
    {
        if (self::$prunedThisRequest) {
            return;
        }
        self::$prunedThisRequest = true;

        if (!self::ensureSchema()) {
            return;
        }

        if (!function_exists('get_option') || !function_exists('update_option') || !function_exists('apply_filters')) {
            return;
        }

        $interval = self::pruneIntervalSeconds();
        $lastRun = (int) get_option(self::PRUNE_LAST_RUN_OPTION, 0);
        if ($lastRun > 0 && (time() - $lastRun) < $interval) {
            return;
        }

        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return;
        }

        $retentionDays = self::retentionDays();
        $cutoffUnix = time() - ($retentionDays * DAY_IN_SECONDS);
        // Match the storage timezone (WordPress site-local time).
        $cutoff = self::siteDateTime($cutoffUnix);
        $table = self::tableName();
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        update_option(self::PRUNE_LAST_RUN_OPTION, time(), false);
    }

    /**
     * Returns a paginated set of transfer logs.
     *
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   total: int,
     *   page: int,
     *   per_page: int,
     *   total_pages: int
     * }
     */
    public static function fetchPage(int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));

        if (!self::ensureSchema()) {
            return [
                'rows' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 1,
            ];
        }

        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return [
                'rows' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 1,
            ];
        }

        $table = self::tableName();
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $sql = $wpdb->prepare(
            "SELECT id, operation, status, api, source, user_id, object_keys, records_count, payload_bytes, payload_hash, error_summary, created_at
            FROM {$table}
            ORDER BY id DESC
            LIMIT %d OFFSET %d",
            $perPage,
            $offset
        );
        $rows = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $rows = is_array($rows) ? $rows : [];

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Runs the table migration using WordPress `dbDelta`.
     *
     * @return void
     */
    private static function runMigration(): void
    {
        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return;
        }

        if (defined('ABSPATH')) {
            $upgradeFile = ABSPATH . 'wp-admin/includes/upgrade.php';
            if (file_exists($upgradeFile)) {
                require_once $upgradeFile;
            }
        }

        if (!function_exists('dbDelta')) {
            return;
        }

        $table = self::tableName();
        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
operation varchar(20) NOT NULL,
status varchar(20) NOT NULL,
api varchar(64) NOT NULL,
source varchar(32) NOT NULL,
user_id bigint(20) unsigned NOT NULL DEFAULT 0,
object_keys longtext NULL,
records_count int(10) unsigned NOT NULL DEFAULT 0,
payload_bytes bigint(20) unsigned NOT NULL DEFAULT 0,
payload_hash char(64) NULL,
error_summary text NULL,
context longtext NULL,
created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY  (id),
KEY operation (operation),
KEY status (status),
KEY api (api),
KEY created_at (created_at),
KEY user_id (user_id)
) {$charsetCollate};";

        dbDelta($sql);
    }

    /**
     * Returns whether the audit table exists in the active WordPress database.
     *
     * @return bool
     */
    private static function tableExists(): bool
    {
        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return false;
        }

        $table = self::tableName();
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

        return is_string($found) && $found === $table;
    }

    /**
     * Builds the fully-qualified transfer logs table name.
     *
     * @return string
     */
    private static function tableName(): string
    {
        global $wpdb;
        if (!$wpdb instanceof wpdb) {
            return self::TABLE_SLUG;
        }

        return $wpdb->prefix . self::TABLE_SLUG;
    }

    /**
     * Resolves retention days from filter and applies a safe minimum.
     *
     * @return int
     */
    private static function retentionDays(): int
    {
        $days = self::DEFAULT_RETENTION_DAYS;
        if (function_exists('apply_filters')) {
            $days = (int) apply_filters('hyperfields/transfer_logs/retention_days', $days);
        }

        return max(1, $days);
    }

    /**
     * Resolves prune interval from filter and applies a safe minimum.
     *
     * @return int
     */
    private static function pruneIntervalSeconds(): int
    {
        $seconds = self::DEFAULT_PRUNE_INTERVAL_SECONDS;
        if (function_exists('apply_filters')) {
            $seconds = (int) apply_filters('hyperfields/transfer_logs/prune_interval_seconds', $seconds);
        }

        return max(60, $seconds);
    }

    /**
     * Formats a timestamp using the WordPress site timezone.
     *
     * @param int|null $timestamp Unix timestamp, defaults to current time.
     * @return string
     */
    private static function siteDateTime(?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();

        if (function_exists('wp_date')) {
            return wp_date('Y-m-d H:i:s', $timestamp);
        }

        if (function_exists('current_time') && $timestamp === time()) {
            return (string) current_time('mysql');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
