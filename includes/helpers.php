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
 * HTMX send header response and die()
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
		// Check if action is set inside $_POST['hxvals']['action']
		$action = isset($_POST['hxvals']['action']) ? sanitize_text_field($_POST['hxvals']['action']) : '';
	}

	// Action still empty, null or not set?
	if (empty($action)) {
		$action = 'none';
	}

	// If success, set code to 200
	$code = $status === 'success' ? 200 : 400;

	// Response array
	$response = [
		'hxwpResponse' => [
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

/**
 * HTMX die helper
 * To be used inside templates
 * die, but with a 200 status code, so HTMX can show and display the error message
 * Also sends a custom header with the error message, to be used by HTMX if needed
 *
 * @since 2023-12-15
 *
 * @param string $message
 *
 * @return void
 */
function hxwp_die($message = '', $display_error = false)
{
	// Send our response
	if (!headers_sent()) {
		status_header(200);
		nocache_headers();
		header('HX-Error: ' . json_encode([
			'status'  => 'error',
			'data'    => [
				'message' => $message,
			],
		]));
	}

	// Don't display error message
	if ($display_error === false) {
		$message = '';
	}

	die($message);
}
