<?php

declare(strict_types=1);

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the Hypermedia API URL, with a template path if provided.
 *
 * @since 2.0.0
 *
 * @param string $template_path (optional)
 *
 * @return string
 */
function hmapi_get_endpoint_url($template_path = '')
{
    $hmapi_api_url = home_url((defined('HMAPI_ENDPOINT') ? HMAPI_ENDPOINT : 'wp-html') . '/' . (defined('HMAPI_ENDPOINT_VERSION') ? HMAPI_ENDPOINT_VERSION : 'v1'));

    // Path provided?
    if (!empty($template_path)) {
        $hmapi_api_url .= '/' . ltrim($template_path, '/');
    }

    return apply_filters('hmapi/api_url', $hmapi_api_url);
}

/**
 * Echo the Hypermedia API URL, with a template path if provided.
 *
 * @since 2.0.0
 *
 * @param string $template_path (optional)
 *
 * @return string
 */
function hmapi_endpoint_url($template_path = '')
{
    echo hmapi_get_endpoint_url($template_path);
}

/**
 * HTMX send header response and die() (New HMAPI version)
 * To be used inside noswap templates
 * Sends HX-Trigger header with our response inside hmapiResponse.
 *
 * @since 2.0.0
 *
 * @param array $data status (success|error|silent-success), message, params => $hmvals, etc.
 * @param string $action WP action, optional, default value: none
 *
 * @return void
 */
function hmapi_send_header_response($data = [], $action = null)
{
    // Use shared validation logic
    if (!hmapi_validate_request()) {
        hmapi_die('Nonce verification failed.');
    }

    if ($action === null) {
        // Legacy: check if action is set inside $_POST['hmvals']['action']
        $action = isset($_POST['hmvals']['action']) ? sanitize_text_field($_POST['hmvals']['action']) : '';
    }

    // Action still empty, null or not set?
    if (empty($action)) {
        $action = 'none';
    }

    // If success or silent-success, set code to 200
    $code = $data['status'] == 'error' ? 400 : 200;

    // Response array
    $response = [
        'hmapiResponse' => [
            'action'  => $action,
            'status'  => $data['status'],
            'data'    => $data,
        ],
    ];

    // Headers already sent?
    if (headers_sent()) {
        wp_die('HMAPI Error: Headers already sent.');
    }

    // Filter our response
    $response = apply_filters('hmapi/header_response', $response, $action, $data['status'], $data);

    // Send our response
    status_header($code);
    nocache_headers();
    header('HX-Trigger: ' . wp_json_encode($response));

    die(); // Don't use wp_die() here
}

/**
 * HTMX die helper (New HMAPI version)
 * To be used inside templates
 * die, but with a 200 status code, so HTMX can show and display the error message
 * Also sends a custom header with the error message, to be used by HTMX if needed.
 *
 * @since 2.0.0
 *
 * @param string $message
 * @param bool $display_error
 *
 * @return void
 */
function hmapi_die($message = '', $display_error = false)
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
 * Validate HTMX request (New HMAPI version)
 * Checks if the nonce is valid and optionally validates the action.
 *
 * @since 2.0.0
 *
 * @param array|null $hmvals The hypermedia values array (optional, will use $_REQUEST if not provided)
 * @param string|null $action The expected action (optional)
 *
 * @return bool
 */
function hmapi_validate_request($hmvals = null, $action = null)
{
    // If hmvals not provided, get from $_REQUEST for backwards compatibility
    if ($hmvals === null) {
        $hmvals = $_REQUEST;
    }

    // Secure it - check both request parameter and header for nonce
    $nonce = '';
    if (isset($_REQUEST['_wpnonce'])) {
        $nonce = sanitize_key($_REQUEST['_wpnonce']);
    } elseif (isset($_SERVER['HTTP_X_WP_NONCE'])) {
        $nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE']);
    }

    // Check if nonce is valid (try both new and old nonce names for compatibility).
    $is_valid_new = wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), 'hmapi_nonce');
    $is_valid_legacy = wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), 'hxwp_nonce');

    if (!$is_valid_new && !$is_valid_legacy) {
        return false;
    }

    // Check if action is set and matches the expected action (if provided)
    if ($action !== null) {
        if (!isset($hmvals['action']) || $hmvals['action'] !== $action) {
            return false;
        }
    }

    // Return true if everything is ok
    return true;
}

// ===================================================================
// BACKWARD COMPATIBILITY ALIASES
// ===================================================================

/**
 * Helper to get the API URL.
 *
 * @since 2023-12-04
 * @deprecated 2.0.0 Use hmapi_get_endpoint_url() instead
 *
 * @param string $template_path (optional)
 *
 * @return string The full URL to the API endpoint for the given template.
 */
function hxwp_api_url($template_path = '')
{
    // Set a global flag to indicate that a legacy function has been used.
    $GLOBALS['hmapi_is_legacy_theme'] = true;

    _deprecated_function(__FUNCTION__, '2.0.0', 'hmapi_get_endpoint_url');

    return hmapi_get_endpoint_url($template_path);
}

/**
 * HTMX send header response and die() (Legacy HXWP version - deprecated)
 * To be used inside noswap templates
 * Sends HX-Trigger header with our response inside hxwpResponse.
 *
 * @since 2023-12-13
 * @deprecated 2.0.0 Use hmapi_send_header_response() instead
 *
 * @param array $data status (success|error|silent-success), message, params => $hxvals, etc.
 * @param string $action WP action, optional, default value: none
 *
 * @return void
 */
function hxwp_send_header_response($data = [], $action = null)
{
    _deprecated_function(__FUNCTION__, '2.0.0', 'hmapi_send_header_response');

    // Use shared validation logic
    if (!hmapi_validate_request()) {
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

    // If success or silent-success, set code to 200
    $code = $data['status'] == 'error' ? 400 : 200;

    // Response array (keep legacy format for backward compatibility)
    $response = [
        'hxwpResponse' => [
            'action'  => $action,
            'status'  => $data['status'],
            'data'    => $data,
        ],
    ];

    // Headers already sent?
    if (headers_sent()) {
        wp_die('HXWP Error: Headers already sent.');
    }

    // Filter our response (legacy filter)
    $response = apply_filters('hxwp/header_response', $response, $action, $data['status'], $data);

    // Send our response
    status_header($code);
    nocache_headers();
    header('HX-Trigger: ' . wp_json_encode($response));

    die(); // Don't use wp_die() here
}

/**
 * HTMX die helper (Legacy HXWP version - deprecated)
 * To be used inside templates
 * die, but with a 200 status code, so HTMX can show and display the error message
 * Also sends a custom header with the error message, to be used by HTMX if needed.
 *
 * @since 2023-12-15
 * @deprecated 2.0.0 Use hmapi_die() instead
 *
 * @param string $message
 * @param bool $display_error
 *
 * @return void
 */
function hxwp_die($message = '', $display_error = false)
{
    _deprecated_function(__FUNCTION__, '2.0.0', 'hmapi_die');

    hmapi_die($message, $display_error);
}

/**
 * Validate HTMX request (Legacy HXWP version - deprecated)
 * Checks if the nonce is valid and optionally validates the action.
 *
 * @since 2023-12-15
 * @deprecated 2.0.0 Use hmapi_validate_request() instead
 *
 * @param array|null $hxvals The HTMX values array (optional, will use $_REQUEST if not provided)
 * @param string|null $action The expected action (optional)
 *
 * @return bool
 */
function hxwp_validate_request($hxvals = null, $action = null)
{
    _deprecated_function(__FUNCTION__, '2.0.0', 'hmapi_validate_request');

    return hmapi_validate_request($hxvals, $action);
}

/**
 * Detect if the plugin is running as a library (not as an active plugin).
 *
 * @return bool
 */
function hmapi_is_library_mode(): bool
{
    // Check if plugin is in active_plugins
    if (defined('HMAPI_BASENAME')) {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', []));
        if (in_array(HMAPI_BASENAME, $active_plugins, true)) {
            return false; // Plugin is active, not in library mode
        }
    }

    // If we reach here, plugin is not in active plugins list
    // This means it's loaded as a library
    return true;
}
