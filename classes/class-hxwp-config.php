<?php

/**
 * Load plugin Config on frontend
 *
 * @package HXWP
 * @since   2023-12-04
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Config Class
 */
class HXWP_Config
{
	/**
	 * Insert HTMX config meta tag into <head>
	 *
	 * @since 2023-12-04
	 * @return void
	 */
	public function insert_config_meta_tag()
	{
		$meta_config = '';
		$meta_config = apply_filters('hxwp/meta_config', $meta_config);

		$meta_tag = '<meta name="htmx-config" content="' . $meta_config . '">';

		do_action('hxwp/insert_config_meta_tag', $meta_tag);

		echo $meta_tag;
	}
}
