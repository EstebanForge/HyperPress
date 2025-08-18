<?php

declare(strict_types=1);

use HyperPress\Fields\Field;
use HyperPress\Fields\OptionsPage;
use HyperPress\Fields\OptionsSection;
use HyperPress\Fields\RepeaterField;
use HyperPress\Fields\TabsField;
use starfederation\datastar\ServerSentEventGenerator;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * @deprecated 2.1.0 Use hp_get_endpoint_url() instead
 */
function hm_get_endpoint_url($template_path = '')
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_get_endpoint_url');

    return hp_get_endpoint_url($template_path);
}

/**
 * @deprecated 2.1.0 Use hp_endpoint_url() instead
 */
function hm_endpoint_url($template_path = ''): void
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_endpoint_url');
    hp_endpoint_url($template_path);
}

/**
 * @deprecated 2.1.0 Use hp_send_header_response() instead
 */
function hm_send_header_response($data = [], $action = null)
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_send_header_response');
    hp_send_header_response($data, $action);
}

/**
 * @deprecated 2.1.0 Use hp_die() instead
 */
function hm_die($message = '', $display_error = false)
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_die');
    hp_die($message, $display_error);
}

/**
 * @deprecated 2.1.0 Use hp_validate_request() instead
 */
function hm_validate_request($hmvals = null, $action = null): bool
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_validate_request');

    return hp_validate_request($hmvals, $action);
}

/**
 * @deprecated 2.1.0 Use hp_is_library_mode() instead
 */
function hm_is_library_mode(): bool
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_is_library_mode');

    return hp_is_library_mode();
}

/**
 * @deprecated 2.1.0 Use hp_ds_sse() instead
 */
function hm_ds_sse(): ?ServerSentEventGenerator
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_sse');

    return hp_ds_sse();
}

/**
 * @deprecated 2.1.0 Use hp_ds_read_signals() instead
 */
function hm_ds_read_signals(): array
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_read_signals');

    return hp_ds_read_signals();
}

/**
 * @deprecated 2.1.0 Use hp_ds_patch_elements() instead
 */
function hm_ds_patch_elements(string $html, array $options = []): void
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_patch_elements');
    hp_ds_patch_elements($html, $options);
}

/**
 * @deprecated 2.1.0 Use hp_ds_remove_elements() instead
 */
function hm_ds_remove_elements(string $selector, array $options = []): void
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_remove_elements');
    hp_ds_remove_elements($selector, $options);
}

/**
 * @deprecated 2.1.0 Use hp_ds_patch_signals() instead
 */
function hm_ds_patch_signals($signals, array $options = []): void
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_patch_signals');
    hp_ds_patch_signals($signals, $options);
}

/**
 * @deprecated 2.1.0 Use hp_ds_execute_script() instead
 */
function hm_ds_execute_script(string $script, array $options = []): void
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_execute_script');
    hp_ds_execute_script($script, $options);
}

/**
 * @deprecated 2.1.0 Use hp_ds_location() instead
 */
function hm_ds_location(string $url): void
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_location');
    hp_ds_location($url);
}

/**
 * @deprecated 2.1.0 Use hp_ds_is_rate_limited() instead
 */
function hm_ds_is_rate_limited(array $options = []): bool
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_ds_is_rate_limited');

    return hp_ds_is_rate_limited($options);
}

/**
 * @deprecated 2.1.0 Use hp_create_option_page() instead
 */
function hf_option_page(string $page_title, string $menu_slug): OptionsPage
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_create_option_page');

    return hp_create_option_page($page_title, $menu_slug);
}

/**
 * @deprecated 2.1.0 Use hp_create_field() instead
 */
function hf_field(string $type, string $name, string $label): Field
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_create_field');

    return hp_create_field($type, $name, $label);
}

/**
 * @deprecated 2.1.0 Use hp_create_tabs() instead
 */
function hf_tabs(string $name, string $label): TabsField
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_create_tabs');

    return hp_create_tabs($name, $label);
}

/**
 * @deprecated 2.1.0 Use hp_create_repeater() instead
 */
function hf_repeater(string $name, string $label): RepeaterField
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_create_repeater');

    return hp_create_repeater($name, $label);
}

/**
 * @deprecated 2.1.0 Use hp_create_section() instead
 */
function hf_section(string $id, string $title): OptionsSection
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_create_section');

    return hp_create_section($id, $title);
}

/**
 * @deprecated 2.1.0 Use hp_resolve_field_context() instead
 */
function hf_resolve_field_context($source = null, array $args = []): array
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_resolve_field_context');

    return hp_resolve_field_context($source, $args);
}

/**
 * @deprecated 2.1.0 Use hp_maybe_sanitize_field_value() instead
 */
function hf_maybe_sanitize_field_value(string $name, $value, array $args = [])
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_maybe_sanitize_field_value');

    return hp_maybe_sanitize_field_value($name, $value, $args);
}

/**
 * @deprecated 2.1.0 Use hp_get_field() instead
 */
function hf_get_field(string $name, $source = null, array $args = [])
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_get_field');

    return hp_get_field($name, $source, $args);
}

/**
 * @deprecated 2.1.0 Use hp_update_field() instead
 */
function hf_update_field(string $name, $value, $source = null, array $args = []): bool
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_update_field');

    return hp_update_field($name, $value, $source, $args);
}

/**
 * @deprecated 2.1.0 Use hp_delete_field() instead
 */
function hf_delete_field(string $name, $source = null, array $args = []): bool
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_delete_field');

    return hp_delete_field($name, $source, $args);
}

/**
 * @deprecated 2.1.0 Use hp_save_field() instead
 */
function hf_save_field(string $name, $value, $source = null, array $args = []): bool
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_save_field');

    return hp_save_field($name, $value, $source, $args);
}

/**
 * @deprecated 2.1.0 Use hp_get_endpoint_url() instead
 */
function hxwp_api_url($template_path = '')
{
    // Set a global flag to indicate that a legacy function has been used.
    $GLOBALS['hyperpress_is_legacy_theme'] = true;

    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_get_endpoint_url');

    return hp_get_endpoint_url($template_path);
}

/**
 * @deprecated 2.1.0 Use hp_send_header_response() instead
 */
function hxwp_send_header_response($data = [], $action = null)
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_send_header_response');

    // Use shared validation logic
    if (!hp_validate_request()) {
        hxwp_die(__('Nonce verification failed.', 'hyperpress'));
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
        wp_die(__('HXWP Error: Headers already sent.', 'hyperpress'));
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
 * @deprecated 2.1.0 Use hp_die() instead
 */
function hxwp_die($message = '', $display_error = false)
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_die');

    hp_die($message, $display_error);
}

/**
 * @deprecated 2.1.0 Use hp_validate_request() instead
 */
function hxwp_validate_request($hxvals = null, $action = null)
{
    _deprecated_function(__FUNCTION__, '2.1.0', 'hp_validate_request');

    return hp_validate_request($hxvals, $action);
}
