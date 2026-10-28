<?php

declare(strict_types=1);

namespace HyperBlocks;

/**
 * Filesystem path containment helpers.
 *
 * The containment check lives on this class instead of only on the
 * hb_path_within_base() procedural helper so class consumers can never hit
 * an undefined-function fatal when a divergent vendor copy's procedural
 * helpers did not load (stale bootstrap, guard order, prepended autoloaders):
 * classes autoload by name, so whatever copy wins the class election is
 * self-consistent.
 */
final class Path
{
    /**
     * Report whether a realpath-resolved path equals a realpath-resolved base
     * directory or sits inside it.
     *
     * Both sides are compared through wp_normalize_path: realpath() returns
     * backslash separators on Windows, so appending '/' to the raw base never
     * prefix-matches there and rejects every legitimate file: template. The
     * trailing separator stays in the comparison so a sibling directory whose
     * name shares a prefix ("blocks" vs "blocks-evil") is never treated as
     * inside the base.
     *
     * @param string $realPath realpath()-resolved candidate path.
     * @param string $realBase realpath()-resolved base directory.
     * @return bool True when $realPath is the base or inside it.
     */
    public static function withinBase(string $realPath, string $realBase): bool
    {
        $normalize = static function (string $p): string {
            $p = str_replace('\\', '/', $p);

            return function_exists('wp_normalize_path') ? wp_normalize_path($p) : $p;
        };

        $normalizedPath = $normalize($realPath);
        $normalizedBase = rtrim($normalize($realBase), '/');

        return $normalizedPath === $normalizedBase || str_starts_with($normalizedPath, $normalizedBase . '/');
    }
}
