<?php

declare(strict_types=1);

namespace Pest\Browser\Cleanables;

use Livewire\LivewireManager;
use Pest\Browser\Contracts\Cleanable;

/**
 * @internal
 */
final readonly class Livewire implements Cleanable
{
    /**
     * Cleans Ziggy's state.
     */
    public function clean(): void
    {
        $manager = app()->make(LivewireManager::class);

        // @phpstan-ignore-next-line
        if (method_exists($manager, 'flushState')) {
            $manager->flushState();
        }
    }

    /**
     * Determines if the cleaner is applicable.
     */
    public function applicable(): bool
    {
        return function_exists('app')
            && class_exists(LivewireManager::class)
            && app()->resolved(LivewireManager::class);

    }
}
