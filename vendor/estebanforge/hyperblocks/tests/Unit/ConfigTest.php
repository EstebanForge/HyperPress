<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Config;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Config class.
 */
class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Config before each test
        Config::reset();
        parent::setUp();
    }

    public function testGetDefaultConfig(): void
    {
        $value = Config::get('block_paths');

        $this->assertIsArray($value);
        $this->assertEmpty($value);
    }

    public function testGetWithDefaultValue(): void
    {
        $value = Config::get('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function testSetAndGet(): void
    {
        Config::set('custom_key', 'custom_value');

        $value = Config::get('custom_key');

        $this->assertEquals('custom_value', $value);
    }

    public function testAllReturnsArray(): void
    {
        $all = Config::all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('block_paths', $all);
        $this->assertArrayHasKey('template_extensions', $all);
        $this->assertArrayHasKey('auto_discovery', $all);
    }

    public function testRegisterBlockPath(): void
    {
        Config::registerBlockPath('/test/path');

        $paths = Config::get('block_paths');

        $this->assertContains('/test/path', $paths);
    }

    public function testRegisterBlockPathAddsOnlyOnce(): void
    {
        Config::registerBlockPath('/test/path');
        Config::registerBlockPath('/test/path');

        $paths = Config::get('block_paths');

        $this->assertCount(1, $paths);
    }

    public function testGetBlockPaths(): void
    {
        Config::registerBlockPath('/path1');
        Config::registerBlockPath('/path2');

        $paths = Config::getBlockPaths();

        $this->assertCount(2, $paths);
        $this->assertContains('/path1', $paths);
        $this->assertContains('/path2', $paths);
    }

    public function testGetTemplateExtensions(): void
    {
        $extensions = Config::getTemplateExtensions();

        $this->assertIsArray($extensions);
        $this->assertContains('.hb.php', $extensions);
        $this->assertContains('.php', $extensions);
    }

    public function testSetTemplateExtensions(): void
    {
        Config::set('template_extensions', '.custom,.test');

        $extensions = Config::getTemplateExtensions();

        $this->assertContains('.custom', $extensions);
        $this->assertContains('.test', $extensions);
    }

    public function testIsDebug(): void
    {
        $this->assertFalse(Config::isDebug());

        Config::set('debug', true);

        $this->assertTrue(Config::isDebug());
    }

    public function testIsCacheEnabled(): void
    {
        $this->assertTrue(Config::isCacheEnabled());

        Config::set('cache_blocks', false);

        $this->assertFalse(Config::isCacheEnabled());
    }

    public function testGetRestNamespace(): void
    {
        $this->assertEquals('hyperblocks/v1', Config::getRestNamespace());

        Config::set('rest_namespace', 'custom/v2');

        $this->assertEquals('custom/v2', Config::getRestNamespace());
    }

    public function testGetEditorScriptHandle(): void
    {
        $this->assertEquals('hyperblocks-editor', Config::getEditorScriptHandle());

        Config::set('editor_script_handle', 'custom-editor');

        $this->assertEquals('custom-editor', Config::getEditorScriptHandle());
    }

    public function testResetRestoresDefaults(): void
    {
        Config::set('debug', true);
        Config::set('custom_key', 'custom_value');

        Config::reset();

        $this->assertFalse(Config::get('debug'));
        $this->assertNull(Config::get('custom_key'));
        $this->assertEquals('.hb.php,.php', Config::get('template_extensions'));
    }

    public function testSetAllMergesWithDefaults(): void
    {
        Config::setAll([
            'debug' => true,
            'custom_key' => 'custom_value',
        ]);

        $all = Config::all();

        $this->assertTrue($all['debug']);
        $this->assertEquals('custom_value', $all['custom_key']);
        $this->assertArrayHasKey('template_extensions', $all);
        $this->assertArrayHasKey('rest_namespace', $all);
    }

    public function testValidateWithValidConfig(): void
    {
        $config = [
            'block_paths' => ['/test/path'],
            'template_extensions' => '.php,.html',
            'rest_namespace' => 'test/v1',
            'auto_discovery' => true,
            'debug' => false,
        ];

        $this->assertTrue(Config::validate($config));
    }

    public function testValidateWithInvalidBlockPaths(): void
    {
        $config = [
            'block_paths' => 'not_an_array',
        ];

        $this->assertFalse(Config::validate($config));
    }

    public function testValidateWithInvalidTemplateExtensions(): void
    {
        $config = [
            'template_extensions' => 123,
        ];

        $this->assertFalse(Config::validate($config));
    }

    public function testValidateWithInvalidRestNamespace(): void
    {
        $config = [
            'rest_namespace' => 'invalid-format',
        ];

        $this->assertFalse(Config::validate($config));
    }

    public function testValidateWithInvalidBoolean(): void
    {
        $config = [
            'debug' => 'not_a_boolean',
        ];

        $this->assertFalse(Config::validate($config));
    }

    public function testValidateExtensionWithoutDot(): void
    {
        $config = [
            'template_extensions' => 'php,html',
        ];

        $this->assertFalse(Config::validate($config));
    }

    public function testInitLoadsOnlyOnce(): void
    {
        Config::set('test_key', 'test_value');
        Config::init();

        $this->assertEquals('test_value', Config::get('test_key'));
    }

    public function testDefaultValuesAreCorrect(): void
    {
        $defaults = [
            'block_paths' => [],
            'template_extensions' => '.hb.php,.php',
            'auto_discovery' => true,
            'debug' => false,
            'cache_blocks' => true,
            'rest_namespace' => 'hyperblocks/v1',
            'editor_script_handle' => 'hyperblocks-editor',
        ];

        foreach ($defaults as $key => $expected) {
            $this->assertEquals($expected, Config::get($key), "Default for '{$key}' mismatch");
        }
    }
}
