<?php

declare(strict_types=1);

it('keeps adapter bootstrap wiring for core autoload and fallback', function (): void {
    $bootstrapPath = __DIR__ . '/../../bootstrap.php';
    $bootstrap = (string) file_get_contents($bootstrapPath);

    expect($bootstrap)->toContain("__DIR__ . '/vendor/autoload.php'")
        ->and($bootstrap)->toContain("dirname(__DIR__) . '/HyperPress-Core/vendor/autoload.php'")
        ->and($bootstrap)->toContain("__DIR__ . '/vendor/estebanforge/hyperpress-core/bootstrap.php'")
        ->and($bootstrap)->toContain("dirname(__DIR__) . '/HyperPress-Core/bootstrap.php'");
});

it('registers selector hooks and lifecycle hooks in adapter bootstrap', function (): void {
    $bootstrapPath = __DIR__ . '/../../bootstrap.php';
    $bootstrap = (string) file_get_contents($bootstrapPath);

    expect($bootstrap)->toContain("hyperpress_select_and_load_latest")
        ->and($bootstrap)->toContain("hyperfields_select_and_load_latest")
        ->and($bootstrap)->toContain("hyperblocks_select_and_load_latest")
        ->and($bootstrap)->toContain("registerDataToolsPage(")
        ->and($bootstrap)->toContain("parentSlug: 'tools.php'")
        ->and($bootstrap)->toContain("register_activation_hook")
        ->and($bootstrap)->toContain("register_deactivation_hook");
});

it('keeps plugin entrypoint delegating to shared bootstrap', function (): void {
    $entryPath = __DIR__ . '/../../api-for-htmx.php';
    $entry = (string) file_get_contents($entryPath);

    expect($entry)->toContain("require_once __DIR__ . '/bootstrap.php';")
        ->and($entry)->toContain("hyperpress_select_and_load_latest")
        ->and($entry)->toContain("hyperfields_select_and_load_latest")
        ->and($entry)->toContain("hyperblocks_select_and_load_latest");
});
