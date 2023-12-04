<?php

/**
 * Main Class
 *
 * @package    HXWP
 * @since      2023
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main Class for initialize the plugin
 */
class HXWP_Main
{
	// Properties
	protected $router;
	protected $render;
	protected $assets;
	protected $config;

	/**
	 * Constructor
	 *
	 * @since 2023-11-22
	 */
	public function __construct()
	{
		do_action('hxwp/init_construct_start');

		$this->includes();

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
		$router = new HXWP_Router();
		$render = new HXWP_Render();
		$assets = new HXWP_Assets();
		$config = new HXWP_Config();

		// Hook into actions and filters
		add_action('init', [$router, 'register_main_route']);
		add_action('template_redirect', [$render, 'load_template']);
		add_action('wp_enqueue_scripts', [$assets, 'enqueue_scripts']);
		add_action('wp_head', [$config, 'insert_config_meta_tag']);

		if (is_admin()) {
			$options = new HXWP_Options();
			$activate_deactivate = new HXWP_Activate_Deactivate();
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
		include_once HXWP_ABSPATH . 'classes/class-hxwp-config.php';
		include_once HXWP_ABSPATH . 'classes/class-hxwp-assets.php';
		include_once HXWP_ABSPATH . 'classes/class-hxwp-router.php';
		include_once HXWP_ABSPATH . 'classes/class-hxwp-render.php';

		if (is_admin()) {
			include_once HXWP_ABSPATH . 'classes/admin/class-hxwp-activation.php';
			include_once HXWP_ABSPATH . 'classes/admin/class-hxwp-options.php';
		}
	}
}
