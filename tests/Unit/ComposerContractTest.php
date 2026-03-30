<?php

declare(strict_types=1);

it('declares wordpress-plugin package type and core requirement', function (): void {
    $composerPath = __DIR__ . '/../../composer.json';
    $composer = json_decode((string) file_get_contents($composerPath), true);

    expect($composer)->toBeArray()
        ->and($composer['type'] ?? null)->toBe('wordpress-plugin')
        ->and($composer['require']['estebanforge/hyperpress-core'] ?? null)->not->toBeNull();
});

it('defines required adapter scripts', function (): void {
    $composerPath = __DIR__ . '/../../composer.json';
    $composer = json_decode((string) file_get_contents($composerPath), true);
    $scripts = $composer['scripts'] ?? [];

    expect($scripts)->toHaveKeys([
        'production',
        'test',
        'test:unit',
        'cs:fix',
        'cs:check',
        'version-bump',
    ]);
});

