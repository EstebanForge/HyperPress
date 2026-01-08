<?php

declare(strict_types=1);

namespace Pest\Browser\Api;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;

final readonly class Livewire
{
    /**
     * Tests a Livewire component with the given name and parameters.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function test(Component|string $name, array $parameters = []): TestableLivewire
    {
        if ($name instanceof Component) {
            $name = $name::class;
        }

        $testable = Testable::create(
            $name,
            $parameters,
        );

        /** @var Component $component */
        $component = $testable->instance();

        $componentId = $component->id();
        assert(is_string($componentId));

        $routeName = '/pest/livewire-visit/'.$componentId;

        Route::get($routeName, function () use ($testable) {
            $html = $testable->html();

            return Blade::render(<<<HTML
                <html>
                    <head>
                        @livewireStyles
                    </head>
                    <body>
                        @livewireScripts

                        $html
                    </body>
                    </html>
                HTML,
            );

        })->middleware('web');

        return new TestableLivewire(
            visit($routeName), $componentId,
        );
    }
}
