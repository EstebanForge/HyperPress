<?php

declare(strict_types=1);

// Place shared test helpers here.
function hm_fixture_path(string $relative): string {
    return __DIR__ . '/_fixtures/' . ltrim($relative, '/');
}
