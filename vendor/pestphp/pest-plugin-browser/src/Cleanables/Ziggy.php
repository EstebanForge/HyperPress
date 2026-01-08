<?php

declare(strict_types=1);

namespace Pest\Browser\Cleanables;

use Pest\Browser\Contracts\Cleanable;
use Tighten\Ziggy\BladeRouteGenerator;

/**
 * @internal
 */
final readonly class Ziggy implements Cleanable
{
    /**
     * Cleans Ziggy's state.
     */
    public function clean(): void
    {
        // @phpstan-ignore-next-line
        BladeRouteGenerator::$generated = false;
    }

    /**
     * Determines if the cleaner is applicable.
     */
    public function applicable(): bool
    {
        return function_exists('app')
            && class_exists(BladeRouteGenerator::class);
    }
}
