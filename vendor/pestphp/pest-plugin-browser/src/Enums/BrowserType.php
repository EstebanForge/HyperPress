<?php

declare(strict_types=1);

namespace Pest\Browser\Enums;

/**
 * @internal
 */
enum BrowserType: string
{
    case CHROME = 'chrome';
    case FIREFOX = 'firefox';

    case SAFARI = 'safari';

    /**
     * Get the browser type as a Playwright-compatible name.
     */
    public function toPlaywrightName(): string
    {
        return match ($this) {
            self::CHROME => 'chromium',
            self::FIREFOX => 'firefox',
            self::SAFARI => 'webkit',
        };
    }
}
