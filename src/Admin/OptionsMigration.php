<?php

declare(strict_types=1);

namespace HMApi\Admin;

use HMApi\Main;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration class to handle transition from wp-settings to hyper fields system.
 * Ensures no data loss during the migration process.
 *
 * @since 2025-07-21
 */
class OptionsMigration
{
    private string $old_option_name = 'hmapi_options';
    private string $migration_version_key = 'hmapi_migration_version';
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    /**
     * Check if migration is needed.
     *
     * @return bool True if migration is needed, false otherwise.
     */
    public function needs_migration(): bool
    {
        $current_version = get_option($this->migration_version_key, '0');

        return version_compare($current_version, '2.1.0', '<');
    }

    /**
     * Perform the migration from wp-settings to hyper fields system.
     *
     * @return bool True if migration was successful, false otherwise.
     */
    public function migrate(): bool
    {
        if (!$this->needs_migration()) {
            return true;
        }

        try {
            // Get current wp-settings data
            $old_options = get_option($this->old_option_name, []);

            if (empty($old_options)) {
                // No existing data, just mark as migrated
                return $this->mark_migration_complete();
            }

            // Validate and sanitize existing data
            $sanitized_options = $this->sanitize_existing_data($old_options);

            // Update to new format (already compatible)
            update_option($this->old_option_name, $sanitized_options);

            // Mark migration as complete
            return $this->mark_migration_complete();

        } catch (\Exception $e) {
            // Log error and return false
            error_log('HMAPI Migration Error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Sanitize and validate existing wp-settings data.
     *
     * @param array $old_options Existing options data.
     * @return array Sanitized options.
     */
    private function sanitize_existing_data(array $old_options): array
    {
        $sanitized = [];

        // Core settings
        $sanitized['active_library'] = $this->sanitize_active_library($old_options['active_library'] ?? 'htmx');
        $sanitized['load_from_cdn'] = isset($old_options['load_from_cdn']) ? (bool) $old_options['load_from_cdn'] : false;

        // HTMX settings
        $sanitized['load_hyperscript'] = isset($old_options['load_hyperscript']) ? (bool) $old_options['load_hyperscript'] : true;
        $sanitized['load_alpinejs_with_htmx'] = isset($old_options['load_alpinejs_with_htmx']) ? (bool) $old_options['load_alpinejs_with_htmx'] : false;
        $sanitized['set_htmx_hxboost'] = isset($old_options['set_htmx_hxboost']) ? (bool) $old_options['set_htmx_hxboost'] : false;
        $sanitized['load_htmx_backend'] = isset($old_options['load_htmx_backend']) ? (bool) $old_options['load_htmx_backend'] : false;

        // Alpine settings
        $sanitized['load_alpinejs_backend'] = isset($old_options['load_alpinejs_backend']) ? (bool) $old_options['load_alpinejs_backend'] : false;

        // Datastar settings
        $sanitized['load_datastar_backend'] = isset($old_options['load_datastar_backend']) ? (bool) $old_options['load_datastar_backend'] : false;

        // HTMX extensions
        $extensions = $this->get_htmx_extension_keys();
        foreach ($extensions as $key) {
            $sanitized['load_extension_' . $key] = isset($old_options['load_extension_' . $key]) ? (bool) $old_options['load_extension_' . $key] : false;
        }

        return $sanitized;
    }

    /**
     * Sanitize the active library value.
     *
     * @param mixed $value Active library value.
     * @return string Sanitized value.
     */
    private function sanitize_active_library($value): string
    {
        $valid_libraries = ['htmx', 'alpinejs', 'datastar'];
        $value = sanitize_text_field((string) $value);

        return in_array($value, $valid_libraries) ? $value : 'htmx';
    }

    /**
     * Get list of HTMX extension keys.
     *
     * @return array Extension keys.
     */
    private function get_htmx_extension_keys(): array
    {
        return [
            'sse',
            'head-support',
            'response-targets',
            'loading-states',
            'debug',
            'path-deps',
            'class-tools',
            'multi-swap',
            'includes',
            'json-enc',
            'method-override',
            'morphdom-swap',
            'client-side-templates',
            'preload',
        ];
    }

    /**
     * Mark migration as complete.
     *
     * @return bool True if successful.
     */
    private function mark_migration_complete(): bool
    {
        return update_option($this->migration_version_key, '2.1.0');
    }

    /**
     * Get backup of current options before migration.
     *
     * @return array Backup data.
     */
    public function create_backup(): array
    {
        $current_options = get_option($this->old_option_name, []);
        $backup_key = $this->old_option_name . '_backup_' . time();

        update_option($backup_key, $current_options);

        return [
            'backup_key' => $backup_key,
            'options' => $current_options,
        ];
    }

    /**
     * Restore from backup if needed.
     *
     * @param string $backup_key Backup option key.
     * @return bool True if restored successfully.
     */
    public function restore_from_backup(string $backup_key): bool
    {
        $backup_data = get_option($backup_key, []);

        if (!empty($backup_data)) {
            return update_option($this->old_option_name, $backup_data);
        }

        return false;
    }

    /**
     * Clean up old backup options.
     *
     * @return int Number of backups removed.
     */
    public function cleanup_backups(): int
    {
        global $wpdb;

        $backup_pattern = $this->old_option_name . '_backup_%';
        $query = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $backup_pattern
        );

        return $wpdb->query($query);
    }

    /**
     * Get migration status.
     *
     * @return array Migration status information.
     */
    public function get_migration_status(): array
    {
        $current_version = get_option($this->migration_version_key, '0');
        $needs_migration = $this->needs_migration();
        $current_options = get_option($this->old_option_name, []);

        return [
            'current_version' => $current_version,
            'needs_migration' => $needs_migration,
            'has_data' => !empty($current_options),
            'data_keys' => array_keys($current_options),
        ];
    }
}
