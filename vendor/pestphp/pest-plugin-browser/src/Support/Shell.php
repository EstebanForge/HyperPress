<?php

declare(strict_types=1);

namespace Pest\Browser\Support;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final readonly class Shell
{
    /**
     * Opens the given file in the default application.
     */
    public static function open(string $file): void
    {
        if (str_starts_with(PHP_OS, 'WIN')) {
            exec("start \"\" \"$file\"");
        } elseif (str_starts_with(PHP_OS, 'Darwin')) {
            exec("open \"$file\"");
        } else {
            exec("xdg-open \"$file\"");
        }
    }
}
