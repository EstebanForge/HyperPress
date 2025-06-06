<?php

/**
 * Main Class.
 *
 * @since      2023
 */

namespace HMApi;

use HMApi\Admin\Activation;
use HMApi\Admin\Options;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Class for initialize the plugin.
 * Serves as the central coordinator for all plugin components and manages dependency injection.
 *
 * @since 2023-11-22
 */
class Main
{
    /**
     * Router instance for handling API endpoints.
     *
     * @var Router
     */
    public Router $router;

    /**
     * Render instance for template loading and processing.
     *
     * @var Render
     */
    public Render $render;

    /**
     * Assets manager instance for script and style enqueuing.
     *
     * @var Assets
     */
    public Assets $assets_manager;

    /**
     * Config instance for meta tag and configuration management.
     *
     * @var Config
     */
    public Config $config;

    /**
     * Compatibility instance for handling plugin conflicts.
     *
     * @var Compatibility
     */
    public Compatibility $compatibility;

    /**
     * Theme support instance for theme-related integrations.
     *
     * @var Theme
     */
    public Theme $theme_support;

    /**
     * Options instance for admin settings management.
     *
     * @var Options
     */
    public Options $options;

    /**
     * Constructor.
     * Initializes the plugin with dependency injection for all core components.
     *
     * @since 2023-11-22
     *
     * @param Router        $router        Router instance for API endpoints.
     * @param Render        $render        Render instance for template processing.
     * @param Config        $config        Config instance for meta tags.
     * @param Compatibility $compatibility Compatibility instance for plugin conflicts.
     * @param Theme         $theme_support Theme instance for theme integrations.
     */
    public function __construct(
        Router $router,
        Render $render,
        Config $config,
        Compatibility $compatibility,
        Theme $theme_support
    ) {
        do_action('hmapi/init_construct_start');
        $this->router = $router;
        $this->render = $render;
        $this->config = $config;
        $this->compatibility = $compatibility;
        $this->theme_support = $theme_support;

        $this->assets_manager = new Assets($this);

        if (is_admin()) {
            $this->options = new Options($this);
            new Activation();
        }
        do_action('hmapi/init_construct_end');
    }

    /**
     * Returns all CDN URLs for core libraries and HTMX extensions.
     *
     * This method serves as the centralized source for all external library URLs and versions.
     * It provides CDN URLs for core hypermedia libraries (HTMX, Alpine.js, Datastar, etc.)
     * and all available HTMX extensions with their specific versions.
     *
     * The returned array structure allows for:
     * - Consistent versioning across the plugin
     * - Dynamic library loading based on available CDN resources
     * - Easy maintenance and updates of library versions
     * - Validation of available extensions in the admin interface
     *
     * @since 2023-11-22
     * @since 1.3.0 Refactored to include version information and centralized URL management
     *
     * @return array {
     *     Array of CDN URLs and versions for libraries and extensions.
     *
     *     @type array $htmx {
     *         HTMX core library information.
     *
     *         @type string $url     CDN URL for HTMX core library.
     *         @type string $version Version number of HTMX library.
     *     }
     *     @type array $hyperscript {
     *         Hyperscript library information.
     *
     *         @type string $url     CDN URL for Hyperscript library.
     *         @type string $version Version number of Hyperscript library.
     *     }
     *     @type array $alpinejs {
     *         Alpine.js core library information.
     *
     *         @type string $url     CDN URL for Alpine.js library.
     *         @type string $version Version number of Alpine.js library.
     *     }
     *     @type array $alpine_ajax {
     *         Alpine.js AJAX extension information.
     *
     *         @type string $url     CDN URL for Alpine AJAX extension.
     *         @type string $version Version number of Alpine AJAX extension.
     *     }
     *     @type array $datastar {
     *         Datastar library information.
     *
     *         @type string $url     CDN URL for Datastar library.
     *         @type string $version Version number of Datastar library.
     *     }
     *     @type array $htmx_extensions {
     *         Collection of HTMX extensions with their URLs and versions.
     *         Each extension follows the same structure with 'url' and 'version' keys.
     *         Available extensions include: sse, head-support, response-targets,
     *         loading-states, ws, preload, alpine-morph, json-enc, remove-me,
     *         debug, multi-swap, class-tools, disable-element, client-side-templates,
     *         ajax-header, path-params, event-header, restored, include-vals,
     *         path-deps, morphdom-swap, method-override.
     *
     *         @type array $extension_name {
     *             Individual extension information.
     *
     *             @type string $url     CDN URL for the extension.
     *             @type string $version Version number of the extension.
     *         }
     *     }
     * }
     *
     * @example
     * // Get all CDN URLs
     * $cdn_urls = $this->get_cdn_urls();
     *
     * // Get HTMX core URL
     * $htmx_url = $cdn_urls['htmx']['url'];
     *
     * // Get all HTMX extensions
     * $extensions = $cdn_urls['htmx_extensions'];
     *
     * // Check if specific extension is available
     * if (isset($cdn_urls['htmx_extensions']['sse'])) {
     *     $sse_url = $cdn_urls['htmx_extensions']['sse']['url'];
     * }
     *
     * @see Assets::enqueue_scripts_logic() For usage in script enqueuing
     * @see Admin\Options::get_htmx_extensions() For admin interface integration
     */
    public function get_cdn_urls(): array
    {
        return [
            'htmx' => [
                'url' => 'https://cdn.jsdelivr.net/npm/htmx.org@2/dist/htmx.min.js',
                'version' => '2.0.4',
            ],
            'hyperscript' => [
                'url' => 'https://cdn.jsdelivr.net/npm/hyperscript.org/dist/hdb.min.js',
                'version' => '0.9.14',
            ],
            'alpinejs' => [
                'url' => 'https://cdn.jsdelivr.net/npm/alpinejs/dist/cdn.min.js',
                'version' => '3.14.9',
            ],
            'alpine_ajax' => [
                'url' => 'https://cdn.jsdelivr.net/npm/@imacrayon/alpine-ajax/dist/cdn.min.js',
                'version' => '0.12.2',
            ],
            'datastar' => [
                'url' => 'https://cdn.jsdelivr.net/npm/@starfederation/datastar/dist/datastar.min.js',
                'version' => '1.0.0-beta.11',
            ],
            'htmx_extensions' => [
                'sse' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-sse/sse.min.js',
                    'version' => '2.2.3',
                ],
                'head-support' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-head-support/head-support.min.js',
                    'version' => '2.0.4',
                ],
                'response-targets' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-response-targets/response-targets.min.js',
                    'version' => '2.0.3',
                ],
                'loading-states' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-loading-states/loading-states.min.js',
                    'version' => '2.0.1',
                ],
                'ws' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-ws/ws.min.js',
                    'version' => '2.0.3',
                ],
                'preload' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-preload/preload.min.js',
                    'version' => '2.1.1',
                ],
                'alpine-morph' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-alpine-morph/alpine-morph.min.js',
                    'version' => '2.0.1',
                ],
                'json-enc' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-json-enc/json-enc.min.js',
                    'version' => '2.0.2',
                ],
                'remove-me' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-remove-me/remove-me.min.js',
                    'version' => '2.0.1',
                ],
                'debug' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-debug/debug.min.js',
                    'version' => '2.0.1',
                ],
                'multi-swap' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-multi-swap/multi-swap.min.js',
                    'version' => '2.0.1',
                ],
                'class-tools' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-class-tools/class-tools.min.js',
                    'version' => '2.0.2',
                ],
                'disable-element' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-disable-element/disable-element.min.js',
                    'version' => '2.0.1',
                ],
                'client-side-templates' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-client-side-templates/client-side-templates.min.js',
                    'version' => '2.0.1',
                ],
                'ajax-header' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-ajax-header/dist/ajax-header.esm.min.js',
                    'version' => '2.0.2',
                ],
                'path-params' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-path-params/dist/path-params.esm.min.js',
                    'version' => '2.0.1',
                ],
                'event-header' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-event-header/dist/event-header.esm.min.js',
                    'version' => '2.0.1',
                ],
                'restored' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-restored/dist/restored.esm.min.js',
                    'version' => '2.0.1',
                ],
                'include-vals' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-include-vals/dist/include-vals.esm.min.js',
                    'version' => '2.0.1',
                ],
                'path-deps' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-path-deps/path-deps.min.js',
                    'version' => '2.0.1',
                ],
                'morphdom-swap' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-morphdom-swap/dist/morphdom-swap.esm.min.js',
                    'version' => '2.0.1',
                ],
                'method-override' => [
                    'url' => 'https://cdn.jsdelivr.net/npm/htmx-ext-method-override/dist/method-override.esm.min.js',
                    'version' => '2.0.2',
                ],
            ],
        ];
    }

    /**
     * Main HMApi Instance.
     * Initializes and registers all WordPress hooks and actions for the plugin.
     *
     * This method serves as the main entry point for plugin initialization.
     * It registers all necessary WordPress hooks and starts the plugin components.
     *
     * @since 2023-11-22
     *
     * @return void
     */
    public function run()
    {
        do_action('hmapi/init_run_start');

        add_action('init', [$this->router, 'register_main_route']);
        add_action('template_redirect', [$this->render, 'load_template']);
        add_action('wp_head', [$this->config, 'insert_config_meta_tag']);
        $this->compatibility->run();
        $this->theme_support->run();

        do_action('hmapi/init_run_end');
    }
}
