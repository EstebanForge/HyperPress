<?php

/**
 * Handles rendering the HTMX template
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
 * Render Class
 */
class HXWP_Render
{
	// Properties
	protected $template_name;
	protected $nonce;
	protected $hxvals = false;

	/**
	 * Render the template
	 *
	 * @since 2023-11-22
	 * @return void
	 */
	public function load_template()
	{
		global $wp_query;

		// Don't go further if this is not a request for our endpoint
		if (!isset($wp_query->query_vars[HXWP_ENDPOINT])) {
			return;
		}

		// Check if nonce exists and is valid, only on POST requests
		if (!$this->valid_nonce() && $_SERVER['REQUEST_METHOD'] === 'POST') {
			wp_die(__('Invalid nonce', 'hxwp'), __('Error', 'hxwp'), ['response' => 403]);
		}

		// Sanitize template name
		$template_name = $this->sanitize_path($wp_query->query_vars[HXWP_ENDPOINT]);

		// Get hxvals from $_REQUEST
		$hxvals = $_REQUEST;

		if (!isset($hxvals) || empty($hxvals)) {
			$hxvals = false;
		} else {
			$hxvals = $this->sanitize_params($hxvals);
		}

		// Load the requested template or fail with a 404
		$this->render_or_fail($template_name, $hxvals);
		die(); // No wp_die() here, we don't want to show the complete WP error page
	}

	/**
	 * Render or fail
	 * Load the requested template or fail with a 404
	 *
	 * @since 2023-11-30
	 * @param string $template_name
	 * @param array|bool $hxvals
	 *
	 * @return void
	 */
	protected function render_or_fail($template_name = '', $hxvals = false)
	{
		if (empty($template_name)) {
			status_header(404);

			wp_die(__('Invalid template name', 'hxwp'), __('Error', 'hxwp'), ['response' => 404]);
		}

		// Get our template file and vars
		$template_path = $this->get_template_file($template_name);

		if (!$template_path) {
			status_header(404);

			wp_die(__('Invalid route', 'hxwp'), __('Error', 'hxwp'), ['response' => 404]);
		}

		// Check if the template exists
		if (!file_exists($template_path)) {
			// Set 404 status
			status_header(404);

			wp_die(__('Template not found', 'hxwp'), __('Error', 'hxwp'), ['response' => 404]);
		}

		// To help developers know when template files were loaded via our plugin
		define('HXWP_REQUEST', true);

		// Load the template
		require_once $template_path;
	}

	/**
	 * Check if nonce exists and is valid
	 * nonce: hxwp_nonce
	 *
	 * @since 2023-11-30
	 *
	 * @return bool
	 */
	protected function valid_nonce()
	{
		// https://github.com/WP-API/api-core/blob/develop/wp-includes/rest-api.php#L555
		$nonce = null;

		if (isset($_REQUEST['_wpnonce'])) {
			$nonce = $_REQUEST['_wpnonce'];
		} elseif (isset($_SERVER['HTTP_X_WP_NONCE'])) {
			$nonce = $_SERVER['HTTP_X_WP_NONCE'];
		}

		if (null === $nonce) {
			// No nonce at all, so act as if it's an unauthenticated request.
			wp_set_current_user(0);
			return false;
		}

		if (!wp_verify_nonce($nonce, 'hxwp_nonce')) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize path
	 *
	 * @since 2023-11-30
	 * @param string $path
	 *
	 * @return string | bool
	 */
	private function sanitize_path($path = '')
	{
		if (empty($path)) {
			return false;
		}

		// Ensure path is always a string
		$path = (string) $path;

		// Replace spaces with hyphens (standard behavior)
		$path = str_replace(' ', '-', $path);

		// Don't allow directory traversal
		$path = str_replace('..', '', $path);

		// Remove accents
		$path = remove_accents($path);

		// Split the path into an array
		$path = explode('/', $path);

		// Remove empty values
		$path = array_filter($path);

		// Last element is the file name, sanitize it
		$path[count($path) - 1] = $this->sanitize_file_name(end($path));

		// Reconstruct the path with forward slashes
		$path = implode('/', $path);

		return $path;
	}

	/**
	 * Sanitize file name
	 *
	 * @since 2023-11-30
	 * @param string $file_name
	 *
	 * @return string | bool
	 */
	private function sanitize_file_name($file_name = '')
	{
		if (empty($file_name)) {
			return false;
		}

		// Remove accents and sanitize it
		$file_name = sanitize_file_name(remove_accents($file_name));

		return $file_name;
	}

	/**
	 * Sanitize hxvals
	 *
	 * @since 2023-11-30
	 * @param array $hxvals
	 *
	 * @return array | bool
	 */
	private function sanitize_params($hxvals = [])
	{
		if (empty($hxvals)) {
			return false;
		}

		// Sanitize each param
		foreach ($hxvals as $key => $value) {
			// Sanitize key and apply filter in one line
			$key = apply_filters('hxwp/sanitize_param_key', sanitize_key($key), $key);

			// Sanitize value and apply filter in one line
			$value = apply_filters('hxwp/sanitize_param_value', sanitize_textarea_field($value), $value);

			// Update param
			$hxvals[$key] = $value;
		}

		// Remove nonce if exists
		if (isset($hxvals['hxwp_nonce'])) {
			unset($hxvals['hxwp_nonce']);
		}

		return $hxvals;
	}

	/**
	 * Get active theme or child theme path
	 * If a child theme is active, use it instead of the parent theme
	 *
	 * @since 2023-11-30
	 *
	 * @return string
	 */
	protected function get_theme_path()
	{
		$theme_path = trailingslashit(get_template_directory());

		if (is_child_theme()) {
			$theme_path = trailingslashit(get_stylesheet_directory());
		}

		return $theme_path;
	}

	/**
	 * Determine our template file, and it's vars if any
	 *
	 * @since 2023-11-30
	 * @param string $template_name
	 *
	 * @return array | bool
	 */
	protected function get_template_file($template_name = '')
	{
		if (empty($template_name)) {
			return false;
		}

		// Let users filter the templates path, so they can place HTMX templates in a different location if they want
		$templates_path = apply_filters('hxwp/get_template_file/templates_path', $this->get_theme_path() . HXWP_TEMPLATE_DIR . '/');

		// Full path and extension to the template name
		$template_file_path = $templates_path . $template_name . HXWP_EXT;

		// Sanitize full path
		$template_file_path = $this->sanitize_full_path($template_file_path);

		return $template_file_path;
	}

	/**
	 * Sanitize full path
	 *
	 * @since 2023-12-13
	 *
	 * @param string $full_path
	 *
	 * @return string | bool
	 */
	protected function sanitize_full_path($full_path = '')
	{
		if (empty($full_path)) {
			return false;
		}

		// Ensure full path is always a string
		$full_path = (string) $full_path;

		// Realpath
		$full_path = realpath($full_path);

		return $full_path;
	}
}
