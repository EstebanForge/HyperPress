<?php

declare(strict_types=1);

namespace Pest\Browser\Api;

use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Pest\Browser\Execution;

/**
 * @mixin Webpage
 */
final readonly class TestableLivewire
{
    /**
     * Creates a new instance of testable livewire component.
     */
    public function __construct(
        private PendingAwaitablePage $awaitablePage,
        private string $componentId,
    ) {
        //
    }

    /**
     * Tests a Livewire component with the given name and parameters.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        /** @phpstan-ignore-next-line */
        $result = $this->awaitablePage->{$name}(...$arguments);

        return $this->shouldPersist($result)
            ? $this
            : $result;
    }

    /**
     * Returns the HTML of the Livewire component.
     */
    public function assertSet(string $property, mixed $value, bool $strict = false): self
    {
        Execution::instance()->waitForExpectation(function () use ($property, $value, $strict): void {
            $actual = $this->awaitablePage->script(<<<JS
                window.Livewire.find('{$this->componentId}').get('{$property}')
            JS);

            $strict ? expect($actual)->toBe($value) : expect($actual)->toEqual($value);
        });

        return $this;
    }

    /**
     * Asserts that the given property is not set to the given value.
     */
    public function assertNotSet(string $property, mixed $value, bool $strict = false): self
    {
        Execution::instance()->waitForExpectation(function () use ($property, $value, $strict): void {
            $actual = $this->awaitablePage->script(<<<JS
                window.Livewire.find('{$this->componentId}').get('{$property}')
            JS);

            $strict ? expect($actual)->not->toBe($value) : expect($actual)->not->toEqual($value);
        });

        return $this;
    }

    /**
     * Asserts that the given property is set to the given value, strictly.
     */
    public function assertSetStrict(string $property, mixed $value): self
    {
        return $this->assertSet($property, $value, true);
    }

    /**
     * Asserts that the given property is not set to the given value, strictly.
     */
    public function assertNotSetStrict(string $property, mixed $value): self
    {
        return $this->assertNotSet($property, $value, true);
    }

    /**
     * Determine whether the result should be persisted.
     */
    private function shouldPersist(mixed $result): bool
    {
        $onPending = false;

        foreach ([PendingAwaitablePage::class, AwaitableWebpage::class, On::class] as $class) {
            if ($result instanceof $class) {
                $onPending = true;
            }
        }

        return $onPending;
    }
}
