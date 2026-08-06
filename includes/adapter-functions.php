<?php

declare(strict_types=1);

/**
 * Pure helper functions extracted from bootstrap.php for testability.
 *
 * These have no WordPress dependencies and no side effects. The closures in
 * bootstrap.php call them for their decision/transform logic so the behavior
 * can be unit-tested directly: Brain Monkey (used by the wiring tests) does
 * not execute registered closures, so the pure pieces must be callable alone.
 */

if (!function_exists('hyperpress_adapter_insert_library_versions')) {
    /**
     * Splice $libraryVersions into $info immediately after the $afterKey row.
     * Appends at the end when $afterKey is absent, and returns $info unchanged
     * when there are no versions to insert. Pure.
     *
     * @param array<string, mixed> $info
     * @param array<string, mixed> $libraryVersions
     * @return array<string, mixed>
     */
    function hyperpress_adapter_insert_library_versions(array $info, array $libraryVersions, string $afterKey): array
    {
        if ($libraryVersions === []) {
            return $info;
        }

        $result = [];
        $inserted = false;
        foreach ($info as $key => $value) {
            $result[$key] = $value;
            if (!$inserted && $key === $afterKey) {
                foreach ($libraryVersions as $libKey => $libValue) {
                    $result[$libKey] = $libValue;
                }
                $inserted = true;
            }
        }

        return $inserted ? $result : array_merge($result, $libraryVersions);
    }
}

if (!function_exists('hyperpress_adapter_should_record_review_milestone')) {
    /**
     * Whether the review milestone should be recorded for this request: true
     * only when it has not been recorded yet and the route is a /wp-html/ one.
     */
    function hyperpress_adapter_should_record_review_milestone(bool $alreadyRecorded, ?string $route): bool
    {
        return !$alreadyRecorded && $route !== null && str_starts_with($route, '/wp-html/');
    }
}

if (!function_exists('hyperpress_adapter_maybe_record_review_milestone')) {
    /**
     * Record the review milestone for a /wp-html/ request, once per site.
     *
     * Gates on the route BEFORE reading the (non-autoloaded) option, so
     * non-hypermedia REST traffic never issues the extra get_option() SELECT
     * on every request.
     *
     * @param string|null $route The REST route, or null.
     */
    function hyperpress_adapter_maybe_record_review_milestone(?string $route): void
    {
        if ($route === null || !str_starts_with($route, '/wp-html/')) {
            return;
        }

        if (hyperpress_adapter_should_record_review_milestone(
            !empty(get_option('hyperpress_review_milestone', false)),
            $route
        )) {
            update_option(
                'hyperpress_review_milestone',
                ['at' => time()],
                false
            );
        }
    }
}

if (!function_exists('hyperpress_adapter_should_show_review_notice')) {
    /**
     * Whether the one-shot review notice should render. Pure: the caller
     * resolves the WordPress state (option, capability, user, screen) and
     * passes it in; this decides. $now is injected (not read via time()) so
     * the predicate is deterministic.
     *
     * @param mixed $record The stored milestone record (array or false).
     */
    function hyperpress_adapter_should_show_review_notice(mixed $record, bool $canManageOptions, int $userId, int $snoozedUntil, int $now, ?string $screenId): bool
    {
        if (empty($record) || !is_array($record)) {
            return false;
        }
        if (!$canManageOptions) {
            return false;
        }
        if ($userId <= 0) {
            return false;
        }
        if ($snoozedUntil > 0 && $now < $snoozedUntil) {
            return false;
        }
        if ($screenId === null) {
            return false;
        }

        return str_contains($screenId, 'hyperpress') || $screenId === 'plugins';
    }
}
