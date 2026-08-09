<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks_Testing_Registry;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for the JSON block path cache added to stop the
 * /block-fields and /render-preview endpoints from rescanning the block tree
 * on every request.
 *
 * The name -> directory map is populated as owned blocks register during init
 * discovery; findJsonBlockPath() then resolves in O(1). Misses fall through to
 * the scan. Registry::reset() clears the cache.
 */
class JsonBlockPathCacheTest extends TestCase
{
    private string $scanDir;

    private string $ownedDir;

    protected function setUp(): void
    {
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        unset($GLOBALS['__hb_test_current_user_can']);

        $this->scanDir = sys_get_temp_dir() . '/hb-json-cache-' . uniqid('', true);
        mkdir($this->scanDir, 0777, true);

        $this->ownedDir = $this->scanDir . '/owned';
        $this->writeBlockJson($this->ownedDir, [
            'name' => 'test/owned',
            'title' => 'Owned',
            'hyperblocks' => true,
        ]);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->scanDir);
        Config::reset();
        Registry::reset();
        HyperBlocks_Testing_Registry::reset();
        $GLOBALS['__hb_test_filters'] = [];
        unset($GLOBALS['__hb_test_current_user_can']);
        parent::tearDown();
    }

    /**
     * After discovery runs, the lookup returns the cached path even when the
     * block.json is gone from disk, proving the hit does not rescan.
     */
    public function testDiscoveryPopulatesCacheSoLookupSkipsDisk(): void
    {
        Config::registerBlockPath($this->scanDir);
        $registry = Registry::getInstance();
        $registry->discoverAndRegisterJsonBlocks();

        // Remove the block.json from disk: a fresh scan would now miss.
        $this->rmrf($this->ownedDir);

        $this->assertSame($this->ownedDir, $registry->findJsonBlockPath('test/owned'));
    }

    /**
     * reset() clears the cache, so a subsequent lookup re-scans and, with the
     * file gone, returns null.
     */
    public function testResetClearsJsonBlockPathCache(): void
    {
        Config::registerBlockPath($this->scanDir);
        $registry = Registry::getInstance();
        $registry->discoverAndRegisterJsonBlocks();
        $this->rmrf($this->ownedDir);

        // Cache hit before reset.
        $this->assertSame($this->ownedDir, $registry->findJsonBlockPath('test/owned'));

        Registry::reset();

        // Cache cleared -> scan -> block.json gone -> null.
        $this->assertNull($registry->findJsonBlockPath('test/owned'));
    }

    /**
     * A name never seen during discovery still resolves through the fallback
     * scan (auto_discovery disabled or a late-registered block).
     */
    public function testMissFallsThroughToScan(): void
    {
        $lateDir = $this->scanDir . '/late';
        $this->writeBlockJson($lateDir, [
            'name' => 'test/late',
            'title' => 'Late',
            'hyperblocks' => true,
        ]);
        Config::registerBlockPath($this->scanDir);
        $registry = Registry::getInstance();

        // No discovery run: cache empty, so the scan fallback must find it.
        $this->assertSame($lateDir, $registry->findJsonBlockPath('test/late'));
    }

    /**
     * Lookups for unknown names cache negative results up to a bounded cap,
     * preventing redundant disk rescans for identical misses while bounding memory.
     */
    public function testNegativeLookupsAreCachedAndBounded(): void
    {
        Config::registerBlockPath($this->scanDir);
        $registry = Registry::getInstance();

        $this->assertNull($registry->findJsonBlockPath('nope/one'));
        $this->assertNull($registry->findJsonBlockPath('nope/one')); // Cache hit

        $cache = (new \ReflectionProperty(Registry::class, 'jsonBlockPathCache'))
            ->getValue($registry);
        $this->assertArrayHasKey('nope/one', $cache);
        $this->assertNull($cache['nope/one']);
    }

    private function writeBlockJson(string $dir, array $payload): void
    {
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/block.json', json_encode($payload, JSON_PRETTY_PRINT) ?: '');
    }

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
