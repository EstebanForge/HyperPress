<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for hyperblocks_resolve_content_url().
 *
 * Pins the fix for fluent blocks being silently invisible in the Gutenberg
 * inserter when HyperBlocks is vendored into a non-plugin directory. WordPress'
 * plugins_url($path, $file) resolves correctly only when $file sits directly
 * under WP_PLUGIN_DIR: it calls plugin_basename(), which strips that one
 * prefix and nothing else. Vendored copies that live elsewhere — most notably
 * a Bedrock application's root composer vendor (public_html/src/vendor),
 * outside both WP_PLUGIN_DIR and the web document root — produce a URL like
 * https://host/app/plugins/home/.../src/vendor/... that 404s. The editor
 * script then never loads, wp.blocks.registerBlockType() never fires, and
 * every fluent block vanishes from the inserter while still rendering on the
 * front end. These tests lock in the resolver that replaces that path.
 */
class AssetUrlResolverTest extends TestCase
{
    /**
     * A nested vendor path inside WP_PLUGIN_DIR — the consumer plugin's own
     * bundled copy — resolves to the correct public URL, exactly mirroring
     * what the editor needs to fetch editor.js.
     */
    public function testResolvesNestedPluginVendorPath(): void
    {
        $file = WP_PLUGIN_DIR . '/host-plugin/vendor/estebanforge/hyperblocks/bootstrap.php';

        $this->assertSame(
            WP_PLUGIN_URL . '/host-plugin/vendor/estebanforge/hyperblocks/bootstrap.php',
            hyperblocks_resolve_content_url($file)
        );
    }

    /**
     * A path that equals a content root exactly returns the root URL with no
     * trailing slash and no doubled segment.
     */
    public function testResolvesExactRootMatchWithoutTrailingSlash(): void
    {
        $this->assertSame(WP_PLUGIN_URL, hyperblocks_resolve_content_url(WP_PLUGIN_DIR));
    }

    /**
     * Prefix matching is anchored to a directory boundary: a sibling whose
     * name merely shares a prefix (e.g. '/wp-content-x') must not match
     * WP_CONTENT_DIR ('/wp-content').
     */
    public function testDoesNotMatchOnSharedPrefixWithoutDirectoryBoundary(): void
    {
        $sibling = dirname(WP_CONTENT_DIR) . '/wp-content-evil/inside.php';

        // Not under any candidate root (the temp dir itself is not a content
        // root), so it resolves to the empty string rather than a mis-prefix.
        $this->assertSame('', hyperblocks_resolve_content_url($sibling));
    }

    /**
     * A Bedrock-style root composer vendor lives outside WP_PLUGIN_DIR and the
     * web document root, so it is not HTTP-reachable. The resolver returns ''
     * so the editor-script registration can bail instead of enqueueing a 404.
     */
    public function testReturnsEmptyForPathOutsideWebAccessibleRoots(): void
    {
        // app root: sibling of WP_CONTENT_DIR, mimicking public_html/src vs
        // public_html/src/web/app. Not under any WP_*_DIR candidate.
        $appRoot = dirname(dirname(dirname(WP_CONTENT_DIR))) . '/src/vendor/estebanforge/hyperblocks/bootstrap.php';

        $this->assertStringNotContainsStringIgnoringCase(WP_PLUGIN_DIR, $appRoot);
        $this->assertSame('', hyperblocks_resolve_content_url($appRoot));
    }

    /**
     * Backslashes (Windows-style paths) are normalized before matching, so the
     * resolver works cross-platform.
     */
    public function testNormalizesBackslashesBeforeMatching(): void
    {
        $file = str_replace('/', '\\', WP_PLUGIN_DIR . '/acme/vendor/hyperblocks/bootstrap.php');

        $this->assertSame(
            WP_PLUGIN_URL . '/acme/vendor/hyperblocks/bootstrap.php',
            hyperblocks_resolve_content_url($file)
        );
    }

    /**
     * The shared-resolver architecture intends hyperblocks_resolve_content_url()
     * to delegate to hyperfields_resolve_content_url() when HyperFields is
     * present (single canonical implementation across the three libraries).
     * The delegation branch cannot be unit-tested in HyperBlocks' own suite
     * because HyperFields' helpers.php is a Composer autoload.files entry: it
     * runs before tests/bootstrap.php defines HYPERFIELDS_TESTING_MODE, so its
     * ABSPATH/TESTING_MODE guard bails and the procedural function never
     * registers in-process (require_once then no-ops). Instead, assert the
     * delegation contract directly: when the HyperFields function IS callable,
     * both surfaces return the same value for the same input. This runs in any
     * environment where both are loaded (HyperFields' own suite, or a stack
     * integration test), and guards against the two resolvers drifting apart.
     */
    public function testDelegationContractHoldsWhenHyperFieldsPresent(): void
    {
        if (!function_exists('hyperfields_resolve_content_url')) {
            $this->markTestSkipped(
                'HyperFields procedural resolver not loaded: its helpers.php is a Composer autoload.files entry whose ABSPATH/HYPERFIELDS_TESTING_MODE guard bails before pest defines those constants. Fixing this requires test-infrastructure work in HyperFields (the guard runs at autoload time in every consumer suite). Delegation is verified in HyperFields own suite and via stack integration.'
            );
        }

        $file = WP_PLUGIN_DIR . '/acme/vendor/hyperblocks/bootstrap.php';

        $this->assertSame(
            \hyperfields_resolve_content_url($file),
            hyperblocks_resolve_content_url($file)
        );
    }
}
