<?php

declare(strict_types=1);

namespace HyperFields\Transfer;

/**
 * Request-scoped transfer audit context.
 *
 * Tracks whether execution is currently inside Transfer\Manager export/import,
 * allowing nested transfer logs to be de-duplicated.
 */
final class AuditContext
{
    private static int $managerDepth = 0;

    /**
     * Increments manager nesting depth for current request scope.
     *
     * @return void
     */
    public static function enterManager(): void
    {
        self::$managerDepth++;
    }

    /**
     * Decrements manager nesting depth for current request scope.
     *
     * @return void
     */
    public static function leaveManager(): void
    {
        self::$managerDepth = max(0, self::$managerDepth - 1);
    }

    /**
     * Returns whether execution is currently inside a manager transfer flow.
     *
     * @return bool
     */
    public static function isInsideManager(): bool
    {
        return self::$managerDepth > 0;
    }
}
