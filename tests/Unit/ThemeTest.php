<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test Theme functionality.
 */
class ThemeTest extends TestCase
{
    public function testThemeConstantsExist()
    {
        $this->assertTrue(defined('HYPERPRESS_VERSION'));
        $this->assertTrue(defined('HYPERPRESS_DIR'));
        $this->assertTrue(defined('HYPERPRESS_URL'));
        $this->assertTrue(defined('HYPERPRESS_ASSETS_URL'));
    }

    public function testHyperPressVersion()
    {
        $this->assertEquals('3.0.3', HYPERPRESS_VERSION);
    }

    public function testHyperPressDirectory()
    {
        $this->assertStringContainsString('api-for-htmx', HYPERPRESS_DIR);
        $this->assertTrue(is_dir(HYPERPRESS_DIR));
    }

    public function testHyperPressUrl()
    {
        $this->assertEquals('http://localhost/wp-content/plugins/api-for-htmx', HYPERPRESS_URL);
        $this->assertStringEndsWith('api-for-htmx', HYPERPRESS_URL);
    }

    public function testHyperPressAssetsUrl()
    {
        $this->assertEquals('http://localhost/wp-content/plugins/api-for-htmx/assets/', HYPERPRESS_ASSETS_URL);
        $this->assertStringEndsWith('assets/', HYPERPRESS_ASSETS_URL);
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
        $this->assertStringContainsString('api-for-htmx', plugins_url());
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
