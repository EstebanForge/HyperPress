<?php

declare(strict_types=1);

namespace HyperFields\Admin;

use HyperFields\Transfer\AuditLogStorage;

/**
 * Renders and routes the HyperFields transfer logs admin view.
 */
class TransferLogsUI
{
    private const VIEW_QUERY_ARG = 'hf_view';
    private const VIEW_QUERY_VALUE = 'transfer_logs';
    private const PAGE_QUERY_ARG = 'paged';
    private const PER_PAGE = 25;

    /**
     * Returns whether transfer logs UI is available.
     *
     * @return bool True when storage exists and the UI filter is enabled.
     */
    public static function isEnabled(): bool
    {
        if (!class_exists(AuditLogStorage::class)) {
            return false;
        }

        return (bool) apply_filters('hyperfields/transfer_logs/ui_enabled', true);
    }

    /**
     * Returns whether the current request targets the transfer logs view.
     *
     * @return bool True when `hf_view=transfer_logs` is present.
     */
    public static function isLogsViewRequest(): bool
    {
        $view = isset($_GET[self::VIEW_QUERY_ARG]) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? sanitize_text_field(wp_unslash($_GET[self::VIEW_QUERY_ARG])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : '';

        return $view === self::VIEW_QUERY_VALUE;
    }

    /**
     * Builds the current page URL for returning from the logs screen.
     *
     * @return string Admin URL of the current HyperFields page.
     */
    public static function pageBaseUrl(): string
    {
        $slug = self::currentAdminPageSlug();

        return admin_url('admin.php?page=' . rawurlencode($slug));
    }

    /**
     * Builds the transfer logs view URL for the current HyperFields page.
     *
     * @return string Transfer logs screen URL.
     */
    public static function logsUrl(): string
    {
        return add_query_arg(
            [
                'page' => self::currentAdminPageSlug(),
                self::VIEW_QUERY_ARG => self::VIEW_QUERY_VALUE,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Renders the transfer logs table page.
     *
     * @return string HTML for the logs view.
     */
    public static function renderPage(): string
    {
        AuditLogStorage::maybePruneExpired();

        $page = isset($_GET[self::PAGE_QUERY_ARG]) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? max(1, (int) $_GET[self::PAGE_QUERY_ARG]) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : 1;

        $logs = AuditLogStorage::fetchPage($page, self::PER_PAGE);
        $retentionDays = max(1, (int) apply_filters('hyperfields/transfer_logs/retention_days', 180));
        $timezoneLabel = function_exists('wp_timezone_string') ? wp_timezone_string() : '';
        if ($timezoneLabel === '') {
            $timezoneLabel = 'UTC';
        }

        $backUrl = self::pageBaseUrl();
        $baseUrl = self::logsUrl();

        ob_start();
        ?>
        <div class="wrap hyperpress hyperpress-options-wrap">
            <h1><?php esc_html_e('Transfer Logs', 'hyperfields'); ?></h1>
            <p><?php echo esc_html(sprintf(__('Audit trail for HyperFields export/import activity. Older rows are pruned lazily based on a %d-day retention policy.', 'hyperfields'), $retentionDays)); ?></p>
            <p><a href="<?php echo esc_url($backUrl); ?>">&larr; <?php esc_html_e('Back to Export / Import', 'hyperfields'); ?></a></p>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html(sprintf(__('Date (%s)', 'hyperfields'), $timezoneLabel)); ?></th>
                        <th><?php esc_html_e('Operation', 'hyperfields'); ?></th>
                        <th><?php esc_html_e('Status', 'hyperfields'); ?></th>
                        <th><?php esc_html_e('API', 'hyperfields'); ?></th>
                        <th><?php esc_html_e('Records', 'hyperfields'); ?></th>
                        <th><?php esc_html_e('User', 'hyperfields'); ?></th>
                        <th><?php esc_html_e('Objects', 'hyperfields'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs['rows'])): ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e('No transfer logs found.', 'hyperfields'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs['rows'] as $row): ?>
                        <tr>
                            <td><?php echo esc_html((string) ($row['created_at'] ?? '')); ?></td>
                            <td><?php echo esc_html(self::formatOperation((array) $row)); ?></td>
                            <td title="<?php echo esc_attr((string) ($row['error_summary'] ?? '')); ?>">
                                <?php echo esc_html(self::formatStatus((array) $row)); ?>
                            </td>
                            <td><?php echo esc_html((string) ($row['api'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($row['records_count'] ?? '0')); ?></td>
                            <td><?php echo esc_html(self::formatUser((array) $row)); ?></td>
                            <td><?php echo esc_html(self::formatObjectsPreview((array) $row)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <?php self::renderPagination($logs, $baseUrl); ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Returns the current admin page slug.
     *
     * @return string Sanitized `page` value from query string.
     */
    private static function currentAdminPageSlug(): string
    {
        return isset($_GET['page']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? sanitize_text_field(wp_unslash($_GET['page'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : '';
    }

    /**
     * Formats the operation label with the source context.
     *
     * @param array $row A single transfer log row.
     * @return string Operation text including source.
     */
    private static function formatOperation(array $row): string
    {
        $rawSource = sanitize_key((string) ($row['source'] ?? ''));
        $sourceLabel = match ($rawSource) {
            'admin' => __('admin', 'hyperfields'),
            'ajax' => __('ajax', 'hyperfields'),
            'cli' => __('cli', 'hyperfields'),
            'cron' => __('system cron', 'hyperfields'),
            'frontend' => __('frontend', 'hyperfields'),
            default => $rawSource !== '' ? $rawSource : __('unknown', 'hyperfields'),
        };
        $operation = (string) ($row['operation'] ?? '');

        return sprintf('%s (%s %s)', $operation, __('via', 'hyperfields'), $sourceLabel);
    }

    /**
     * Formats row status for display.
     *
     * @param array $row A single transfer log row.
     * @return string `success` or `error`.
     */
    private static function formatStatus(array $row): string
    {
        $rawStatus = sanitize_key((string) ($row['status'] ?? ''));

        return $rawStatus === 'success' ? 'success' : 'error';
    }

    /**
     * Formats the actor label for a row.
     *
     * @param array $row A single transfer log row.
     * @return string User login plus ID, or `System`.
     */
    private static function formatUser(array $row): string
    {
        $userId = isset($row['user_id']) ? (int) $row['user_id'] : 0;
        $userLabel = $userId > 0 ? (string) $userId : __('System', 'hyperfields');

        if ($userId > 0) {
            $user = get_userdata($userId);
            if ($user && isset($user->user_login)) {
                $userLabel = $user->user_login . ' (#' . $userId . ')';
            }
        }

        return $userLabel;
    }

    /**
     * Formats object keys into a shortened comma-separated preview.
     *
     * @param array $row A single transfer log row.
     * @return string First four object keys with ellipsis when truncated.
     */
    private static function formatObjectsPreview(array $row): string
    {
        $objectsRaw = isset($row['object_keys']) ? (string) $row['object_keys'] : '';
        $objects = json_decode($objectsRaw, true);
        $objects = is_array($objects) ? array_values(array_map('strval', $objects)) : [];
        $objectsPreview = implode(', ', array_slice($objects, 0, 4));
        if (count($objects) > 4) {
            $objectsPreview .= ', ...';
        }

        return $objectsPreview;
    }

    /**
     * Outputs pagination controls for the transfer logs table.
     *
     * @param array  $logs    Paginated logs payload.
     * @param string $baseUrl Base URL used to build pagination links.
     * @return void
     */
    private static function renderPagination(array $logs, string $baseUrl): void
    {
        $pagination = paginate_links(
            [
                'base' => add_query_arg(self::PAGE_QUERY_ARG, '%#%', $baseUrl),
                'format' => '',
                'current' => (int) ($logs['page'] ?? 1),
                'total' => (int) ($logs['total_pages'] ?? 1),
                'type' => 'array',
                'prev_text' => __('« Previous', 'hyperfields'),
                'next_text' => __('Next »', 'hyperfields'),
            ]
        );
        if (!is_array($pagination) || empty($pagination)) {
            return;
        }
        ?>
        <div class="tablenav" style="margin-top:12px;">
            <div class="tablenav-pages">
                <?php foreach ($pagination as $pageLink): ?>
                    <?php echo wp_kses_post($pageLink); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
