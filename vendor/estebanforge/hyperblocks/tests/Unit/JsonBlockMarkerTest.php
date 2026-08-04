<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Config;
use HyperBlocks\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Global test-capture helper defined in tests/mocks/wp-mocks.php.
 */
use HyperBlocks_Testing_Registry;

/**
 * Pins the HyperBlocks JSON-block ownership marker.
 *
 * A block.json is only owned (registered and resolved by the REST API) when it
 * declares a truthy top-level "hyperblocks" key. This is the JSON analog of
 * the fluent "HyperBlocks Block:" PHP header: explicit opt-in so auto-discovery
 * never registers foreign (WP-native/ACF) block.json files co-located in a
 * registered path such as a theme's /blocks tree.
 */
class JsonBlockMarkerTest extends TestCase
{
    private string $scanDir;

    protected function setUp(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];

        // Fresh scan root under sys_get_temp_dir().
        $this->scanDir = sys_get_temp_dir() . '/hb-json-marker-' . uniqid('', true);
        mkdir($this->scanDir, 0777, true);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->scanDir);
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        parent::tearDown();
    }

    /**
     * Owned block (marker present + truthy) is registered from its metadata.
     */
    public function testOwnedJsonBlockIsRegistered(): void
    {
        $this->writeBlockJson($this->scanDir . '/owned', [
            'name' => 'test/owned',
            'title' => 'Owned',
            'hyperblocks' => true,
        ]);
        Config::registerBlockPath($this->scanDir);

        Registry::getInstance()->discoverAndRegisterJsonBlocks();

        $registrations = HyperBlocks_Testing_Registry::getMetadataRegistrations();
        $this->assertCount(1, $registrations);
        $this->assertSame($this->scanDir . '/owned', $registrations[0]['path']);
    }

    /**
     * No marker: a co-located WP/ACF block.json must NOT be touched.
     */
    public function testForeignJsonBlockWithoutMarkerIsSkipped(): void
    {
        $this->writeBlockJson($this->scanDir . '/foreign', [
            'name' => 'test/foreign',
            'title' => 'Foreign',
        ]);
        Config::registerBlockPath($this->scanDir);

        Registry::getInstance()->discoverAndRegisterJsonBlocks();

        $this->assertSame([], HyperBlocks_Testing_Registry::getMetadataRegistrations());
    }

    /**
     * Explicit opt-out: "hyperblocks": false is treated as not owned.
     */
    public function testFalsyMarkerIsNotOwned(): void
    {
        $this->writeBlockJson($this->scanDir . '/opt-out', [
            'name' => 'test/opt-out',
            'hyperblocks' => false,
        ]);
        Config::registerBlockPath($this->scanDir);

        Registry::getInstance()->discoverAndRegisterJsonBlocks();

        $this->assertSame([], HyperBlocks_Testing_Registry::getMetadataRegistrations());
    }

    /**
     * Underscore-prefixed directories are skipped before the marker is read,
     * preserving the WP convention for disabling a block folder.
     */
    public function testUnderscoreDirIsSkippedBeforeMarkerCheck(): void
    {
        $this->writeBlockJson($this->scanDir . '/_disabled', [
            'name' => 'test/disabled',
            'hyperblocks' => true,
        ]);
        Config::registerBlockPath($this->scanDir);

        Registry::getInstance()->discoverAndRegisterJsonBlocks();

        $this->assertSame([], HyperBlocks_Testing_Registry::getMetadataRegistrations());
    }

    /**
     * The REST field/preview lookup (findJsonBlockPath) must only match owned
     * blocks, so foreign block.json files in the same path never leak into the
     * /block-fields or /render-preview responses.
     */
    public function testFindJsonBlockPathOnlyMatchesOwned(): void
    {
        $this->writeBlockJson($this->scanDir . '/owned', [
            'name' => 'test/owned',
            'hyperblocks' => true,
        ]);
        $this->writeBlockJson($this->scanDir . '/foreign', [
            'name' => 'test/foreign',
        ]);
        Config::registerBlockPath($this->scanDir);

        $registry = Registry::getInstance();

        $this->assertSame($this->scanDir . '/owned', $registry->findJsonBlockPath('test/owned'));
        $this->assertNull($registry->findJsonBlockPath('test/foreign'));
    }

    /**
     * Write a block.json payload into a (created) block directory.
     */
    private function writeBlockJson(string $dir, array $payload): void
    {
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/block.json', json_encode($payload, JSON_PRETTY_PRINT) ?: '');
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
            $this->rmrf($path . '/' . $item);
        }

        @rmdir($path);
    }
}
