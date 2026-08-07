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
