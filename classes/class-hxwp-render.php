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
	protected $params = false;

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

		// Get params from $_REQUEST
		$params = $_REQUEST;

		if (!isset($params) || empty($params)) {
			$params = false;
		} else {
			$params = $this->sanitize_params($params);
		}

		// Load the requested template or fail with a 404
		$this->render_or_fail($template_name, $params);
		die(); // No wp_die() here, we don't want to show the complete WP error page
	}

	/**
	 * Render or fail
	 * Load the requested template or fail with a 404
	 *
	 * @since 2023-11-30
	 * @param string $template_name
	 * @param array|bool $params
	 *
	 * @return void
	 */
	protected function render_or_fail($template_name = '', $params = false)
	{
		if (empty($template_name)) {
			status_header(404);

			wp_die(__('Invalid template name', 'hxwp'), __('Error', 'hxwp'), ['response' => 404]);
		}

		// Get our template file and vars
		$template = $this->get_template($template_name);

		if (!$template) {
			status_header(404);

			wp_die(__('Invalid route', 'hxwp'), __('Error', 'hxwp'), ['response' => 404]);
		}

		// If template is htmx-demo, load it from the plugin folder
		if ($template == 'htmx-demo') {
			// Add plugin path and extension to the template name
			$template_path = HXWP_ABSPATH . HXWP_TEMPLATE_DIR . '/' . $template . HXWP_EXT;
		} else {
			// Add full path and extension to the template name
			$template_path = $this->get_theme_path() . HXWP_TEMPLATE_DIR . '/' . $template . HXWP_EXT;
		}

		// Check if the template exists
		if (!file_exists($template_path)) {
			// Set 404 status
			status_header(404);

			wp_die(__('Template not found', 'hxwp'), __('Error', 'hxwp'), ['response' => 404]);
		}

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
	 * Sanitize params
	 *
	 * @since 2023-11-30
	 * @param array $params
	 *
	 * @return array | bool
	 */
	private function sanitize_params($params = [])
	{
		if (empty($params)) {
			return false;
		}

		// Sanitize each param
		foreach ($params as $key => $value) {
			// Sanitize key and apply filter in one line
			$key = apply_filters('hxwp/sanitize_param_key', sanitize_key($key), $key);

			// Sanitize value and apply filter in one line
			$value = apply_filters('hxwp/sanitize_param_value', sanitize_text_field($value), $value);

			// Update param
			$params[$key] = $value;
		}

		// Remove nonce if exists
		if (isset($params['hxwp_nonce'])) {
			unset($params['hxwp_nonce']);
		}

		return $params;
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
		$theme_path = trailingslashit(get_stylesheet_directory());

		if (is_child_theme()) {
			$theme_path = trailingslashit(get_template_directory());
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
	protected function get_template($template_name = '')
	{
		if (empty($template_name)) {
			return false;
		}

		return $template_name;
	}
}
