<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test Main HyperPress class functionality.
 */
class MainTest extends TestCase
{
    public function testPluginInitialization()
    {
        // Test that the plugin main file exists
        $pluginFile = HYPERPRESS_DIR . '/api-for-htmx.php';
        $this->assertFileExists($pluginFile);
        $this->assertFileIsReadable($pluginFile);
    }

    public function testBootstrapFileExists()
    {
        $bootstrapFile = HYPERPRESS_DIR . '/bootstrap.php';
        $this->assertFileExists($bootstrapFile);
        $this->assertFileIsReadable($bootstrapFile);
    }

    public function testMainClassFileExists()
    {
        $mainClassFile = HYPERPRESS_DIR . '/src/Main.php';
        $this->assertFileExists($mainClassFile);
        $this->assertFileIsReadable($mainClassFile);
    }

    public function testMainClassStructure()
    {
        $mainClassFile = HYPERPRESS_DIR . '/src/Main.php';
        $content = file_get_contents($mainClassFile);

        // Check for expected class structure
        $this->assertStringContainsString('namespace HyperPress', $content);
        $this->assertStringContainsString('class Main', $content);
        $this->assertStringContainsString('ABSPATH', $content);
    }

    public function testPluginDependencies()
    {
        // Test HyperFields dependency
        $hyperFieldsFile = dirname(HYPERPRESS_DIR) . '/HyperFields/vendor/autoload.php';
        if (file_exists($hyperFieldsFile)) {
            $this->assertTrue(true, 'HyperFields dependency available');
        } else {
            $this->markTestSkipped('HyperFields dependency not available in expected location');
        }
    }

    public function testWordPressIntegration()
    {
        // Test WordPress functions are available
        $this->assertTrue(function_exists('is_admin'));
        $this->assertTrue(function_exists('add_action'));
        $this->assertTrue(function_exists('get_option'));
        $this->assertTrue(function_exists('wp_parse_args'));

        // Test WordPress constants
        $this->assertTrue(defined('ABSPATH'));
        $this->assertTrue(defined('WP_PLUGIN_DIR'));
    }

    public function testPluginConfiguration()
    {
        // Test that plugin can be configured
        $config = [
            'version' => HYPERPRESS_VERSION,
            'dir' => HYPERPRESS_DIR,
            'url' => HYPERPRESS_URL,
            'assets_url' => HYPERPRESS_ASSETS_URL
        ];

        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('dir', $config);
        $this->assertArrayHasKey('url', $config);
        $this->assertArrayHasKey('assets_url', $config);

        $this->assertEquals('3.0.3', $config['version']);
        $this->assertStringContainsString('api-for-htmx', $config['dir']);
        $this->assertEquals('http://localhost/wp-content/plugins/api-for-htmx', $config['url']);
        $this->assertEquals('http://localhost/wp-content/plugins/api-for-htmx/assets/', $config['assets_url']);
    }

    public function testAssetEnqueueing()
    {
        $this->assertTrue(function_exists('wp_enqueue_script'));
        $this->assertTrue(function_exists('wp_enqueue_style'));
        $this->assertTrue(function_exists('wp_register_script'));
        $this->assertTrue(function_exists('wp_register_style'));

        // Test mocked asset functions return expected values
        $this->assertTrue(wp_enqueue_script('test-script'));
        $this->assertTrue(wp_enqueue_style('test-style'));
        $this->assertTrue(wp_register_script('test-script-register'));
        $this->assertTrue(wp_register_style('test-style-register'));
    }

    public function testPluginHooks()
    {
        // Test WordPress hook functions are mocked and working
        $this->assertTrue(add_action('init', function() { return true; }));
        $this->assertTrue(add_filter('the_content', function($content) { return $content; }));

        // Test filter functionality
        $this->assertEquals('test', apply_filters('test_filter', 'test'));
        $this->assertEquals('original', apply_filters('test_filter', 'original'));
    }

    public function testPluginSecurity()
    {
        // Test security functions are available
        $this->assertTrue(function_exists('esc_html'));
        $this->assertTrue(function_exists('esc_attr'));

        // Test escaping functions work
        $html = '<script>alert("xss")</script>';
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', esc_html($html));
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', esc_attr($html));
    }

    public function testHypermediaIntegration()
    {
        // Test hypermedia library integration
        $assetsDir = HYPERPRESS_DIR . '/assets/libs/';
        $this->assertTrue(is_dir(HYPERPRESS_DIR . '/assets/'));

        // Check for expected hypermedia library files
        $expectedLibs = [
            'htmx' => 'htmx.min.js',
            'alpine' => 'alpinejs.min.js',
            'datastar' => 'datastar.min.js'
        ];
        foreach ($expectedLibs as $lib => $file) {
            $libPath = $assetsDir . $file;
            if (file_exists($libPath)) {
                $this->assertTrue(true, "$lib library file exists");
            } else {
                $this->markTestSkipped("$lib library file not found - may need npm install");
            }
        }
    }
}
