<?php

declare(strict_types=1);

namespace Pest\Browser\Api\Concerns;

use Pest\Browser\Api\Webpage;
use Pest\Browser\Execution;
use Psy\Shell;

/**
 * @mixin Webpage
 */
trait InteractsWithTab
{
    /**
     * Opens the current page URL in the default web browser and waits for a key press.
     *
     * This method is useful for debugging purposes, allowing you to view the page in a browser.
     */
    public function waitForKey(): self
    {
        $this->page->waitForLoadState();

        Execution::instance()->waitForKey();

        return $this;
    }

    /**
     * Pause for the given number of seconds.
     */
    public function wait(int|float|null $seconds = null): self
    {
        if ($seconds === null) {
            return $this->waitForKey();
        }

        Execution::instance()->wait($seconds);

        return $this;
    }

    /**
     * Opens an interactive shell with the current state of the app.
     */
    public function shell(): self
    {
        // @phpstan-ignore-next-line
        test()->shell();

        return $this;
    }

    /**
     * Opens an interactive shell with the current state of the app.
     */
    public function tinker(): self
    {
        // @phpstan-ignore-next-line
        test()->shell();

        return $this;
    }
}
