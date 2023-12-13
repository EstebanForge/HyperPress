<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * HTMX API URL
 *
 * @since 2023-12-04
 * @return string
 */
function hxwp_api_url()
{
	return apply_filters('hxwp/api_url', home_url(HXWP_ENDPOINT . '/' . HXWP_ENDPOINT_VERSION));
}

/**
 * HTMX send header response
 *
 * @since 2023-12-13
 *
 * @param string $status (success|error)
 * @param array $data (extra data, optional)
 * @param array $action (WP action, optional, default value: default)
 *
 * @return void
 */
function hxwp_send_header_response($status = 'success', $data = [], $action = null)
{
	if ($action === null) {
		// Check if action is set inside $_POST['hxparams']['action']
		$action = isset($_POST['hxparams']['action']) ? sanitize_text_field($_POST['hxparams']['action']) : '';
	}

	// Action still empty, null or not set?
	if (empty($action)) {
		$action = 'default';
	}

	// If success, set code to 200
	$code = $status === 'success' ? 200 : 400;

	// Response array
	$response = [
		'hxwpResponse:' . $action => [
			'action'  => $action,
			'status'  => $status,
			'data'    => $data,
		],
	];

	// Headers already sent?
	if (headers_sent()) {
		wp_die('HWXP Error: Headers already sent.');
	}

	// Filter our response
	$response = apply_filters('hxwp/header_response', $response, $action, $status, $data);

	// Send our response
	status_header($code);
	nocache_headers();
	header('HX-Trigger: ' . json_encode($response));

	die(); // Don't need wp_die() here
}
