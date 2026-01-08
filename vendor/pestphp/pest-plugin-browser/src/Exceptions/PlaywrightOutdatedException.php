<?php

declare(strict_types=1);

namespace Pest\Browser\Exceptions;

use LogicException;
use NunoMaduro\Collision\Contracts\RenderlessEditor;
use NunoMaduro\Collision\Contracts\RenderlessTrace;

/**
 * @internal
 */
final class PlaywrightOutdatedException extends LogicException implements RenderlessEditor, RenderlessTrace
{
    /**
     * Creates a new exception instance.
     */
    public function __construct()
    {
        parent::__construct(
            'Playwright is outdated. Please run [npm install playwright@latest && npx playwright install] in the root directory of your project.',
        );
    }
}
