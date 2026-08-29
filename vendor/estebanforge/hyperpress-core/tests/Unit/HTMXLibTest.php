<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use HyperPress\Libraries\HTMXLib;
use PHPUnit\Framework\TestCase;

/**
 * Test the htmx extension registries.
 *
 * The v4 registry is a private static method on purpose (it derives
 * everything from the CDN map), so it is exercised through reflection. The
 * v2 path needs a Main instance for getCdnUrls(); the version-independent
 * behavior is covered by the shape assertions on the v4 map instead.
 */
class HTMXLibTest extends TestCase
{
    /**
     * Invoke the private static v4 registry builder against a synthetic map.
     *
     * @param array $extensions Slug => config map shaped like getCdnUrls().
     */
    private function buildV4(array $extensions): array
    {
        $method = new \ReflectionMethod(HTMXLib::class, 'getV4Extensions');
        $method->setAccessible(true);

        return $method->invoke(null, $extensions);
    }

    public function test_v4_registry_skips_hx_live(): void
    {
        $extensions = [
            'hx-live'       => ['url' => 'https://example.com/hx-live.min.js', 'version' => '4.0.0'],
            'htmx-2-compat' => ['url' => 'https://example.com/htmx-2-compat.min.js', 'version' => '4.0.0'],
        ];

        $registry = $this->buildV4($extensions);

        // hx-live is controlled by the dedicated load_hxlive option and must
        // never appear in the per-extension toggles.
        $this->assertArrayNotHasKey('hx-live', $registry);
        $this->assertArrayHasKey('htmx-2-compat', $registry);
    }

    public function test_v4_registry_has_migration_bridge_description(): void
    {
        $registry = $this->buildV4([
            'htmx-2-compat' => ['url' => 'https://example.com/x.min.js', 'version' => '4.0.0'],
        ]);

        $this->assertArrayHasKey('label', $registry['htmx-2-compat']);
        $this->assertArrayHasKey('description', $registry['htmx-2-compat']);
        // The bridge description must tell users what it restores.
        $this->assertStringContainsString('2.x', $registry['htmx-2-compat']['description']);
    }

    public function test_v4_registry_labels_every_entry(): void
    {
        $extensions = [
            'hx-sse'     => ['url' => '', 'version' => '4.0.0'],
            'hx-ws'      => ['url' => '', 'version' => '4.0.0'],
            'hx-unknown' => ['url' => '', 'version' => '4.0.0'],
        ];

        $registry = $this->buildV4($extensions);

        foreach (['hx-sse', 'hx-ws', 'hx-unknown'] as $slug) {
            $this->assertArrayHasKey($slug, $registry);
            $this->assertNotEmpty($registry[$slug]['label']);
            $this->assertNotEmpty($registry[$slug]['description']);
        }

        // Curated description wins for known slugs; unknown slugs get the
        // generic fallback rather than being dropped.
        $this->assertStringContainsString('Server-Sent Events', $registry['hx-sse']['description']);
        $this->assertStringContainsString('hx-unknown', $registry['hx-unknown']['description']);
    }
}
