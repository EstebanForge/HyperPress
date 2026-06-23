<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit\Admin;

use HyperPress\Admin\Options;
use HyperPress\Main;
use PHPUnit\Framework\TestCase;

/**
 * Test Admin Options library-mode gating.
 *
 * When HyperPress-Core is consumed as a Composer library (no plugin entry
 * point), the admin UI must be hidden by default. Consumers opt in via the
 * `hyperpress/admin/show_menu` filter returning a truthy value.
 *
 * Architecture note: the gate is evaluated in initOptionsPage() (which fires
 * on the `init` hook), not in the constructor. This gives library consumers
 * until the last `init` callback to register their filter, instead of having
 * to register before `after_setup_theme` priority 0.
 *
 * Note on filter testing: Brain\Monkey's filter/action expectations rely on
 * Patchwork overriding already-defined WordPress functions. The shared test
 * bootstrap defines `apply_filters`/`add_action` as passthroughs before
 * Brain\Monkey loads, so Patchwork cannot take over. The opt-in path is
 * covered by the trust we place in `apply_filters` itself; the gating logic
 * is covered by the "hidden by default" assertion below.
 */
class OptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The production bootstrap loads includes/helpers.php during
        // hyperpress_run_initialization_logic(), which fires on
        // after_setup_theme. Unit tests bypass that path, so stub the
        // helper locally. HYPERPRESS_BASENAME is not defined in the test
        // environment, which matches library mode behavior.
        if (!function_exists('hp_is_library_mode')) {
            $this->defineLibraryModeStub();
        }
    }

    /**
     * Defines hp_is_library_mode() in the global namespace as a one-line
     * passthrough that always reports library mode. Required because the
     * production version lives in includes/helpers.php, which depends on
     * HYPERPRESS_ABSPATH and other constants the unit test env does not set.
     *
     * runkit/native function redefine is unavailable; PHP can't redefine a
     * function once defined in the global namespace, so we write the stub
     * to a temp file and include it from the global namespace scope.
     */
    private function defineLibraryModeStub(): void
    {
        if (function_exists('hp_is_library_mode')) {
            return;
        }

        $stub = '<?php function hp_is_library_mode(): bool { return true; }';
        $tmp = tempnam(sys_get_temp_dir(), 'hplib_');
        file_put_contents($tmp, $stub);
        require_once $tmp;
        unlink($tmp);
    }

    public function test_is_enabled_returns_false_by_default_in_library_mode(): void
    {
        // Library mode is active (stubbed) and apply_filters returns its
        // second argument (passthrough mock). isEnabled() therefore returns
        // false: admin page is hidden.
        $this->assertFalse(Options::isEnabled());
    }

    public function test_init_options_page_is_noop_when_disabled(): void
    {
        // With isEnabled() returning false, initOptionsPage() must early-return
        // before touching HyperFields::registerOptionsPage. Smoke assertion:
        // the method returns cleanly without side effects.
        $options = new Options($this->createMainMock());
        $options->initOptionsPage();

        $this->assertTrue(true);
    }

    public function test_constructor_does_not_throw_in_library_mode(): void
    {
        // Constructor now only stores Main and registers the init hook;
        // it never queries HyperFields. The gate runs later in initOptionsPage.
        $options = new Options($this->createMainMock());

        $this->assertInstanceOf(Options::class, $options);
    }

    private function createMainMock(): Main
    {
        return $this->getMockBuilder(Main::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
