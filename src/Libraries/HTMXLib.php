<?php

declare(strict_types=1);

namespace HMApi\Libraries;

use HMApi\Main;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HTMX Class.
 * Handles HTMX related functionalities, such as managing extensions.
 *
 * @since 2.0.2
 */
class HTMXLib
{
    /**
     * Get available HTMX extensions with descriptions using centralized URL management.
     *
     * This method dynamically retrieves the list of available HTMX extensions from the
     * centralized CDN URL system in Main::get_cdn_urls(). It ensures that only extensions
     * that are actually available in the CDN configuration can be displayed and enabled
     * in the admin interface.
     *
     * @since 2.0.2 Adapted from HMApi\Admin\Options
     *
     * @param Main $main_instance Main plugin instance for accessing CDN URLs.
     * @return array {
     *     Array of available HTMX extensions with descriptions.
     *
     *     @type string $extension_key Extension description for display in admin interface.
     * }
     */
    public static function get_extensions(Main $main_instance): array
    {
        $cdn_urls = $main_instance->get_cdn_urls();
        $available_extensions = $cdn_urls['htmx_extensions'] ?? [];

        // Extension descriptions - these remain as fallbacks and for better UX
        $extension_descriptions = [
            // Official extensions
            'sse'                   => esc_html__('Server send events. Uni-directional server push messaging via EventSource', 'api-for-htmx'),
            'ws'                    => esc_html__('WebSockets. Bi-directional connection to WebSocket servers', 'api-for-htmx'),
            'htmx-1-compat'         => esc_html__('HTMX 1.x compatibility mode. Rolls back most of the behavioral changes of htmx 2 to the htmx 1 defaults.', 'api-for-htmx'),
            'preload'               => esc_html__('Preloads selected href and hx-get targets based on rules you control.', 'api-for-htmx'),
            'response-targets'      => esc_html__('Allows to specify different target elements to be swapped when different HTTP response codes are received', 'api-for-htmx'),
            'head-support'          => esc_html__('Support for head tag merging when using HTMX swapping', 'api-for-htmx'),
            'loading-states'        => esc_html__('Allows you to disable inputs, add and remove CSS classes, etc. while a request is in-flight', 'api-for-htmx'),
            // Community extensions
            'ajax-header'           => esc_html__('Includes the commonly-used X-Requested-With header that identifies ajax requests in many backend frameworks', 'api-for-htmx'),
            'alpine-morph'          => esc_html__('An extension for using the Alpine.js morph plugin as the swapping mechanism in htmx.', 'api-for-htmx'),
            'class-tools'           => esc_html__('An extension for manipulating timed addition and removal of classes on HTML elements', 'api-for-htmx'),
            'client-side-templates' => esc_html__('Support for client side template processing of JSON/XML responses', 'api-for-htmx'),
            'debug'                 => esc_html__('An extension for debugging of a particular element using htmx', 'api-for-htmx'),
            'event-header'          => esc_html__('Includes a JSON serialized version of the triggering event, if any', 'api-for-htmx'),
            'include-vals'          => esc_html__('Allows you to include additional values in a request', 'api-for-htmx'),
            'disable-element'       => esc_html__('Allows you to disable elements during requests', 'api-for-htmx'),
            'method-override'       => esc_html__('Supports method override via hidden input or header', 'api-for-htmx'),
            'multi-swap'            => esc_html__('Allows you to swap multiple elements with different swap strategies', 'api-for-htmx'),
            'path-deps'             => esc_html__('Allows you to declare path dependencies for requests', 'api-for-htmx'),
            'path-params'           => esc_html__('Allows you to use path parameters in your URLs', 'api-for-htmx'),
            'restored'              => esc_html__('Allows you to trigger events when elements are restored from cache', 'api-for-htmx'),
            'json-enc'              => esc_html__('Allows encoding request bodies as JSON', 'api-for-htmx'),
            'morphdom-swap'         => esc_html__('Allows you to use morphdom as the swapping mechanism', 'api-for-htmx'),
            'remove-me'             => esc_html__('Allows you to remove elements from the DOM after requests', 'api-for-htmx'),
        ];

        // Build the final array with only extensions that are available in CDN URLs
        $result = [];
        foreach (array_keys($available_extensions) as $extension_key) {
            // Create a user-friendly label from the extension key.
            $label = ucwords(str_replace(['-', '_'], ' ', $extension_key));

            $result[$extension_key] = [
                'label'       => $label,
                'description' => $extension_descriptions[$extension_key] ?? sprintf(esc_html__('HTMX %s extension', 'api-for-htmx'), $extension_key),
            ];
        }

        return $result;
    }
}
