<?php

declare(strict_types=1);

namespace Pest\Browser\Cleanables;

use Inertia\ResponseFactory;
use Pest\Browser\Contracts\Cleanable;

/**
 * @internal
 */
final readonly class Inertia implements Cleanable
{
    /**
     * Cleans Ziggy's state.
     */
    public function clean(): void
    {
        // @phpstan-ignore-next-line
        $factory = app()->make(ResponseFactory::class);

        if (method_exists($factory, 'flushShared')) {
            // @phpstan-ignore-next-line
            $factory->flushShared();
        }
    }

    /**
     * Determines if the cleaner is applicable.
     */
    public function applicable(): bool
    {
        return function_exists('app')
            && class_exists(ResponseFactory::class)
            && app()->resolved(ResponseFactory::class);

    }
}
