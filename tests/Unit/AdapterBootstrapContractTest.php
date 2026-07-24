<?php

declare(strict_types=1);

/**
 * Adapter bootstrap contract.
 *
 * Locks in the post-cleanup surface: the Composer autoloader is loaded, Core
 * presence is guarded via the Bootstrap class with prefix-safe ::class refs,
 * the removed multi-instance election machinery stays gone, and the
 * adapter-owned hooks (Data Tools page, lifecycle, system-info) remain wired
 * with Config-backed version reads.
 */

it('loads the composer autoloader from plugin vendor or monorepo fallback', function (): void {
    $bootstrap = (string) file_get_contents(__DIR__ . '/../../bootstrap.php');

    expect($bootstrap)->toContain("__DIR__ . '/vendor/autoload.php'")
        ->and($bootstrap)->toContain("dirname(__DIR__) . '/HyperPress-Core/vendor/autoload.php'");
});

it('guards core presence via prefix-safe class references and drops the removed election machinery', function (): void {
    $bootstrap = (string) file_get_contents(__DIR__ . '/../../bootstrap.php');

    // Presence check + adapter wiring use ::class so a Mozart-prefixed build
    // rewrites them to the prefixed namespace.
    expect($bootstrap)->toContain('\\HyperPress\\Bootstrap::class')
        ->and($bootstrap)->toContain('\\HyperFields\\HyperFields::class')
        ->and($bootstrap)->toContain('\\HyperPress\\Admin\\Activation::class')
        ->and($bootstrap)->toContain('\\HyperFields\\Config::class')
        ->and($bootstrap)->toContain('\\HyperBlocks\\Config::class')
        ->and($bootstrap)->toContain('\\HyperPress\\Config::class');

    // Removed machinery must stay gone.
    expect($bootstrap)->not->toContain('select_and_load_latest')
        ->and($bootstrap)->not->toContain('run_initialization_logic')
        ->and($bootstrap)->not->toContain('jetpack')
        ->and($bootstrap)->not->toContain('autoload_packages');
});

it('keeps adapter-owned hooks: data tools page, lifecycle, and system info', function (): void {
    $bootstrap = (string) file_get_contents(__DIR__ . '/../../bootstrap.php');

    expect($bootstrap)->toContain('registerDataToolsPage(')
        ->and($bootstrap)->toContain("parentSlug: 'tools.php'")
        ->and($bootstrap)->toContain('register_activation_hook')
        ->and($bootstrap)->toContain('register_deactivation_hook')
        ->and($bootstrap)->toContain('hyperpress/about/system_info');
});

it('keeps plugin entrypoint delegating to shared bootstrap with no election machinery', function (): void {
    $entry = (string) file_get_contents(__DIR__ . '/../../api-for-htmx.php');

    expect($entry)->toContain("require_once __DIR__ . '/bootstrap.php';")
        ->and($entry)->not->toContain('select_and_load_latest')
        ->and($entry)->not->toContain('autoload_packages');
});
