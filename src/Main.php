<?php

/**
 * Main Class.
 *
 * @since      2023
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Class for initialize the plugin.
 */
class Main
{
    // Properties
    protected $router;
    protected $render;
    protected $assets;
    protected $config;

    /**
     * Constructor.
     *
     * @since 2023-11-22
     */
    public function __construct()
    {
        do_action('hxwp/init_construct_start');

        //$this->includes();

        do_action('hxwp/init_construct_end');
    }

    /**
     * Main HXWP Instance.
     *
     * @since 2023-11-22
     * @return void
     */
    public function run()
    {
        do_action('hxwp/init_run_start');

        // Initialize classes
        $router = new Router();
        $render = new Render();
        $assets = new Assets();
        $config = new Config();
        $compat = new Compatibility();
        $theme = new Theme();

        // Hook into actions and filters
        add_action('init', [$router, 'register_main_route']);
        add_action('template_redirect', [$render, 'load_template']);
        add_action('wp_enqueue_scripts', [$assets, 'enqueue_scripts']);
        add_action('wp_head', [$config, 'insert_config_meta_tag']);

        // Compatibility
        $compat->run();

        // Theme support
        $theme->run();

        // HTMX at WP backend?
        $hxwp_options = get_option('hxwp_options');

        if (isset($hxwp_options['load_htmx_backend']) && $hxwp_options['load_htmx_backend'] == 1) {
            add_action('admin_enqueue_scripts', [$assets, 'enqueue_scripts']);
        }

        if (is_admin()) {
            $options = new Admin\Options();
            $activate_deactivate = new Admin\Activation();
        }

        do_action('hxwp/init_run_end');
    }

    /**
     * Include required core files used in admin and on the frontend.
     *
     * @since 2023-11-22
     * @return void
     */
    private function includes()
    {
        // Classes are autoloaded via Composer PSR-4
    }
}
