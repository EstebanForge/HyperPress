<?php

/**
 * Load plugin assets
 *
 * @package HXWP
 * @since   2023-11-22
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Assets Class
 */
class HXWP_Assets
{
	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 2023-11-22
	 * @return void
	 */
	public function enqueue_scripts()
	{
		do_action('hxwp/init_enqueue_scripts_start');

		$hxwp_options = get_option('hxwp_options');

		// If $hxwp_options false, set some defaults
		if ($hxwp_options == false) {
			$hxwp_options = [
				'load_from_cdn' => 1,
				'load_hyperscript' => 1,
				'load_extension_animations' => 0,
				'load_extension_autosave' => 0,
				'load_extension_debug' => 0,
				'load_extension_externals' => 0,
				'load_extension_fileinput' => 0,
				'load_extension_immediate' => 0,
				'load_extension_intervalpoll' => 0,
				'load_extension_node' => 0,
				'load_extension_select' => 0,
				'load_extension_sse' => 0,
				'load_extension_swap' => 0,
				'load_extension_websocket' => 0,
				'load_extension_zen' => 0,
			];
		}

		// Load HTMX
		$load_from_cdn = $hxwp_options['load_from_cdn'];

		if ($load_from_cdn == 0) {
			$src_htmx = HXWP_PLUGIN_URL . 'assets/js/htmx.min.js';
		} else {
			$src_htmx = 'https://unpkg.com/htmx.org';
		}

		wp_enqueue_script('hxwp-htmx', $src_htmx, [], '1.9.9', true);

		// Load Hyperscript
		$load_hyperscript = $hxwp_options['load_hyperscript'];

		if ($load_hyperscript == 0) {
			$src_hyperscript = HXWP_PLUGIN_URL . 'assets/js/_hyperscript.min.js';
		} else {
			$src_hyperscript = 'https://unpkg.com/hyperscript.org';
		}

		wp_enqueue_script('hxwp-hyperscript', $src_hyperscript, ['hxwp-htmx'], '0.9.12', true);

		// Load HTMX extensions
		// get all options that start with "load_extension_"
		foreach ($hxwp_options as $key => $value) {
			if (strpos($key, 'load_extension_') === 0) {
				$extension_name = str_replace('load_extension_', '', $key);
				$extension_name = str_replace('_', '-', $extension_name);

				if ($value == 1) {
					$src_extension = 'https://unpkg.com/htmx.org/dist/ext/' . $extension_name . '.js';
				} else {
					$src_extension = HXWP_PLUGIN_URL . 'assets/js/extensions/' . $extension_name . '.js';
				}

				wp_enqueue_script('hxwp-htmx-' . $extension_name, $src_extension, ['hxwp-htmx'], '1.9.9', true);
			}
		}

		// Nonce
		$hxwp_nonce = wp_create_nonce('hxwp_nonce');

		// wp-htmx URL
		$hxwp_api_url = home_url(HXWP_ENDPOINT . '/' . HXWP_ENDPOINT_VERSION . '/');

		// Localize script
		wp_localize_script('hxwp-htmx', 'hxwp', [
			'htmx_api' => $hxwp_api_url,
			'nonce'    => $hxwp_nonce,
		]);

		// Also, let use X-WP-Nonce header, to automate nonce integration with HTMX
		$hxwp_script = "document.body.addEventListener('htmx:configRequest', function(evt) {evt.detail.headers['X-WP-Nonce'] = '" . $hxwp_nonce . "';});";

		// Filter
		$hxwp_script = apply_filters('hxwp/htmx_configrequest_nonce', $hxwp_script);

		wp_add_inline_script('hxwp-htmx', $hxwp_script);

		do_action('hxwp/init_enqueue_scripts_end');
	}
}
