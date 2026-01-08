<?php

declare(strict_types=1);

namespace Pest\Browser\Exceptions;

use LogicException;
use NunoMaduro\Collision\Contracts\RenderlessEditor;
use NunoMaduro\Collision\Contracts\RenderlessTrace;

/**
 * @internal
 */
final class PlaywrightNotInstalledException extends LogicException implements RenderlessEditor, RenderlessTrace
{
    /**
     * Creates a new exception instance.
     */
    public function __construct()
    {
        parent::__construct(
            'Playwright is not installed. Please run [npm install playwright && npx playwright install] in the root directory of your project.',
        );
    }
}
