<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks\WordPress\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Pins the `hyperblocks/blocks/auto_discover_theme_blocks` filter.
 *
 * Auto-registration of the active theme's /blocks directories as discovery
 * paths is on by default (back-compat). A consumer whose theme uses /blocks
 * for WP-native/ACF blocks can opt out entirely with __return_false. This is
 * the second of two independent gates protecting against the WP/ACF /blocks/ auto-discovery bug
 * (the first is the HyperBlocks Block header check in discovery).
 *
 * Fixture layout relies on the get_template_directory() mock, which returns a
 * fixed path under sys_get_temp_dir(). Tests create/clean that tree.
 */
class ThemeAutoDiscoveryFilterTest extends TestCase
{
    private string $themeBlocksDir;

    protected function setUp(): void
    {
        Config::reset();
        Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];

        // The mock resolves get_template_directory() to a fixed temp path.
        // Create its /blocks subdir so registerDefaultPaths() has something
        // to register; derive the path from the mock so it tracks if the
        // mock ever changes.
        $this->themeBlocksDir = get_template_directory() . '/blocks';
        $this->rmrf(dirname($this->themeBlocksDir, 3)); // wipe /wp-content leftover
        mkdir($this->themeBlocksDir, 0777, true);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->rmrf(dirname($this->themeBlocksDir, 3));
        Config::reset();
        Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        parent::tearDown();
    }

    /**
     * Default behavior: with no filter attached, the theme /blocks directory
     * IS auto-registered as a discovery path. Back-compat guarantee.
     */
    public function testThemeBlocksAutoRegisteredByDefault(): void
    {
        Bootstrap::registerDefaultPaths();

        $this->assertContains($this->themeBlocksDir, Config::getBlockPaths());
    }

    /**
     * A developer returning true via the filter keeps the default behavior
     * (explicit opt-in is a no-op). Confirms __return_true works as documented.
     */
    public function testExplicitReturnTrueKeepsAutoRegistration(): void
    {
        add_filter('hyperblocks/blocks/auto_discover_theme_blocks', '__return_true');

        Bootstrap::registerDefaultPaths();

        $this->assertContains($this->themeBlocksDir, Config::getBlockPaths());
    }

    /**
     * A developer returning false via the filter stops theme /blocks from
     * being auto-registered — even though the directory exists and would
     * otherwise be picked up. This is the documented escape hatch.
     */
    public function testReturnFalseOptsOutOfThemeAutoRegistration(): void
    {
        add_filter('hyperblocks/blocks/auto_discover_theme_blocks', '__return_false');

        Bootstrap::registerDefaultPaths();

        $this->assertNotContains($this->themeBlocksDir, Config::getBlockPaths());
        $this->assertSame([], Config::getBlockPaths());
    }

    /**
     * Opting out of theme auto-registration must NOT disable the library's
     * own bundled blocks (HYPERBLOCKS_PATH/blocks), which are registered
     * separately in initializeConfig(). This scoping guard prevents a future
     * refactor from accidentally widening the filter.
     *
     * Note: initializeConfig() is gated on HYPERBLOCKS_PATH being defined,
     * which the test bootstrap sets. We assert only the filter's scope: it
     * affects theme paths, nothing else reachable from registerDefaultPaths().
     */
    public function testFilterDoesNotAffectExplicitlyRegisteredPaths(): void
    {
        $unrelated = $this->themeBlocksDir . '/../other-blocks';
        $unrelated = dirname($this->themeBlocksDir) . '/other-blocks';
        mkdir($unrelated, 0777, true);

        add_filter('hyperblocks/blocks/auto_discover_theme_blocks', '__return_false');

        // Explicit registration is unaffected by the theme-auto-discover filter.
        Config::registerBlockPath($unrelated);

        Bootstrap::registerDefaultPaths();

        $this->assertContains($unrelated, Config::getBlockPaths());
        $this->assertNotContains($this->themeBlocksDir, Config::getBlockPaths());
    }

    /**
     * Recursive best-effort fixture cleanup.
     */
    private function rmrf(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_link($full) || is_file($full)) {
                @unlink($full);
            } elseif (is_dir($full)) {
                $this->rmrf($full);
            }
        }

        @rmdir($path);
    }
}
