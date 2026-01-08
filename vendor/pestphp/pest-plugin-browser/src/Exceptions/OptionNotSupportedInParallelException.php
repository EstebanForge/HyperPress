<?php

declare(strict_types=1);

namespace Pest\Browser\Exceptions;

use LogicException;
use NunoMaduro\Collision\Contracts\RenderlessEditor;
use NunoMaduro\Collision\Contracts\RenderlessTrace;

/**
 * @internal
 */
final class OptionNotSupportedInParallelException extends LogicException implements RenderlessEditor, RenderlessTrace
{
    //
}
