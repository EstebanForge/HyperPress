<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use HyperFields\LibraryBootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HyperPress-Core's reliance on the shared HyperFields asset
 * URL resolver (hyperfields_resolve_content_url() / LibraryBootstrap::resolveContentUrl()).
 *
 * HyperPress's bootstrap.php library-mode branch delegates HYPERPRESS_PLUGIN_URL
 * resolution to HyperFields' resolver (HyperPress vendors HyperFields, so the
 * helper is always present). This locks in that the resolver correctly handles
 * the nested plugin-vendor case and the Bedrock-root-vendor (non-web-accessible)
 * case, so HyperPress frontend assets (htmx/alpine/datastar) never enqueue a
 * 404ing URL when the library is vendored outside WP_PLUGIN_DIR.
 */
class AssetUrlResolverTest extends TestCase
{
    /**
     * HyperPress vendored inside a consumer plugin's vendor tree resolves to
     * the correct public URL, so frontend assets can be served.
     */
    public function testResolvesHyperPressNestedInPluginVendor(): void
    {
        $file = WP_PLUGIN_DIR . '/host-plugin/vendor/estebanforge/hyperpress-core/bootstrap.php';

        $this->assertSame(
            WP_PLUGIN_URL . '/host-plugin/vendor/estebanforge/hyperpress-core/bootstrap.php',
            LibraryBootstrap::resolveContentUrl($file)
        );
    }

    /**
     * A Bedrock-style root composer vendor lives outside WP_PLUGIN_DIR and the
     * web document root, so it is not HTTP-reachable. The resolver returns ''
     * so HyperPress's bootstrap defines HYPERPRESS_PLUGIN_URL as '' and the
     * frontend asset enqueue bails instead of emitting a 404ing URL.
     */
    public function testReturnsEmptyForBedrockRootVendorPath(): void
    {
        $appRoot = dirname(dirname(dirname(WP_CONTENT_DIR))) . '/src/vendor/estebanforge/hyperpress-core/bootstrap.php';

        $this->assertStringNotContainsStringIgnoringCase(WP_PLUGIN_DIR, $appRoot);
        $this->assertSame('', LibraryBootstrap::resolveContentUrl($appRoot));
    }
}
