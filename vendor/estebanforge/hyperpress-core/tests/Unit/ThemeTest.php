<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use HyperPress\Config;
use PHPUnit\Framework\TestCase;

/**
 * Test Theme functionality.
 */
class ThemeTest extends TestCase
{
    public function testRuntimeIdentityIsInitialized()
    {
        // Runtime identity now lives on Config (prefix-safe), not global
        // HYPERPRESS_* constants. Bootstrap::init() (or the test harness)
        // populates these once.
        $this->assertTrue(Config::isInitialized());
        $this->assertNotSame('', Config::VERSION);
        $this->assertNotSame('', Config::$abspath);
        $this->assertNotSame('', Config::$pluginUrl);
    }

    public function testHyperPressVersion()
    {
        $this->assertSame(\hyperpress_test_get_plugin_version(), Config::VERSION);
    }

    public function testHyperPressDirectory()
    {
        $this->assertStringContainsString('HyperPress-Core', Config::$abspath);
        $this->assertTrue(is_dir(Config::$abspath));
    }

    public function testHyperPressUrl()
    {
        $this->assertEquals('http://localhost/wp-content/plugins/HyperPress-Core/', Config::$pluginUrl);
        $this->assertStringContainsString('HyperPress-Core', Config::$pluginUrl);
    }

    public function testHyperPressAssetsUrl()
    {
        $this->assertEquals('http://localhost/wp-content/plugins/HyperPress-Core/assets/', Config::$pluginUrl . 'assets/');
        $this->assertStringEndsWith('assets/', Config::$pluginUrl . 'assets/');
    }

    public function testWordPressConstantsDefined()
    {
        $this->assertTrue(defined('ABSPATH'));
        $this->assertTrue(defined('WP_PLUGIN_DIR'));
        $this->assertTrue(defined('WP_CONTENT_DIR'));
    }

    public function testWordPressFunctionsMocked()
    {
        $this->assertTrue(function_exists('plugins_url'));
        $this->assertTrue(function_exists('is_admin'));
        $this->assertTrue(function_exists('add_action'));
        $this->assertTrue(function_exists('get_option'));

        // Test mocked functions work
        $this->assertStringContainsString('HyperPress-Core', plugins_url());
        $this->assertStringEndsWith('test', plugins_url('test'));
        $this->assertTrue(is_admin());
        $this->assertTrue(add_action('init', function() { return true; }));
        $this->assertFalse(get_option('nonexistent_option'));
    }

    public function testThemeHelperFunctions()
    {
        $this->assertTrue(function_exists('esc_html'));
        $this->assertTrue(function_exists('esc_attr'));
        $this->assertTrue(function_exists('__'));
        $this->assertTrue(function_exists('_e'));

        // Test functionality
        $this->assertEquals('&lt;script&gt;', esc_html('<script>'));
        $this->assertEquals('&quot;test&quot;', esc_attr('"test"'));
        $this->assertEquals('Test Text', __('Test Text'));
    }
}
