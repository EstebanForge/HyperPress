<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Config;
use HyperBlocks\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Pins the HyperBlocks Block file-header requirement for auto-discovery.
 *
 * Background: themes following the de-facto WP/ACF /blocks/<slug>/ layout
 * co-locate render.php / init.php files that expect to be included by WP's
 * block renderer with $block in scope. Auto-loading them at init executes
 * them out of context — echoing markup before <!DOCTYPE html> and tripping
 * "undefined $block" warnings. Discovery now require_once's only files that
 * declare the `HyperBlocks Block:` header, so WP-native render.php / init.php
 * are skipped without execution. This is the WP/ACF /blocks/ auto-discovery bug fix.
 */
class DiscoveryHeaderTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        Config::reset();
        Registry::reset();
        unset($GLOBALS['__hb_header_block_loaded']);
        $GLOBALS['__hb_test_filters'] = [];

        $this->tmpRoot = rtrim(sys_get_temp_dir(), '/\\') . '/hb-hdr-test-' . uniqid('', true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpRoot);
        Config::reset();
        Registry::reset();
        unset($GLOBALS['__hb_header_block_loaded']);
        $GLOBALS['__hb_test_filters'] = [];
        parent::tearDown();
    }

    /**
     * A WP-native render.php (echoes output, no header) co-located in a
     * /blocks/<slug>/ tree must NOT be require_once'd by discovery — even when
     * its directory is explicitly registered as a block path. Nothing echoes.
     */
    public function testRenderPhpWithoutHeaderIsNotExecuted(): void
    {
        // Theme-style /blocks/welcome/{block.json,render.php}.
        $welcome = $this->tmpRoot . '/blocks/welcome';
        mkdir($welcome, 0777, true);
        file_put_contents($welcome . '/block.json', '{"name":"test/welcome"}');
        // render.php echoes immediately AND reads $block — the exact
        // theme-style footgun (echo + undefined-variable access).
        file_put_contents(
            $welcome . '/render.php',
            "<?php\necho 'LEAKED-BEFORE-DOCTYPE';\necho \$block['className'] ?? '';\n"
        );

        Config::registerBlockPath($this->tmpRoot . '/blocks');

        ob_start();
        $loaded = Registry::getInstance()->discoverAndLoadFluentBlocks();
        $output = ob_get_clean();

        // Not loaded.
        $this->assertNotContains($welcome . '/render.php', $loaded);
        // Nothing echoed: no early output, no broken page.
        $this->assertSame('', $output);
    }

    /**
     * A PHP file declaring the HyperBlocks Block header IS loaded by
     * discovery and its side-effect fires.
     */
    public function testHeaderedFileIsLoaded(): void
    {
        $dir = $this->tmpRoot . '/blocks/real';
        mkdir($dir, 0777, true);
        file_put_contents(
            $dir . '/block.php',
            "<?php\n/**\n * HyperBlocks Block: Real\n */\n\$GLOBALS['__hb_header_block_loaded'] = true;\n"
        );

        Config::registerBlockPath($this->tmpRoot . '/blocks');

        $loaded = Registry::getInstance()->discoverAndLoadFluentBlocks();

        $this->assertContains($dir . '/block.php', $loaded);
        $this->assertTrue($GLOBALS['__hb_header_block_loaded'] ?? false);
    }

    /**
     * Mixed directory: a headered definition loads, co-located headerless
     * init.php + render.php do not. Regression for the exact WP/ACF /blocks/ layout
     * (block.json + init.php + render.php alongside a real HB definition).
     */
    public function testMixedDirectoryLoadsOnlyHeaderedFiles(): void
    {
        $dir = $this->tmpRoot . '/blocks/mixed';
        mkdir($dir, 0777, true);
        // HB definition (headered).
        file_put_contents(
            $dir . '/definition.php',
            "<?php\n/**\n * HyperBlocks Block: Mixed\n */\n\$GLOBALS['__hb_header_block_loaded'] = true;\n"
        );
        // WP-native init.php + render.php (headerless).
        file_put_contents($dir . '/block.json', '{"name":"test/mixed"}');
        file_put_contents(
            $dir . '/init.php',
            "<?php\nnamespace Demo;\necho 'LEAKED-INIT';\n"
        );
        file_put_contents(
            $dir . '/render.php',
            "<?php\necho 'LEAKED-RENDER';\n"
        );

        Config::registerBlockPath($this->tmpRoot . '/blocks');

        ob_start();
        $loaded = Registry::getInstance()->discoverAndLoadFluentBlocks();
        $output = ob_get_clean();

        $this->assertContains($dir . '/definition.php', $loaded);
        $this->assertNotContains($dir . '/init.php', $loaded);
        $this->assertNotContains($dir . '/render.php', $loaded);
        $this->assertTrue($GLOBALS['__hb_header_block_loaded'] ?? false);
        $this->assertSame('', $output);
    }

    /**
     * The header is recognized inside a docblock comment block, matching WP's
     * get_file_data() parsing of ` * Header: value` lines. Also confirms an
     * unrelated header (Plugin Name) does not satisfy the check on its own —
     * but the HyperBlocks Block line alongside it does.
     */
    public function testHeaderParsedFromDocblock(): void
    {
        $dir = $this->tmpRoot . '/blocks/docblock';
        mkdir($dir, 0777, true);
        file_put_contents(
            $dir . '/block.php',
            "<?php\n/**\n * Plugin Name: Not This\n * HyperBlocks Block: Docblock Title\n * Description: other\n */\n\$GLOBALS['__hb_header_block_loaded'] = true;\n"
        );

        Config::registerBlockPath($this->tmpRoot . '/blocks');

        $loaded = Registry::getInstance()->discoverAndLoadFluentBlocks();

        $this->assertContains($dir . '/block.php', $loaded);
        $this->assertTrue($GLOBALS['__hb_header_block_loaded'] ?? false);
    }

    /**
     * Files pointed at directly via the hyperblocks/blocks/register_fluent_blocks
     * filter BYPASS the header check — naming a file explicitly is consumer
     * consent. This pins the documented escape hatch so a future refactor of
     * discoverAndLoadFluentBlocks() can't silently start enforcing the header
     * on filter-provided files (which would break explicit registration).
     */
    public function testRegisterFluentBlocksFilterBypassesHeaderCheck(): void
    {
        // Headerless file living OUTSIDE any registered block path, so it is
        // reachable ONLY through the filter (not via the path glob).
        $external = $this->tmpRoot . '/external/headerless.php';
        mkdir(dirname($external), 0777, true);
        file_put_contents(
            $external,
            "<?php\n// No HyperBlocks Block header here.\n\$GLOBALS['__hb_header_block_loaded'] = true;\n"
        );

        // Register an EMPTY block path so the glob loop runs but finds nothing;
        // the file comes in exclusively via the filter.
        $emptyPath = $this->tmpRoot . '/empty';
        mkdir($emptyPath, 0777, true);
        Config::registerBlockPath($emptyPath);

        add_filter('hyperblocks/blocks/register_fluent_blocks', static function () use ($external): array {
            return [$external];
        });

        $loaded = Registry::getInstance()->discoverAndLoadFluentBlocks();

        // Loaded despite lacking the header — explicit consent via filter.
        $this->assertContains($external, $loaded);
        $this->assertTrue($GLOBALS['__hb_header_block_loaded'] ?? false);
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
