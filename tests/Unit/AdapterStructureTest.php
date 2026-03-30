<?php

declare(strict_types=1);

it('keeps the adapter bootstrap files in place', function (): void {
    expect(file_exists(__DIR__ . '/../../api-for-htmx.php'))->toBeTrue()
        ->and(file_exists(__DIR__ . '/../../bootstrap.php'))->toBeTrue()
        ->and(file_exists(__DIR__ . '/../../uninstall.php'))->toBeTrue();
});
