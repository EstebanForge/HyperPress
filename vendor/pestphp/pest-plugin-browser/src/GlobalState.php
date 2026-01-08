<?php

declare(strict_types=1);

namespace Pest\Browser;

use Pest\Browser\Cleanables\Inertia;
use Pest\Browser\Cleanables\Livewire;
use Pest\Browser\Cleanables\Ziggy;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final class GlobalState
{
    /**
     * The list of cleanable classes.
     *
     * @var array<int, class-string<Contracts\Cleanable>>
     */
    private static array $cleaners = [
        Ziggy::class,
        Livewire::class,
        Inertia::class,
    ];

    /**
     * Flushes the state of all cleanable classes.
     */
    public static function flush(): void
    {
        foreach (self::$cleaners as $cleaner) {
            /** @var Contracts\Cleanable $instance */
            $instance = new $cleaner();

            if ($instance->applicable()) {
                $instance->clean();
            }
        }
    }
}
