<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HTMX API URL
 * Returns the HTMX API URL, with a template path if provided.
 *
 * @since 2023-12-04
 *
 * @param string $template_path (optional)
 *
 * @return string
 */
function hxwp_api_url($template_path = '')
{
    $htmx_api_url = home_url(HXWP_ENDPOINT . '/' . HXWP_ENDPOINT_VERSION);

    // Path provided?
    if (!empty($template_path)) {
        $htmx_api_url .= '/' . ltrim($template_path, '/');
    }

    return apply_filters('hxwp/api_url', $htmx_api_url);
}

/**
 * HTMX send header response and die()
 * To be used inside noswap templates
 * Sends HX-Trigger header with our response inside hxwpResponse.
 *
 * @since 2023-12-13
 *
 * @param string $nonce
 * @param array $data status (success|error|silent-sucess), message, params => $hxvals, etc.
 * @param array $action WP action, optional, default value: none
 *
 * @return void
 */
function hxwp_send_header_response($nonce = null, $data = [], $action = null)
{
    if (!isset($nonce)) {
        hxwp_die('Nonce not provided.');
    }

    if (isset($nonce) && !wp_verify_nonce($nonce, 'hxwp_nonce')) {
        hxwp_die('Nonce verification failed.');
    }

    if ($action === null) {
        // Legacy: check if action is set inside $_POST['hxvals']['action']
        $action = isset($_POST['hxvals']['action']) ? sanitize_text_field($_POST['hxvals']['action']) : '';
    }

    // Action still empty, null or not set?
    if (empty($action)) {
        $action = 'none';
    }

    // If success or silent-sucess, set code to 200
    $code = $data['status'] == 'error' ? 400 : 200;

    // Response array
    $response = [
        'hxwpResponse' => [
            'action'  => $action,
            'status'  => $data['status'],
            'data'    => $data,
        ],
    ];

    // Headers already sent?
    if (headers_sent()) {
        wp_die('HWXP Error: Headers already sent.');
    }

    // Filter our response
    $response = apply_filters('hxwp/header_response', $response, $action, $data['status'], $data);

    // Send our response
    status_header($code);
    nocache_headers();
    header('HX-Trigger: ' . wp_json_encode($response));

    die(); // Don't need wp_die() here
}

/**
 * HTMX die helper
 * To be used inside templates
 * die, but with a 200 status code, so HTMX can show and display the error message
 * Also sends a custom header with the error message, to be used by HTMX if needed.
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
        header('HX-Error: ' . wp_json_encode([
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

/**
 * Validate HTMX request
 * Checks if the nonce is valid and optionally validates the action.
 *
 * @param array $hxvals The HTMX values array
 * @param string|null $action The expected action (optional)
 *
 * @return bool
 */
function hxwp_validate_request($hxvals, $action = null)
{
    // Secure it.
    $hxwp_nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE'] ?? '');

    // Check if nonce is valid.
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($hxwp_nonce)), 'hxwp_nonce')) {
        return false;
    }

    // Check if action is set and matches the expected action (if provided)
    if ($action !== null) {
        if (!isset($hxvals['action']) || $hxvals['action'] !== $action) {
            return false;
        }
    }

    // Return true if everything is ok
    return true;
}
