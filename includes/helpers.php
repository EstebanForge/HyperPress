<?php

declare(strict_types=1);

use HyperPress\starfederation\datastar\ServerSentEventGenerator;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Get the HyperPress API URL, with a template path if provided.
 *
 * @since 2.1.0
 *
 * @param string $template_path (optional)
 *
 * @return string
 */
function hp_get_endpoint_url($template_path = '')
{
    $hyperpress_api_url = home_url((defined('HYPERPRESS_ENDPOINT') ? HYPERPRESS_ENDPOINT : 'wp-html') . '/' . (defined('HYPERPRESS_ENDPOINT_VERSION') ? HYPERPRESS_ENDPOINT_VERSION : 'v1'));

    if (!empty($template_path)) {
        $hyperpress_api_url .= '/' . ltrim($template_path, '/');
    }

    return apply_filters('hyperpress/api_url', $hyperpress_api_url);
}

/**
 * Echo the HyperPress API URL, with a template path if provided.
 *
 * @since 2.1.0
 *
 * @param string $template_path (optional)
 *
 * @return void
 */
function hp_endpoint_url($template_path = ''): void
{
    echo hp_get_endpoint_url($template_path);
}

/**
 * HTMX send header response and die()
 * To be used inside noswap templates
 * Sends HX-Trigger header with our response inside hyperpressResponse.
 *
 * @since 2.1.0
 *
 * @param array $data status (success|error|silent-success), message, params => $hmvals, etc.
 * @param string $action WP action, optional, default value: none
 *
 * @return void
 */
function hp_send_header_response($data = [], $action = null)
{
    // Use shared validation logic
    if (!hp_validate_request()) {
        hp_die(__('Nonce verification failed.', 'hyperpress'));
    }

    if ($action === null) {
        // Check if action is set inside $_POST['hp_vals']['action'] or directly in $_POST['action']
        if (isset($_POST['hp_vals']['action'])) {
            $action = sanitize_text_field($_POST['hp_vals']['action']);
        } elseif (isset($_POST['action'])) {
            $action = sanitize_text_field($_POST['action']);
        } else {
            $action = '';
        }
    }

    // Action still empty, null or not set?
    if (empty($action)) {
        $action = 'none';
    }

    // If success or silent-success, set code to 200
    $code = $data['status'] == 'error' ? 400 : 200;

    // Response array
    $response = [
        'hyperpressResponse' => [
            'action'  => $action,
            'status'  => $data['status'],
            'data'    => $data,
        ],
    ];

    // Headers already sent?
    if (headers_sent()) {
        wp_die(__('HyperPress Error: Headers already sent.', 'hyperpress'));
    }

    // Filter our response
    $response = apply_filters('hyperpress/header_response', $response, $action, $data['status'], $data);

    // Send our response
    status_header($code);
    nocache_headers();
    header('HX-Trigger: ' . wp_json_encode($response));

    die(); // Don't use wp_die() here
}

/**
 * HTMX die helper
 * To be used inside templates
 * die, but with a 200 status code, so HTMX can show and display the error message
 * Also sends a custom header with the error message, to be used by HTMX if needed.
 *
 * @since 2.1.0
 *
 * @param string $message
 * @param bool $display_error
 *
 * @return void
 */
function hp_die($message = '', $display_error = false)
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
 * @since 2.1.0
 *
 * @param array|null $hp_vals The hypermedia values array (optional, will use $_REQUEST if not provided)
 * @param string|null $action The expected action (optional)
 *
 * @return bool
 */
function hp_validate_request($hp_vals = null, $action = null): bool
{
    // If hp_vals not provided, get from $_REQUEST for backwards compatibility
    if ($hp_vals === null) {
        $hp_vals = $_REQUEST;
    }

    // Secure it - check both request parameter and header for nonce
    $nonce = '';
    if (isset($_REQUEST['_wpnonce'])) {
        $nonce = sanitize_key($_REQUEST['_wpnonce']);
    } elseif (isset($_SERVER['HTTP_X_WP_NONCE'])) {
        $nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE']);
    }

    // Check if nonce is valid (try both new and old nonce names for compatibility).
    $is_valid_new = wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), 'hyperpress_nonce');
    $is_valid_legacy = wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), 'hxwp_nonce');

    if (!$is_valid_new && !$is_valid_legacy) {
        return false;
    }

    // Check if action is set and matches the expected action (if provided)
    if ($action !== null) {
        if (!isset($hp_vals['action']) || $hp_vals['action'] !== $action) {
            return false;
        }
    }

    // Return true if everything is ok
    return true;
}

/**
 * Detect if the plugin is running as a library (not as an active plugin).
 *
 * @since 2.1.0
 * @return bool
 */
function hp_is_library_mode(): bool
{
    // Check if the plugin is in the active plugins list
    if (defined('HYPERPRESS_BASENAME')) {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', []));
        if (in_array(HYPERPRESS_BASENAME, $active_plugins, true)) {
            return false; // Plugin is active, not in library mode
        }
    }

    // If we reach here, plugin is not in active plugins list
    // This means it's loaded as a library
    return true;
}

/**
 * Gets the ServerSentEventGenerator instance, creating it if it doesn't exist.
 *
 * @since 2.1.0
 * @return ServerSentEventGenerator|null The SSE generator instance or null if the SDK is not available.
 */
function hp_ds_sse(): ?ServerSentEventGenerator
{
    static $sse = null;

    if (!class_exists(ServerSentEventGenerator::class)) {
        return null;
    }

    if ($sse === null) {
        $sse = new ServerSentEventGenerator();
        $sse->sendHeaders();
    }

    return $sse;
}

/**
 * Reads signals sent from the Datastar client.
 *
 * @since 2.1.0
 * @return array The signals array from the client.
 */
function hp_ds_read_signals(): array
{
    if (!class_exists(ServerSentEventGenerator::class)) {
        return [];
    }

    // WordPress automatically adds slashes to all GET, POST, REQUEST, etc. data
    // through its legacy 'magic quotes' feature. This breaks JSON parsing in
    // Datastar signals sent via GET requests. We need to remove these slashes
    // so that the Datastar SDK can properly decode the JSON data.
    // @see https://stackoverflow.com/a/8949871
    $_GET = array_map('stripslashes_deep', $_GET);

    return ServerSentEventGenerator::readSignals();
}

/**
 * Patches elements into the DOM.
 *
 * @since 2.1.0
 * @param string $html The HTML content to patch.
 * @param array $options Options for patching, including 'selector', 'mode', and 'useViewTransition'.
 * @return void
 */
function hp_ds_patch_elements(string $html, array $options = []): void
{
    $sse = hp_ds_sse();
    if ($sse) {
        $sse->patchElements($html, $options);
    }
}

/**
 * Removes elements from the DOM.
 *
 * @since 2.1.0
 * @param string $selector The CSS selector for elements to remove.
 * @param array $options Options for removal, including 'useViewTransition'.
 * @return void
 */
function hp_ds_remove_elements(string $selector, array $options = []): void
{
    $sse = hp_ds_sse();
    if ($sse) {
        $sse->removeElements($selector, $options);
    }
}

/**
 * Patches signals.
 *
 * @since 2.1.0
 * @param string|array $signals The signals to patch (JSON string or array).
 * @param array $options Options for patching, including 'onlyIfMissing'.
 * @return void
 */
function hp_ds_patch_signals($signals, array $options = []): void
{
    $sse = hp_ds_sse();
    if ($sse) {
        $sse->patchSignals($signals, $options);
    }
}

/**
 * Executes a script in the browser.
 *
 * @since 2.1.0
 * @param string $script The JavaScript code to execute.
 * @param array $options Options for script execution.
 * @return void
 */
function hp_ds_execute_script(string $script, array $options = []): void
{
    $sse = hp_ds_sse();
    if ($sse) {
        $sse->executeScript($script, $options);
    }
}

/**
 * Redirects the browser to a new URL.
 *
 * @since 2.1.0
 * @param string $url The URL to redirect to.
 * @return void
 */
function hp_ds_location(string $url): void
{
    $sse = hp_ds_sse();
    if ($sse) {
        $sse->location($url);
    }
}

/**
 * Check if current request is rate limited for Datastar SSE endpoints.
 *
 * Provides configurable rate limiting for SSE connections to prevent abuse
 * and protect server resources. Uses WordPress transients for persistence.
 *
 * @since 2.1.0
 * @param array $options {
 *     Rate limiting configuration options.
 *
 *     @type int    $requests_per_window Maximum requests allowed per time window. Default 10.
 *     @type int    $time_window_seconds Time window in seconds for rate limiting. Default 60.
 *     @type string $identifier         Custom identifier for rate limiting. Default uses IP + user ID.
 *     @type bool   $send_sse_response  Whether to send SSE error response when rate limited. Default true.
 *     @type string $error_message      Custom error message for rate limit. Default 'Rate limit exceeded'.
 *     @type string $error_selector     CSS selector for error display. Default '#rate-limit-error'.
 * }
 * @return bool True if rate limited (blocked), false if request is allowed.
 */
function hp_ds_is_rate_limited(array $options = []): bool
{
    // Default configuration
    $defaults = [
        'requests_per_window' => 10,
        'time_window_seconds' => 60,
        'identifier' => '',
        'send_sse_response' => true,
        'error_message' => __('Rate limit exceeded. Please wait before making more requests.', 'hyperpress'),
        'error_selector' => '#rate-limit-error',
    ];

    $config = array_merge($defaults, $options);

    // Generate unique identifier for this client
    if (empty($config['identifier'])) {
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $config['identifier'] = 'hpds_rate_limit_' . md5($ip_address . '_' . $user_id);
    } else {
        $config['identifier'] = 'hpds_rate_limit_' . md5($config['identifier']);
    }

    // Get current request count from transient
    $current_count = get_transient($config['identifier']);
    if ($current_count === false) {
        $current_count = 0;
    }

    // Check if rate limit exceeded
    if ($current_count >= $config['requests_per_window']) {
        // Rate limit exceeded
        if ($config['send_sse_response'] && hp_ds_sse()) {
            // Send error response via SSE
            hp_ds_patch_elements(
                '<div class="rate-limit-error error" style="color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin: 10px 0;">'
                . esc_html($config['error_message'])
                . '</div>',
                ['selector' => $config['error_selector']]
            );

            // Update signals to indicate rate limit status
            hp_ds_patch_signals([
                'rate_limited' => true,
                'rate_limit_reset_in' => $config['time_window_seconds'],
                'requests_remaining' => 0,
            ]);

            // Send rate limit info to client via script
            hp_ds_execute_script("
                console.warn('" . esc_js(__('Rate limit exceeded for Datastar SSE endpoint', 'hyperpress')) . "');
                console.info('" . esc_js(sprintf(__('Requests allowed: %d per %d seconds', 'hyperpress'), $config['requests_per_window'], $config['time_window_seconds'])) . "');
            ");
        }

        return true; // Rate limited
    }

    // Increment request count
    $new_count = $current_count + 1;
    set_transient($config['identifier'], $new_count, $config['time_window_seconds']);

    // Send rate limit status via SSE if available
    if ($config['send_sse_response'] && hp_ds_sse()) {
        $remaining_requests = $config['requests_per_window'] - $new_count;

        hp_ds_patch_signals([
            'rate_limited' => false,
            'requests_remaining' => $remaining_requests,
            'total_requests_allowed' => $config['requests_per_window'],
            'time_window_seconds' => $config['time_window_seconds'],
        ]);

        // Remove any existing rate limit error messages
        hp_ds_remove_elements($config['error_selector'] . ' .rate-limit-error');

        // Log remaining requests for debugging
        if ($remaining_requests <= 5) {
            hp_ds_execute_script("
                console.warn('" . esc_js(sprintf(__('Rate limit warning: %d requests remaining in this time window', 'hyperpress'), $remaining_requests)) . "');
            ");
        }
    }

    return false; // Request allowed
}

/**
 * Create an OptionsPage instance.
 *
 * @param string $page_title The title of the page
 * @param string $menu_slug The slug for the menu
 * @return HyperPress\Fields\OptionsPage
 */
function hp_create_option_page(string $page_title, string $menu_slug): HyperPress\Fields\OptionsPage
{
    return HyperPress\Fields\OptionsPage::make($page_title, $menu_slug);
}

/**
 * Create a Field instance.
 *
 * @since 2.1.0
 * @param string $type The field type
 * @param string $name The field name
 * @param string $label The field label
 * @return HyperPress\Fields\Field
 */
function hp_create_field(string $type, string $name, string $label): HyperPress\Fields\Field
{
    return HyperPress\Fields\Field::make($type, $name, $label);
}

/**
 * Create a TabsField instance.
 *
 * @since 2.1.0
 * @param string $name The field name
 * @param string $label The field label
 * @return HyperPress\Fields\TabsField
 */
function hp_create_tabs(string $name, string $label): HyperPress\Fields\TabsField
{
    return HyperPress\Fields\TabsField::make($name, $label);
}

/**
 * Create a RepeaterField instance.
 *
 * @since 2.1.0
 * @param string $name The field name
 * @param string $label The field label
 * @return HyperPress\Fields\RepeaterField
 */
function hp_create_repeater(string $name, string $label): HyperPress\Fields\RepeaterField
{
    return HyperPress\Fields\RepeaterField::make($name, $label);
}

/**
 * Create an OptionsSection instance.
 *
 * @since 2.1.0
 * @param string $id The section ID
 * @param string $title The section title
 * @return HyperPress\Fields\OptionsSection
 */
function hp_create_section(string $id, string $title): HyperPress\Fields\OptionsSection
{
    return HyperPress\Fields\OptionsSection::make($id, $title);
}

/**
 * Resolve field context into a normalized structure.
 *
 * @since 2.1.0
 * Supported $source values:
 * - int|numeric-string: Post ID (post meta)
 * - WP_Post: Post object
 * - "user_{ID}" or WP_User: User meta
 * - "term_{ID}" or WP_Term: Term meta
 * - "option"|"options": Options API using group from args or default
 * - array{type: post|user|term|option, id?: int, option_group?: string}
 * - null: try current post ID (inside The Loop) else treat as option
 */
function hp_resolve_field_context($source = null, array $args = []): array
{
    $context = [
        'type' => 'option',
        'object_id' => 0,
        'option_group' => $args['option_group'] ?? apply_filters('hyperpress/helpers/default_option_group', 'hyperpress_options'),
    ];

    if (is_array($source)) {
        $context['type'] = $source['type'] ?? $context['type'];
        if (isset($source['id'])) {
            $context['object_id'] = (int) $source['id'];
        }
        if (isset($source['option_group'])) {
            $context['option_group'] = (string) $source['option_group'];
        }

        return $context;
    }

    if ($source instanceof WP_Post) {
        $context['type'] = 'post';
        $context['object_id'] = (int) $source->ID;

        return $context;
    }

    if ($source instanceof WP_User) {
        $context['type'] = 'user';
        $context['object_id'] = (int) $source->ID;

        return $context;
    }

    if ($source instanceof WP_Term) {
        $context['type'] = 'term';
        $context['object_id'] = (int) $source->term_id;

        return $context;
    }

    if (is_numeric($source)) {
        $context['type'] = 'post';
        $context['object_id'] = (int) $source;

        return $context;
    }

    if (is_string($source)) {
        if (strpos($source, 'user_') === 0) {
            $context['type'] = 'user';
            $context['object_id'] = (int) substr($source, 5);

            return $context;
        }
        if (strpos($source, 'term_') === 0) {
            $context['type'] = 'term';
            $context['object_id'] = (int) substr($source, 5);

            return $context;
        }
        if ($source === 'option' || $source === 'options') {
            $context['type'] = 'option';

            return $context;
        }
    }

    // Fallbacks when $source is null or unrecognized
    $post_id = get_the_ID();
    if ($post_id) {
        $context['type'] = 'post';
        $context['object_id'] = (int) $post_id;

        return $context;
    }

    return $context; // default is option
}

/**
 * Optionally sanitize a value using Field::sanitize_value when a type is provided.
 *
 * @since 2.1.0
 */
function hp_maybe_sanitize_field_value(string $name, $value, array $args = [])
{
    $type = $args['type'] ?? null;
    if (is_string($type) && $type !== '') {
        try {
            $field = HyperPress\Fields\Field::make($type, $name, $name);

            return $field->sanitize_value($value);
        } catch (Throwable $e) {
            // Fall through to filters if Field cannot be created
        }
    }

    // Allow external sanitization via filter when no type is provided
    return apply_filters('hyperpress/helpers/update_field_sanitize', $value, $name, $args);
}

/**
 * Get a field value from post/user/term meta or options.
 *
 * @since 2.1.0
 * @param string $name   Meta key / option key
 * @param mixed  $source Context (see hp_resolve_field_context)
 * @param array  $args   { option_group?, default? }
 */
function hp_get_field(string $name, $source = null, array $args = [])
{
    $ctx = hp_resolve_field_context($source, $args);

    switch ($ctx['type']) {
        case 'post':
            if ($ctx['object_id'] > 0) {
                $val = get_post_meta($ctx['object_id'], $name, true);

                return $val !== '' ? $val : ($args['default'] ?? null);
            }
            break;
        case 'user':
            if ($ctx['object_id'] > 0) {
                $val = get_user_meta($ctx['object_id'], $name, true);

                return $val !== '' ? $val : ($args['default'] ?? null);
            }
            break;
        case 'term':
            if ($ctx['object_id'] > 0) {
                $val = get_term_meta($ctx['object_id'], $name, true);

                return $val !== '' ? $val : ($args['default'] ?? null);
            }
            break;
        case 'option':
        default:
            $group = $ctx['option_group'];
            $options = get_option($group, []);
            if (is_array($options) && array_key_exists($name, $options)) {
                return $options[$name];
            }

            return $args['default'] ?? null;
    }

    return $args['default'] ?? null;
}

/**
 * Update (save) a field value into post/user/term meta or options.
 *
 * @since 2.1.0
 * @param string $name
 * @param mixed  $value
 * @param mixed  $source Context (see hp_resolve_field_context)
 * @param array  $args   { option_group?, type? }
 */
function hp_update_field(string $name, $value, $source = null, array $args = []): bool
{
    $ctx = hp_resolve_field_context($source, $args);
    $sanitized = hp_maybe_sanitize_field_value($name, $value, $args);

    switch ($ctx['type']) {
        case 'post':
            if ($ctx['object_id'] > 0) {
                return (bool) update_post_meta($ctx['object_id'], $name, $sanitized);
            }
            break;
        case 'user':
            if ($ctx['object_id'] > 0) {
                return (bool) update_user_meta($ctx['object_id'], $name, $sanitized);
            }
            break;
        case 'term':
            if ($ctx['object_id'] > 0) {
                return (bool) update_term_meta($ctx['object_id'], $name, $sanitized);
            }
            break;
        case 'option':
        default:
            $group = $ctx['option_group'];
            $options = get_option($group, []);
            if (!is_array($options)) {
                $options = [];
            }
            $options[$name] = $sanitized;

            return (bool) update_option($group, $options);
    }

    return false;
}

/**
 * Delete a field value from post/user/term meta or options.
 *
 * @since 2.1.0
 */
function hp_delete_field(string $name, $source = null, array $args = []): bool
{
    $ctx = hp_resolve_field_context($source, $args);

    switch ($ctx['type']) {
        case 'post':
            if ($ctx['object_id'] > 0) {
                return (bool) delete_post_meta($ctx['object_id'], $name);
            }
            break;
        case 'user':
            if ($ctx['object_id'] > 0) {
                return (bool) delete_user_meta($ctx['object_id'], $name);
            }
            break;
        case 'term':
            if ($ctx['object_id'] > 0) {
                return (bool) delete_term_meta($ctx['object_id'], $name);
            }
            break;
        case 'option':
        default:
            $group = $ctx['option_group'];
            $options = get_option($group, []);
            if (!is_array($options)) {
                return false;
            }
            if (array_key_exists($name, $options)) {
                unset($options[$name]);

                return (bool) update_option($group, $options);
            }

            return false;
    }

    return false;
}

/**
 * Alias of hp_update_field for parity with the initial TODO wording.
 *
 * @since 2.1.0
 */
function hp_save_field(string $name, $value, $source = null, array $args = []): bool
{
    return hp_update_field($name, $value, $source, $args);
}

/**
 * Include deprecated functions.
 *
 * @since 2.1.0
 */
require_once HYPERPRESS_ABSPATH . 'includes/deprecated.php';
