<?php

declare(strict_types=1);

namespace Pest\Browser\Contracts;

/**
 * @internal
 */
interface Cleanable
{
    /**
     * Cleans the state.
     */
    public function clean(): void;

    /**
     * Determines if the cleaner is applicable.
     */
    public function applicable(): bool;
}
