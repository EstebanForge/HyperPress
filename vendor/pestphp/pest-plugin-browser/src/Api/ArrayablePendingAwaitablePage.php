<?php

declare(strict_types=1);

namespace Pest\Browser\Api;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;
use Pest\Browser\Playwright\Page;
use PHPUnit\Framework\ExpectationFailedException;
use Traversable;

/**
 * @mixin PendingAwaitablePage
 *
 * @implements ArrayAccess<int, PendingAwaitablePage|On>
 * @implements IteratorAggregate<int, PendingAwaitablePage|On>
 */
final class ArrayablePendingAwaitablePage implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Create a new pending awaitable page collection.
     *
     * @param  array<int, PendingAwaitablePage|On>  $pendingAwaitablePages
     */
    public function __construct(
        private array $pendingAwaitablePages,
    ) {
        //
    }

    /**
     * Dynamically forwards a method call to all contained pending pages.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        foreach ($this->pendingAwaitablePages as $key => $pendingPage) {
            // @phpstan-ignore-next-line
            $result = $pendingPage->{$name}(...$arguments);

            $this->ensureOnWebpage($result);

            if ($this->shouldPersist($result)) {
                assert($result instanceof PendingAwaitablePage || $result instanceof On);

                $this->pendingAwaitablePages[$key] = $result;
            }
        }

        return $this;
    }

    /**
     * Get an iterator for all pending pages.
     *
     * @return Traversable<int, PendingAwaitablePage|On>
     *
     * @internal
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->pendingAwaitablePages);
    }

    /**
     * Determine if a pending page exists at the given offset.
     *
     * @internal
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->pendingAwaitablePages[$offset]);
    }

    /**
     * Get a pending page at the given offset.
     *
     * @throws OutOfBoundsException
     *
     * @internal
     */
    public function offsetGet(mixed $offset): PendingAwaitablePage|On
    {
        if (! isset($this->pendingAwaitablePages[$offset])) {
            throw new OutOfBoundsException("Offset {$offset} does not exist.");
        }

        return $this->pendingAwaitablePages[$offset];
    }

    /**
     * Set a pending page at the given offset.
     *
     * @internal
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->pendingAwaitablePages[] = $value;
        } else {
            $this->pendingAwaitablePages[$offset] = $value;
        }
    }

    /**
     * Unset the pending page at the given offset.
     *
     * @internal
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->pendingAwaitablePages[$offset]);
    }

    /**
     * Count the number of pending pages.
     *
     * @internal
     */
    public function count(): int
    {
        return count($this->pendingAwaitablePages);
    }

    /**
     * Ensure the given result is still a valid webpage or pending page type.
     *
     * @throws ExpectationFailedException
     */
    private function ensureOnWebpage(mixed $result): void
    {
        $onWebPage = false;

        foreach ([
            PendingAwaitablePage::class,
            AwaitableWebpage::class,
            Webpage::class,
            On::class,
        ] as $class) {
            if ($result instanceof $class) {
                $onWebPage = true;
            }
        }

        if (! $onWebPage) {
            throw new ExpectationFailedException(
                'Attempted to fetch page information in multiple pages. Array destructuring is required to access the page information. Example: [$page1, $page2] = visit(...);',
            );
        }
    }

    /**
     * Determine whether the result should be persisted.
     */
    private function shouldPersist(mixed $result): bool
    {
        $onPending = false;

        foreach ([PendingAwaitablePage::class, On::class] as $class) {
            if ($result instanceof $class) {
                $onPending = true;
            }
        }

        return $onPending;
    }
}
