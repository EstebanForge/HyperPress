<?php

declare(strict_types=1);

namespace HyperFields;

/**
 * Generic bridge for exporting/importing post-like records with metadata.
 *
 * This adapter centralizes conversion between "WP post row" shapes and the
 * ContentExportImport transport shape used by HyperFields.
 */
final class ContentTransferAdapter
{
    /**
     * Exports posts for a post type as normalized rows.
     *
     * @param string $postType
     * @param array<string, mixed> $queryOverrides
     * @return array<int, array<string, mixed>>
     */
    public static function exportRows(string $postType, array $queryOverrides = []): array
    {
        $query = array_merge([
            'post_type' => $postType,
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => false,
        ], $queryOverrides);

        $posts = get_posts($query);
        $rows = [];
        foreach ($posts as $post) {
            if (!($post instanceof \WP_Post)) {
                continue;
            }

            $rows[] = [
                '__strategy' => 'replace',
                'post_type' => (string) $post->post_type,
                'post_name' => (string) $post->post_name,
                'post_title' => (string) $post->post_title,
                'post_status' => (string) $post->post_status,
                'post_parent' => (int) $post->post_parent,
                'menu_order' => (int) $post->menu_order,
                'post_content' => (string) $post->post_content,
                'post_excerpt' => (string) $post->post_excerpt,
                'meta' => self::exportPostMeta((int) $post->ID),
            ];
        }

        return $rows;
    }

    /**
     * Imports normalized post rows through ContentExportImport.
     *
     * @param array<int, mixed> $rows
     * @param array<string, mixed> $options
     * @return array{success: bool, message: string, stats?: array<string, int>, actions?: array<int, array<string, mixed>>, errors?: array<int, string>}
     */
    public static function importRows(array $rows, array $options = []): array
    {
        $defaultPostType = sanitize_key((string) ($options['default_post_type'] ?? ''));
        $allowedPostTypes = isset($options['allowed_post_types']) && is_array($options['allowed_post_types'])
            ? array_values(array_filter(array_map(static fn ($v): string => sanitize_key((string) $v), $options['allowed_post_types'])))
            : [];
        if (empty($allowedPostTypes) && $defaultPostType !== '') {
            $allowedPostTypes = [$defaultPostType];
        }

        $contentRows = self::normalizeRowsForImport($rows, $defaultPostType);
        $json = wp_json_encode([
            'content' => [
                'posts' => $contentRows,
            ],
        ]);

        if (!is_string($json) || $json === '') {
            return [
                'success' => false,
                'message' => 'Failed to encode content import payload.',
                'errors' => ['Failed to encode content import payload.'],
                'actions' => [],
            ];
        }

        return ContentExportImport::importPosts($json, [
            'allowed_post_types' => $allowedPostTypes,
            'dry_run' => (bool) ($options['dry_run'] ?? false),
            'create_missing' => !isset($options['create_missing']) || (bool) $options['create_missing'],
            'update_existing' => !isset($options['update_existing']) || (bool) $options['update_existing'],
            'include_meta' => !isset($options['include_meta']) || (bool) $options['include_meta'],
            'meta_mode' => ((string) ($options['meta_mode'] ?? 'merge')) === 'replace' ? 'replace' : 'merge',
            'include_private_meta' => !isset($options['include_private_meta']) || (bool) $options['include_private_meta'],
            'normalization_profile' => sanitize_key((string) ($options['normalization_profile'] ?? '')),
        ]);
    }

    /**
     * Converts module import actions into imported/skipped summaries.
     *
     * @param array<string, mixed> $importResult
     * @param callable|null $keyBuilder fn(array $actionRow, string $slug): string
     * @return array{imported: array<int, string>, skipped: array<int, array{key: string, reason: string}>}
     */
    public static function summarizeImportActions(array $importResult, ?callable $keyBuilder = null): array
    {
        $imported = [];
        $skipped = [];
        $actions = $importResult['actions'] ?? [];
        if (!is_array($actions)) {
            return ['imported' => $imported, 'skipped' => $skipped];
        }

        $buildKey = $keyBuilder ?? static fn (array $actionRow, string $slug): string => $slug !== '' ? $slug : 'unknown';

        foreach ($actions as $actionRow) {
            if (!is_array($actionRow)) {
                continue;
            }

            $slug = (string) ($actionRow['slug'] ?? '');
            $key = (string) $buildKey($actionRow, $slug);
            $action = sanitize_key((string) ($actionRow['action'] ?? ''));

            if (in_array($action, ['merge', 'create', 'recreate', 'delete'], true)) {
                $imported[] = $key;
                continue;
            }

            if ($action === 'unchanged') {
                $skipped[] = ['key' => $key, 'reason' => 'no changes detected'];
                continue;
            }

            $reason = (string) ($actionRow['reason'] ?? 'skipped');
            $skipped[] = ['key' => $key, 'reason' => $reason !== '' ? $reason : 'skipped'];
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Maps module rows into ContentExportImport transport rows.
     *
     * @param array<int, mixed> $rows
     * @param string $defaultPostType
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeRowsForImport(array $rows, string $defaultPostType = ''): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized[] = [
                '__strategy' => (string) ($row['__strategy'] ?? 'replace'),
                'post_type' => (string) ($row['post_type'] ?? $defaultPostType),
                'slug' => (string) ($row['post_name'] ?? ''),
                'title' => (string) ($row['post_title'] ?? ''),
                'status' => (string) ($row['post_status'] ?? 'draft'),
                'menu_order' => (int) ($row['menu_order'] ?? 0),
                'content' => (string) ($row['post_content'] ?? ''),
                'excerpt' => (string) ($row['post_excerpt'] ?? ''),
                'meta' => is_array($row['meta'] ?? null) ? $row['meta'] : [],
            ];
        }

        return $normalized;
    }

    /**
     * @param int $postId
     * @return array<string, mixed>
     */
    private static function exportPostMeta(int $postId): array
    {
        $meta = get_post_meta($postId);
        if (!is_array($meta)) {
            return [];
        }

        $normalized = [];
        foreach ($meta as $key => $values) {
            if (!is_string($key) || !is_array($values)) {
                continue;
            }

            $mapped = array_map(
                static fn ($value): mixed => maybe_unserialize($value),
                $values
            );
            $normalized[$key] = count($mapped) === 1 ? $mapped[0] : $mapped;
        }

        ksort($normalized);
        return $normalized;
    }
}
