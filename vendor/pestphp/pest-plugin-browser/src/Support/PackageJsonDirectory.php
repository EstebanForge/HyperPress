<?php

declare(strict_types=1);

namespace Pest\Browser\Support;

use Pest\TestSuite;

/**
 * @internal
 */
final readonly class PackageJsonDirectory
{
    /**
     * The path to the npm "base" directory.
     */
    public static function find(): string
    {
        return TestSuite::getInstance()->rootPath;
    }
}
