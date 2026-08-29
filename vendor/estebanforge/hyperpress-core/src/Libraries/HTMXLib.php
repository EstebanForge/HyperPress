<?php

declare(strict_types=1);

namespace HyperPress\Libraries;

use HyperPress\Main;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
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
     * centralized CDN URL system in Main::getCdnUrls(). It ensures that only extensions
     * that are actually available in the CDN configuration can be displayed and enabled
     * in the admin interface.
     *
     * @since 2.0.2 Adapted from HyperPress\Admin\Options
     * @since 2.1.0 Version-aware: htmx 4 extensions ship inside the htmx.org
     *              package and are incompatible with the htmx 2 extension list.
     *
     * @param Main   $main_instance Main plugin instance for accessing CDN URLs.
     * @param string $version       htmx major line ('2' or '4'). Defaults to
     *                              '2' so existing call sites keep their list.
     * @return array {
     *     Array of available HTMX extensions with descriptions.
     *
     *     @type string $extension_key Extension description for display in admin interface.
     * }
     */
    public static function getExtensions(Main $main_instance, string $version = '2'): array
    {
        $cdn_urls = $main_instance->getCdnUrls();

        if ($version === '4') {
            return self::getV4Extensions($cdn_urls['htmx4_extensions'] ?? []);
        }

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
            'include-vals'          => esc_html__('Allows you to include additional values in a request. Caution: evaluates the include-vals attribute content as JavaScript (upstream extension design); never use it with user-influenced attribute values, and keep it disabled on sites you do not fully control', 'api-for-htmx'),
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
                /* translators: %s: HTMX extension key (for example, "sse" or "preload"). */
                'description' => $extension_descriptions[$extension_key] ?? sprintf(esc_html__('HTMX %s extension', 'api-for-htmx'), $extension_key),
            ];
        }

        return $result;
    }

    /**
     * Build the htmx 4.x extension registry.
     *
     * Descriptions follow the official four.htmx.org extensions index. In
     * htmx 4 these scripts auto-register on load: no hx-ext attribute needed.
     * hx-live is excluded: it is controlled by the dedicated load_hxlive
     * option, not the per-extension toggles.
     *
     * @param array $available_extensions Map of slug => url/version from getCdnUrls().
     * @return array Map of slug => label/description.
     */
    private static function getV4Extensions(array $available_extensions): array
    {
        $descriptions = [
            'htmx-2-compat'        => esc_html__('Migration bridge: restores htmx 2.x defaults (implicit attribute inheritance, old event names, old error-response handling). Recommended when moving an existing site from 2.x to 4.x.', 'api-for-htmx'),
            'hx-sse'               => esc_html__('Stream HTML over Server-Sent Events (rewritten for htmx 4). Basic SSE via hx-sse:connect works without it.', 'api-for-htmx'),
            'hx-ws'                => esc_html__('Stream HTML and send data over WebSockets (rewritten for htmx 4).', 'api-for-htmx'),
            'hx-multipart'         => esc_html__('Stream HTML over multipart/mixed responses.', 'api-for-htmx'),
            'hx-head'              => esc_html__('Merge <head> tag changes from swapped fragments (head-support successor).', 'api-for-htmx'),
            'hx-pending'           => esc_html__('Show custom content during requests, from loading states to optimistic updates.', 'api-for-htmx'),
            'hx-browser-indicator' => esc_html__("Show the browser tab's loading spinner during requests.", 'api-for-htmx'),
            'hx-prompt'            => esc_html__('Restores the hx-prompt attribute: prompts before a request and sends the answer in the HX-Prompt header.', 'api-for-htmx'),
            'hx-preload'           => esc_html__('Preload content on hover to speed up requests.', 'api-for-htmx'),
            'hx-ptag'              => esc_html__('Skip unchanged polling swaps via the HX-PTag header.', 'api-for-htmx'),
            'hx-history-cache'     => esc_html__('Restore back/forward pages from sessionStorage (replaces the removed localStorage history).', 'api-for-htmx'),
            'hx-download'          => esc_html__("Save responses as file downloads via hx-swap='download'.", 'api-for-htmx'),
            'hx-alpine-compat'     => esc_html__('Run htmx alongside Alpine.js without conflicts.', 'api-for-htmx'),
            'hx-targets'           => esc_html__("Swap one response into many elements via hx-targets='selector'.", 'api-for-htmx'),
            'hx-upsert'            => esc_html__('Update-or-insert swap strategy for dynamic lists.', 'api-for-htmx'),
            'hx-csp'               => esc_html__('Make htmx work under a strict Content Security Policy (nonce-based).', 'api-for-htmx'),
        ];

        $result = [];
        foreach (array_keys($available_extensions) as $extension_key) {
            if ($extension_key === 'hx-live') {
                continue;
            }

            $result[$extension_key] = [
                'label'       => ucwords(str_replace(['-', '_'], ' ', $extension_key)),
                'description' => $descriptions[$extension_key] ?? sprintf(esc_html__('htmx %s extension', 'api-for-htmx'), $extension_key),
            ];
        }

        return $result;
    }
}
