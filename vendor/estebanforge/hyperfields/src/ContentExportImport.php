<?php

declare(strict_types=1);

namespace HyperFields;

/**
 * Generic export/import utility for post-like configuration content.
 *
 * Supports pages and CPT records matched by post_type + slug.
 */
class ContentExportImport
{
    private const SCHEMA_VERSION = '1.0';

    /** @var array<int, string> */
    private const DEFAULT_EXCLUDED_META_KEYS = [
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
    ];

    private const WP_OBJECT_OUTPUT = 'OBJECT';

    /**
     * Export posts/pages/CPT records to a JSON payload.
     *
     * @param array $postTypes Post types to export.
     * @param array $options   Optional behavior:
     *                         - post_status: string[]
     *                         - include_meta: bool (default true)
     *                         - include_private_meta: bool (default false)
     *                         - include_meta_keys: string[] allowlist
     *                         - exclude_meta_keys: string[] denylist
     *                         - include_content: bool (default true)
     *                         - include_excerpt: bool (default true)
     *                         - include_parent: bool (default true)
     */
    public static function exportPosts(array $postTypes, array $options = []): string
    {
        $postTypes = self::sanitizePostTypes($postTypes);
        $settings = self::normalizeSettings($options);

        $query = [
            'post_type' => $postTypes,
            'post_status' => $settings['post_status'],
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => false,
        ];

        $posts = !empty($postTypes) ? get_posts($query) : [];
        $exported = [];
        foreach ($posts as $post) {
            $normalized = self::normalizeExportPost($post, $settings);
            if ($normalized !== null) {
                $exported[] = $normalized;
            }
        }

        $encoded = wp_json_encode(
            [
                'version' => self::SCHEMA_VERSION,
                'type' => 'hyperfields_content_export',
                'scope' => 'posts',
                'exported_at' => current_time('mysql'),
                'site_url' => get_site_url(),
                'content' => [
                    'posts' => $exported,
                ],
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        return $encoded !== false ? $encoded : '{}';
    }

    /**
     * Snapshot current posts in-memory for dry-run compare workflows.
     *
     * @param array $postTypes Post types to snapshot.
     * @param array $options Same settings as exportPosts().
     * @return array<string, array<string, mixed>>
     */
    public static function snapshotPosts(array $postTypes, array $options = []): array
    {
        $postTypes = self::sanitizePostTypes($postTypes);
        $settings = self::normalizeSettings($options);

        $query = [
            'post_type' => $postTypes,
            'post_status' => $settings['post_status'],
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => false,
        ];

        $posts = !empty($postTypes) ? get_posts($query) : [];
        $snapshot = [];
        foreach ($posts as $post) {
            $normalized = self::normalizeExportPost($post, $settings);
            if ($normalized === null) {
                continue;
            }

            $key = $normalized['post_type'] . ':' . $normalized['slug'];
            $snapshot[$key] = $normalized;
        }

        return $snapshot;
    }

    /**
     * Import posts/pages/CPT records from an export payload.
     *
     * @param string $jsonString Export JSON produced by exportPosts().
     * @param array  $options Optional behavior:
     *                        - allowed_post_types: string[]
     *                        - dry_run: bool (default false)
     *                        - create_missing: bool (default true)
     *                        - update_existing: bool (default true)
     *                        - include_meta: bool (default true)
     *                        - meta_mode: 'merge'|'replace' (default 'merge')
     *                        - include_private_meta: bool (default false)
     *                        - include_meta_keys: string[] allowlist
     *                        - exclude_meta_keys: string[] denylist
     * @return array{
     *   success: bool,
     *   message: string,
     *   stats: array<string, int>,
     *   actions: array<int, array<string, mixed>>,
     *   errors: array<int, string>
     * }
     */
    public static function importPosts(string $jsonString, array $options = []): array
    {
        if ($jsonString === '') {
            return self::result(false, 'Empty import data.');
        }

        $decoded = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return self::result(false, 'Invalid JSON: ' . json_last_error_msg());
        }

        if (
            !is_array($decoded)
            || !isset($decoded['content'])
            || !is_array($decoded['content'])
            || !isset($decoded['content']['posts'])
            || !is_array($decoded['content']['posts'])
        ) {
            return self::result(false, 'Invalid export format. Expected "content.posts" as an array.');
        }

        $settings = self::normalizeSettings($options);
        $allowedPostTypes = self::sanitizePostTypes($options['allowed_post_types'] ?? []);
        $dryRun = !empty($options['dry_run']);
        $createMissing = !isset($options['create_missing']) || (bool) $options['create_missing'];
        $updateExisting = !isset($options['update_existing']) || (bool) $options['update_existing'];
        $metaMode = isset($options['meta_mode']) && $options['meta_mode'] === 'replace' ? 'replace' : 'merge';

        $stats = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'meta_updates' => 0,
        ];
        $actions = [];
        $errors = [];

        foreach ($decoded['content']['posts'] as $row) {
            if (!is_array($row)) {
                $stats['skipped']++;
                $errors[] = 'Skipped content row: invalid payload item type.';
                continue;
            }

            $postType = sanitize_key((string) ($row['post_type'] ?? ''));
            $slug = sanitize_title((string) ($row['slug'] ?? ''));
            if ($postType === '' || $slug === '') {
                $stats['skipped']++;
                $errors[] = 'Skipped content row: post_type or slug missing.';
                continue;
            }

            if (!empty($allowedPostTypes) && !in_array($postType, $allowedPostTypes, true)) {
                $stats['skipped']++;
                $actions[] = [
                    'action' => 'skip',
                    'post_type' => $postType,
                    'slug' => $slug,
                    'reason' => 'post_type_not_allowed',
                ];
                continue;
            }

            $existing = get_page_by_path($slug, self::WP_OBJECT_OUTPUT, $postType);
            $hasExisting = is_object($existing) && isset($existing->ID);
            $postData = self::buildImportPostData($row, $postType, $slug, $settings);

            $targetId = 0;
            if ($hasExisting) {
                $targetId = (int) $existing->ID;
                if (!$updateExisting) {
                    $stats['skipped']++;
                    $actions[] = [
                        'action' => 'skip',
                        'post_type' => $postType,
                        'slug' => $slug,
                        'reason' => 'update_disabled',
                    ];
                    continue;
                }

                $postData['ID'] = $targetId;
                $needsUpdate = self::postNeedsUpdate($existing, $postData);
                if (!$needsUpdate) {
                    $stats['unchanged']++;
                    $actions[] = [
                        'action' => 'unchanged',
                        'post_type' => $postType,
                        'slug' => $slug,
                    ];
                } elseif ($dryRun) {
                    $stats['updated']++;
                    $actions[] = [
                        'action' => 'update',
                        'post_type' => $postType,
                        'slug' => $slug,
                        'dry_run' => true,
                    ];
                } else {
                    $updated = wp_update_post($postData, true);
                    if (self::isWpError($updated)) {
                        $stats['skipped']++;
                        $errors[] = 'Failed updating ' . $postType . ':' . $slug . ' - ' . self::wpErrorMessage($updated);
                        continue;
                    }
                    $stats['updated']++;
                    $actions[] = [
                        'action' => 'update',
                        'post_type' => $postType,
                        'slug' => $slug,
                        'id' => $targetId,
                    ];
                }
            } else {
                if (!$createMissing) {
                    $stats['skipped']++;
                    $actions[] = [
                        'action' => 'skip',
                        'post_type' => $postType,
                        'slug' => $slug,
                        'reason' => 'create_disabled',
                    ];
                    continue;
                }

                if ($dryRun) {
                    $stats['created']++;
                    $actions[] = [
                        'action' => 'create',
                        'post_type' => $postType,
                        'slug' => $slug,
                        'dry_run' => true,
                    ];
                } else {
                    $created = wp_insert_post($postData, true);
                    if (self::isWpError($created)) {
                        $stats['skipped']++;
                        $errors[] = 'Failed creating ' . $postType . ':' . $slug . ' - ' . self::wpErrorMessage($created);
                        continue;
                    }
                    $targetId = (int) $created;
                    $stats['created']++;
                    $actions[] = [
                        'action' => 'create',
                        'post_type' => $postType,
                        'slug' => $slug,
                        'id' => $targetId,
                    ];
                }
            }

            if (empty($settings['include_meta'])) {
                continue;
            }

            if (!isset($row['meta']) || !is_array($row['meta'])) {
                continue;
            }

            $metaApplied = self::applyPostMeta(
                $targetId,
                $row['meta'],
                $settings,
                $dryRun,
                $metaMode
            );
            $stats['meta_updates'] += $metaApplied;
        }

        $success = empty($errors);
        $message = $success ? 'Content import completed.' : 'Content import completed with errors.';

        return [
            'success' => $success,
            'message' => $message,
            'stats' => $stats,
            'actions' => $actions,
            'errors' => $errors,
        ];
    }

    /**
     * Produce a dry-run compare report for an incoming content payload.
     *
     * @param string $jsonString Export JSON from exportPosts().
     * @param array  $options Import options supported by importPosts().
     * @return array{success: bool, message: string, stats?: array<string, int>, actions?: array<int, array<string, mixed>>, errors?: array<int, string>}
     */
    public static function diffPosts(string $jsonString, array $options = []): array
    {
        $options['dry_run'] = true;
        return self::importPosts($jsonString, $options);
    }

    private static function normalizeExportPost(mixed $post, array $settings): ?array
    {
        if (!is_object($post) || !isset($post->ID, $post->post_type, $post->post_name)) {
            return null;
        }

        $postId = (int) $post->ID;
        $export = [
            'id' => $postId,
            'post_type' => sanitize_key((string) $post->post_type),
            'slug' => sanitize_title((string) $post->post_name),
            'title' => (string) ($post->post_title ?? ''),
            'status' => sanitize_key((string) ($post->post_status ?? 'draft')),
            'menu_order' => (int) ($post->menu_order ?? 0),
            'comment_status' => (string) ($post->comment_status ?? 'closed'),
            'ping_status' => (string) ($post->ping_status ?? 'closed'),
        ];

        if (!empty($settings['include_content'])) {
            $export['content'] = (string) ($post->post_content ?? '');
        }

        if (!empty($settings['include_excerpt'])) {
            $export['excerpt'] = (string) ($post->post_excerpt ?? '');
        }

        if (!empty($settings['include_parent'])) {
            $parentSlug = '';
            $parentId = (int) ($post->post_parent ?? 0);
            if ($parentId > 0) {
                $parent = get_post($parentId);
                if (is_object($parent) && isset($parent->post_name)) {
                    $parentSlug = sanitize_title((string) $parent->post_name);
                }
            }
            $export['parent_slug'] = $parentSlug;
        }

        if (!empty($settings['include_meta'])) {
            $export['meta'] = self::collectPostMeta($postId, $settings);
        }

        return $export;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildImportPostData(array $row, string $postType, string $slug, array $settings): array
    {
        $postData = [
            'post_type' => $postType,
            'post_name' => $slug,
            'post_title' => sanitize_text_field((string) ($row['title'] ?? '')),
            'post_status' => sanitize_key((string) ($row['status'] ?? 'draft')),
            'menu_order' => (int) ($row['menu_order'] ?? 0),
            'comment_status' => sanitize_key((string) ($row['comment_status'] ?? 'closed')),
            'ping_status' => sanitize_key((string) ($row['ping_status'] ?? 'closed')),
        ];

        if (!empty($settings['include_content']) && isset($row['content'])) {
            $postData['post_content'] = (string) $row['content'];
        }

        if (!empty($settings['include_excerpt']) && isset($row['excerpt'])) {
            $postData['post_excerpt'] = (string) $row['excerpt'];
        }

        if (!empty($settings['include_parent']) && !empty($row['parent_slug'])) {
            $parentSlug = sanitize_title((string) $row['parent_slug']);
            if ($parentSlug !== '') {
                $parent = get_page_by_path($parentSlug, self::WP_OBJECT_OUTPUT, $postType);
                if (is_object($parent) && isset($parent->ID)) {
                    $postData['post_parent'] = (int) $parent->ID;
                }
            }
        }

        return $postData;
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeSettings(array $options): array
    {
        $postStatus = isset($options['post_status']) && is_array($options['post_status'])
            ? array_values(array_filter(array_map(static fn($status): string => sanitize_key((string) $status), $options['post_status'])))
            : ['publish', 'draft', 'private'];

        return [
            'post_status' => !empty($postStatus) ? $postStatus : ['publish', 'draft', 'private'],
            'include_meta' => !isset($options['include_meta']) || (bool) $options['include_meta'],
            'include_private_meta' => !empty($options['include_private_meta']),
            'include_meta_keys' => self::sanitizeMetaKeys($options['include_meta_keys'] ?? []),
            'exclude_meta_keys' => self::sanitizeMetaKeys($options['exclude_meta_keys'] ?? self::DEFAULT_EXCLUDED_META_KEYS),
            'include_content' => !isset($options['include_content']) || (bool) $options['include_content'],
            'include_excerpt' => !isset($options['include_excerpt']) || (bool) $options['include_excerpt'],
            'include_parent' => !isset($options['include_parent']) || (bool) $options['include_parent'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function sanitizePostTypes(array $postTypes): array
    {
        $types = [];
        foreach ($postTypes as $postType) {
            $value = sanitize_key((string) $postType);
            if ($value === '') {
                continue;
            }
            $types[] = $value;
        }

        return array_values(array_unique($types));
    }

    /**
     * @return array<int, string>
     */
    private static function sanitizeMetaKeys(array $metaKeys): array
    {
        $keys = [];
        foreach ($metaKeys as $metaKey) {
            $value = sanitize_key((string) $metaKey);
            if ($value === '') {
                continue;
            }
            $keys[] = $value;
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private static function collectPostMeta(int $postId, array $settings): array
    {
        $rawMeta = get_post_meta($postId);
        if (!is_array($rawMeta)) {
            return [];
        }

        $includeKeys = $settings['include_meta_keys'];
        $excludeKeys = $settings['exclude_meta_keys'];
        $includePrivate = !empty($settings['include_private_meta']);

        $meta = [];
        foreach ($rawMeta as $key => $values) {
            $metaKey = (string) $key;
            if ($metaKey === '') {
                continue;
            }

            if (!empty($includeKeys) && !in_array($metaKey, $includeKeys, true)) {
                continue;
            }

            if (in_array($metaKey, $excludeKeys, true)) {
                continue;
            }

            if (!$includePrivate && strpos($metaKey, '_') === 0 && empty($includeKeys)) {
                continue;
            }

            if (!is_array($values)) {
                continue;
            }

            $meta[$metaKey] = array_map(
                static fn($value): mixed => maybe_unserialize($value),
                $values
            );
        }

        return $meta;
    }

    /**
     * @param array<string, array<int, mixed>> $metaPayload
     */
    private static function applyPostMeta(int $postId, array $metaPayload, array $settings, bool $dryRun, string $metaMode): int
    {
        if ($postId <= 0 && !$dryRun) {
            return 0;
        }

        $includeKeys = $settings['include_meta_keys'];
        $excludeKeys = $settings['exclude_meta_keys'];
        $includePrivate = !empty($settings['include_private_meta']);

        $incoming = [];
        foreach ($metaPayload as $key => $values) {
            $metaKey = (string) $key;
            if ($metaKey === '' || !is_array($values)) {
                continue;
            }

            if (!empty($includeKeys) && !in_array($metaKey, $includeKeys, true)) {
                continue;
            }

            if (in_array($metaKey, $excludeKeys, true)) {
                continue;
            }

            if (!$includePrivate && strpos($metaKey, '_') === 0 && empty($includeKeys)) {
                continue;
            }

            $incoming[$metaKey] = $values;
        }

        if (empty($incoming)) {
            return 0;
        }

        if ($dryRun) {
            return count($incoming);
        }

        if ($metaMode === 'replace') {
            $existing = get_post_meta($postId);
            if (is_array($existing)) {
                foreach (array_keys($existing) as $existingKey) {
                    $existingMetaKey = (string) $existingKey;
                    if ($existingMetaKey === '') {
                        continue;
                    }
                    if (in_array($existingMetaKey, $excludeKeys, true)) {
                        continue;
                    }
                    if (!$includePrivate && strpos($existingMetaKey, '_') === 0 && empty($includeKeys)) {
                        continue;
                    }
                    if (!empty($includeKeys) && !in_array($existingMetaKey, $includeKeys, true)) {
                        continue;
                    }
                    delete_post_meta($postId, $existingMetaKey);
                }
            }
        }

        $updated = 0;
        foreach ($incoming as $metaKey => $values) {
            delete_post_meta($postId, $metaKey);
            foreach ($values as $value) {
                add_post_meta($postId, $metaKey, $value);
            }
            $updated++;
        }

        return $updated;
    }

    /**
     * @param array<string, mixed> $postData
     */
    private static function postNeedsUpdate(object $existing, array $postData): bool
    {
        $checks = [
            'post_title' => 'post_title',
            'post_status' => 'post_status',
            'post_content' => 'post_content',
            'post_excerpt' => 'post_excerpt',
            'menu_order' => 'menu_order',
            'comment_status' => 'comment_status',
            'ping_status' => 'ping_status',
            'post_parent' => 'post_parent',
        ];

        foreach ($checks as $incomingKey => $existingKey) {
            if (!array_key_exists($incomingKey, $postData)) {
                continue;
            }

            $incoming = $postData[$incomingKey];
            $current = $existing->{$existingKey} ?? null;
            if ($incoming !== $current) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{success: bool, message: string, stats: array<string, int>, actions: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    private static function result(bool $success, string $message): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'stats' => [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'skipped' => 0,
                'meta_updates' => 0,
            ],
            'actions' => [],
            'errors' => [],
        ];
    }

    private static function isWpError(mixed $value): bool
    {
        return function_exists('is_wp_error') && is_wp_error($value);
    }

    private static function wpErrorMessage(mixed $error): string
    {
        if (!is_object($error) || !method_exists($error, 'get_error_message')) {
            return 'unknown error';
        }

        /** @var object{get_error_message: callable} $error */
        return (string) $error->get_error_message();
    }
}
