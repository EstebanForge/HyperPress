<?php

/**
 * WordPress TestCase for HyperPress using WP_Mock
 */

namespace HyperPress\Tests;

use WP_Mock\Tools\TestCase as WP_Mock_TestCase;
use Mockery;

class WordPressTestCase extends WP_Mock_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up common WordPress mocks
        $this->mockCommonWordPressFunctions();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        parent::tearDown();
    }

    /**
     * Mock common WordPress functions used throughout the plugin
     */
    protected function mockCommonWordPressFunctions(): void
    {
        // Mock i18n functions
        WP_Mock::passthruFunction('__');
        WP_Mock::passthruFunction('_e');
        WP_Mock::passthruFunction('_n');
        WP_Mock::passthruFunction('_x');
        WP_Mock::passthruFunction('esc_html__');
        WP_Mock::passthruFunction('esc_html_e');
        WP_Mock::passthruFunction('esc_attr__');
        WP_Mock::passthruFunction('esc_attr_e');
        WP_Mock::passthruFunction('esc_url');
        WP_Mock::passthruFunction('esc_url_raw');
        WP_Mock::passthruFunction('esc_textarea');
        WP_Mock::passthruFunction('esc_js');
        WP_Mock::passthruFunction('sanitize_text_field');
        WP_Mock::passthruFunction('sanitize_title');
        WP_Mock::passthruFunction('wp_kses');
        WP_Mock::passthruFunction('wp_kses_post');
        WP_Mock::passthruFunction('wp_kses_data');
        
        // Mock URL and path functions
        WP_Mock::passthruFunction('home_url');
        WP_Mock::passthruFunction('site_url');
        WP_Mock::passthruFunction('admin_url');
        WP_Mock::passthruFunction('includes_url');
        WP_Mock::passthruFunction('content_url');
        WP_Mock::passthruFunction('plugins_url');
        WP_Mock::passthruFunction('plugin_dir_url');
        WP_Mock::passthruFunction('plugin_dir_path');
        WP_Mock::passthruFunction('get_template_directory');
        WP_Mock::passthruFunction('get_stylesheet_directory');
        WP_Mock::passthruFunction('trailingslashit');
        WP_Mock::passthruFunction('untrailingslashit');
        
        // Mock user functions
        WP_Mock::userFunction('is_user_logged_in')->andReturn(false);
        WP_Mock::userFunction('current_user_can')->andReturn(true);
        WP_Mock::userFunction('wp_get_current_user')->andReturn(new \WP_User());
        WP_Mock::userFunction('get_current_user_id')->andReturn(1);
        
        // Mock option functions
        WP_Mock::userFunction('get_option')->andReturn(false);
        WP_Mock::userFunction('add_option')->andReturn(true);
        WP_Mock::userFunction('update_option')->andReturn(true);
        WP_Mock::userFunction('delete_option')->andReturn(true);
        
        // Mock post functions
        WP_Mock::userFunction('get_post')->andReturn(new \WP_Post((object)['ID' => 1]));
        WP_Mock::userFunction('get_post_type')->andReturn('post');
        WP_Mock::userFunction('get_post_meta')->andReturn([]);
        WP_Mock::userFunction('add_post_meta')->andReturn(true);
        WP_Mock::userFunction('update_post_meta')->andReturn(true);
        WP_Mock::userFunction('delete_post_meta')->andReturn(true);
        WP_Mock::userFunction('get_posts')->andReturn([]);
        
        // Mock taxonomy functions
        WP_Mock::userFunction('get_terms')->andReturn([]);
        WP_Mock::userFunction('get_term')->andReturn(new \WP_Term());
        WP_Mock::userFunction('get_term_by')->andReturn(new \WP_Term());
        
        // Mock hook functions
        WP_Mock::userFunction('add_action')->andReturn(true);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        WP_Mock::userFunction('remove_action')->andReturn(true);
        WP_Mock::userFunction('remove_filter')->andReturn(true);
        WP_Mock::userFunction('do_action')->andReturn(true);
        WP_Mock::userFunction('apply_filters')->returnFirstArg();
        WP_Mock::userFunction('has_action')->andReturn(false);
        WP_Mock::userFunction('has_filter')->andReturn(false);
        WP_Mock::userFunction('doing_action')->andReturn(false);
        WP_Mock::userFunction('did_action')->andReturn(0);
        
        // Mock conditional functions
        WP_Mock::userFunction('is_admin')->andReturn(false);
        WP_Mock::userFunction('is_front_page')->andReturn(false);
        WP_Mock::userFunction('is_home')->andReturn(false);
        WP_Mock::userFunction('is_single')->andReturn(false);
        WP_Mock::userFunction('is_page')->andReturn(false);
        WP_Mock::userFunction('is_archive')->andReturn(false);
        WP_Mock::userFunction('is_search')->andReturn(false);
        WP_Mock::userFunction('is_404')->andReturn(false);
        WP_Mock::userFunction('is_multisite')->andReturn(false);
        WP_Mock::userFunction('is_main_site')->andReturn(true);
        
        // Mock plugin functions
        WP_Mock::userFunction('plugin_basename')->andReturnFirstArg();
        WP_Mock::userFunction('register_activation_hook')->andReturn(true);
        WP_Mock::userFunction('register_deactivation_hook')->andReturn(true);
        WP_Mock::userFunction('register_uninstall_hook')->andReturn(true);
        WP_Mock::userFunction('is_plugin_active')->andReturn(true);
        WP_Mock::userFunction('activate_plugin')->andReturn(true);
        WP_Mock::userFunction('deactivate_plugin')->andReturn(true);
        
        // Mock file system functions
        WP_Mock::userFunction('wp_mkdir_p')->andReturn(true);
        WP_Mock::userFunction('file_exists')->andReturn(false);
        WP_Mock::userFunction('is_dir')->andReturn(false);
        WP_Mock::userFunction('is_file')->andReturn(false);
        WP_Mock::userFunction('is_readable')->andReturn(true);
        WP_Mock::userFunction('is_writable')->andReturn(true);
        WP_Mock::userFunction('file_get_contents')->andReturn('');
        WP_Mock::userFunction('file_put_contents')->andReturn(true);
        WP_Mock::userFunction('unlink')->andReturn(true);
        WP_Mock::userFunction('copy')->andReturn(true);
        WP_Mock::userFunction('rename')->andReturn(true);
        
        // Mock utility functions
        WP_Mock::userFunction('wp_unslash')->returnFirstArg();
        WP_Mock::userFunction('wp_slash')->returnFirstArg();
        WP_Mock::userFunction('maybe_unserialize')->returnFirstArg();
        WP_Mock::userFunction('maybe_serialize')->returnFirstArg();
        WP_Mock::userFunction('wp_parse_url')->returnFirstArg();
        WP_Mock::userFunction('wp_parse_args')->returnSecondArg();
        WP_Mock::userFunction('wp_list_pluck')->andReturn([]);
        WP_Mock::userFunction('wp_list_filter')->andReturn([]);
        
        // Mock miscellaneous functions
        WP_Mock::userFunction('get_bloginfo')->returnFirstArg();
        WP_Mock::userFunction('get_file_data')->andReturn(['Version' => '3.0.1']);
        WP_Mock::userFunction('realpath')->returnFirstArg();
        WP_Mock::userFunction('dirname')->returnFirstArg();
        WP_Mock::userFunction('basename')->returnFirstArg();
        WP_Mock::userFunction('pathinfo')->andReturn(['dirname' => '', 'basename' => '']);
        WP_Mock::userFunction('json_decode')->andReturn([]);
        WP_Mock::userFunction('json_encode')->andReturn('{}');
        WP_Mock::userFunction('serialize')->andReturn('');
        WP_Mock::userFunction('unserialize')->andReturn([]);
        WP_Mock::userFunction('md5')->andReturn('');
        WP_Mock::userFunction('sha1')->andReturn('');
        
        // Mock error handling
        WP_Mock::userFunction('is_wp_error')->andReturn(false);
        WP_Mock::userFunction('wp_error')->andReturn(new \WP_Error());
        
        // Mock database functions (for SQLite compatibility)
        WP_Mock::userFunction('wpdb')->andReturn(new class {
            public function prepare($query) { return $query; }
            public function get_results($query) { return []; }
            public function get_var($query) { return null; }
            public function get_row($query) { return null; }
            public function get_col($query) { return []; }
            public function query($query) { return true; }
            public function insert($table, $data) { return true; }
            public function update($table, $data, $where) { return true; }
            public function delete($table, $where) { return true; }
            public $prefix = 'wp_';
        });
    }

    /**
     * Helper function to mock WordPress admin notices
     */
    protected function mockAdminNotice(string $message, string $type = 'error'): void
    {
        WP_Mock::expectAction('admin_notices');
        
        // Mock the admin notice output
        ob_start();
        echo '<div class="' . esc_attr($type) . '"><p>' . esc_html($message) . '</p></div>';
        $notice_output = ob_get_clean();
        
        WP_Mock::userFunction('esc_html__')->andReturn($message);
    }

    /**
     * Helper function to mock a specific WordPress function with a return value
     */
    protected function mockFunction(string $function, $returnValue, array $args = []): void
    {
        if (empty($args)) {
            WP_Mock::userFunction($function)->andReturn($returnValue);
        } else {
            WP_Mock::userFunction($function)->with(...$args)->andReturn($returnValue);
        }
    }

    /**
     * Helper function to expect a WordPress action to be fired
     */
    protected function expectAction(string $action, int $times = 1, array $args = []): void
    {
        if ($times === 1 && empty($args)) {
            WP_Mock::expectAction($action);
        } elseif (empty($args)) {
            WP_Mock::expectAction($action, $times);
        } else {
            WP_Mock::expectAction($action, ...$args);
        }
    }

    /**
     * Helper function to expect a WordPress filter to be applied
     */
    protected function expectFilter(string $filter, int $times = 1, array $args = []): void
    {
        if ($times === 1 && empty($args)) {
            WP_Mock::expectFilter($filter);
        } elseif (empty($args)) {
            WP_Mock::expectFilter($filter, $times);
        } else {
            WP_Mock::expectFilter($filter, ...$args);
        }
    }

    /**
     * Helper function to mock a WordPress transient
     */
    protected function mockTransient(string $key, $value): void
    {
        WP_Mock::userFunction('get_transient')->with($key)->andReturn($value);
        WP_Mock::userFunction('set_transient')->with($key, $value, \Mockery::type('int'))->andReturn(true);
        WP_Mock::userFunction('delete_transient')->with($key)->andReturn(true);
    }
}