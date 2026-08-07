<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Base case for adapter WIRING tests.
 *
 * Loads the plugin entrypoint once under Brain Monkey, so the adapter's
 * add_filter / add_action calls are recorded in Brain Monkey's hook store. We
 * deliberately never call Brain\Monkey\tearDown(): it resets the hook store,
 * which would wipe the registrations. Tests then assert registration via the
 * has_filter() / has_action() Brain Monkey provides.
 *
 * SCOPE: registration assertions only. Brain Monkey's apply_filters does NOT
 * execute registered closures, so closure behavior is tested separately by
 * extracting the pure logic into functions and unit-testing those directly.
 */
abstract class AdapterTestCase extends TestCase
{
    private static bool $booted = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Brain Monkey's closure-name extraction emits a benign
        // "Cannot bind an instance to a static closure" warning while
        // normalizing the adapter's static closures. Registration still
        // succeeds and has_filter()/has_action() work; swallow just that
        // warning so the suite stays clean.
        set_error_handler(
            static function (int $errno, string $errstr): bool {
                return $errno === E_WARNING && str_contains($errstr, 'Cannot bind an instance');
            },
            E_WARNING
        );

        if (!self::$booted) {
            $GLOBALS['__hp_lifecycle_hooks'] = [];

            \Brain\Monkey\setUp();

            // Loads api-for-htmx.php (show-menu opt-in + version constant) and,
            // via it, bootstrap.php (system_info, data tools,
            // lifecycle). register_*_hook are preloaded recorders.
            require_once dirname(__DIR__, 2) . '/api-for-htmx.php';

            self::$booted = true;
        }
    }

    protected function tearDown(): void
    {
        restore_error_handler();

        // No Brain\Monkey\tearDown(): it would reset the hook store and wipe
        // the adapter's registrations. Close Mockery only.
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * The recorded lifecycle hook for the given phase ('activate'/'deactivate'),
     * or null if the adapter did not register it.
     *
     * @return array{file: string, callback: mixed}|null
     */
    public static function lifecycleHook(string $phase): ?array
    {
        return $GLOBALS['__hp_lifecycle_hooks'][$phase] ?? null;
    }
}
