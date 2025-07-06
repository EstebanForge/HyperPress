<?php

/**
 * Load plugin assets.
 *
 * @since   2023-11-22
 */

namespace HMApi; // Updated namespace

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets Class.
 * Handles script and style enqueuing for hypermedia libraries (HTMX, Alpine.js, Hyperscript, Datastar).
 *
 * @since 2023-11-22
 */
class Assets
{
    /**
     * Main plugin instance for accessing centralized configuration.
     *
     * @var Main
     */
    protected $main;

    /**
     * Cached plugin options to avoid multiple database calls.
     *
     * @var array|null
     */
    private $options = null;

    /**
     * Assets constructor.
     * Initializes the class and registers WordPress hooks for script enqueuing.
     *
     * @since 2023-11-22
     *
     * @param Main $main Main plugin instance for dependency injection.
     */
    public function __construct($main)
    {
        $this->main = $main;

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_backend_scripts']);
    }

    /**
     * Get plugin options with caching and fallback defaults.
     * Retrieves options from WordPress database and caches them for performance.
     * Provides fallback defaults if options are not set.
     *
     * @since 2023-11-22
     *
     * @return array Plugin options with defaults.
     */
    private function get_options()
    {
        if ($this->options === null) {
            $default_options_fallback = [
                'active_library'        => 'htmx',
                'load_from_cdn'         => 0,
                'load_hyperscript'      => 0,
                'load_alpinejs_with_htmx' => 0,
                'set_htmx_hxboost'      => 0,
                'load_htmx_backend'     => 0,
                'enable_alpinejs_core'  => 0,
                'enable_alpine_ajax'    => 0,
                'load_alpinejs_backend' => 0,
                'load_datastar'         => 0,
                'load_datastar_backend' => 0,
            ];

            // Apply filter to allow programmatic configuration
            $default_options_fallback = apply_filters('hmapi/default_options', $default_options_fallback);

            $this->options = get_option('hmapi_options', $default_options_fallback);
        }

        return $this->options;
    }

    /**
     * Enqueue frontend scripts.
     * WordPress hook callback for wp_enqueue_scripts.
     *
     * @since 2023-11-22
     *
     * @return void
     */
    public function enqueue_frontend_scripts()
    {
        $this->enqueue_scripts_logic(false);
    }

    /**
     * Enqueue backend/admin scripts.
     * WordPress hook callback for admin_enqueue_scripts.
     *
     * @since 2023-11-22
     *
     * @return void
     */
    public function enqueue_backend_scripts()
    {
        $this->enqueue_scripts_logic(true);
    }

    /**
     * Core script enqueuing logic with dynamic URL management.
     *
     * This method handles the intelligent loading of hypermedia libraries and HTMX extensions
     * based on user settings and CDN availability. It uses the centralized URL management
     * system from Main::get_cdn_urls() to ensure consistent versioning and availability.
     *
     * Key features:
     * - Dynamic CDN vs local file loading based on user preferences
     * - Automatic fallback to local files when CDN is unavailable
     * - Proper dependency management between libraries and extensions
     * - Version-aware loading with correct cache busting
     * - Conditional loading based on active library and admin settings
     *
     * URL Management Flow:
     * 1. Retrieve centralized CDN URLs and versions from Main::get_cdn_urls()
     * 2. Check user preference for CDN vs local loading
     * 3. For CDN: Use URL and version from centralized system
     * 4. For local: Use local file path with filemtime() for cache busting
     * 5. Validate file existence before enqueuing
     *
     * @since 2023-11-22
     * @since 1.3.0 Refactored to use centralized URL management and version handling
     *
     * @param bool $is_admin Whether to load scripts for admin area or frontend.
     *
     * @return void
     *
     * @see Main::get_cdn_urls() For centralized URL and version management
     * @see Admin\Options::get_options() For user configuration settings
     *
     * @example
     * // Frontend script loading
     * $this->enqueue_scripts_logic(false);
     *
     * // Backend/admin script loading
     * $this->enqueue_scripts_logic(true);
     */
    private function enqueue_scripts_logic(bool $is_admin)
    {
        $options = $this->get_options();
        $load_from_cdn = !empty($options['load_from_cdn']);
        $active_library = $options['active_library'] ?? 'htmx';

        $htmx_loaded = false;
        $alpine_core_loaded = false;
        $alpine_ajax_loaded = false;
        $datastar_loaded = false;

        // Define base URLs and paths - ensure HMAPI_PLUGIN_URL and HMAPI_ABSPATH are defined
        $plugin_url = defined('HMAPI_PLUGIN_URL') ? HMAPI_PLUGIN_URL : '';
        $plugin_path = defined('HMAPI_ABSPATH') ? HMAPI_ABSPATH : '';
        $plugin_version = defined('HMAPI_VERSION') ? HMAPI_VERSION : null;

        // Asset definitions
        $assets_config = [
            'htmx' => [
                'local_url' => $plugin_url . 'assets/js/libs/htmx.min.js',
                'local_path' => $plugin_path . 'assets/js/libs/htmx.min.js',
            ],
            'hyperscript' => [
                'local_url' => $plugin_url . 'assets/js/libs/_hyperscript.min.js',
                'local_path' => $plugin_path . 'assets/js/libs/_hyperscript.min.js',
            ],
            'alpine_core' => [
                'local_url' => $plugin_url . 'assets/js/libs/alpinejs.min.js',
                'local_path' => $plugin_path . 'assets/js/libs/alpinejs.min.js',
            ],
            'alpine_ajax' => [
                'local_url' => $plugin_url . 'assets/js/libs/alpine-ajax.min.js',
                'local_path' => $plugin_path . 'assets/js/libs/alpine-ajax.min.js',
            ],
            'datastar' => [
                'local_url' => $plugin_url . 'assets/js/libs/datastar.min.js',
                'local_path' => $plugin_path . 'assets/js/libs/datastar.min.js',
            ],
        ];

        // --- HTMX ---
        $should_load_htmx = false;
        if ($is_admin) {
            $should_load_htmx = !empty($options['load_htmx_backend']);
        } else {
            $should_load_htmx = ($active_library === 'htmx');
            if (!$should_load_htmx) { // Check if any extension is enabled
                foreach ($options as $key => $value) {
                    if (strpos($key, 'load_extension_') === 0 && !empty($value)) {
                        $should_load_htmx = true;
                        break;
                    }
                }
            }
        }

        if ($should_load_htmx) {
            $cdn_urls = $this->main->get_cdn_urls();
            $asset = $assets_config['htmx'];
            $url = $load_from_cdn ? $cdn_urls['htmx']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['htmx']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);
            wp_enqueue_script('hmapi-htmx', $url, [], $ver, true);
            $htmx_loaded = true;
        }

        // --- Hyperscript ---
        if (!empty($options['load_hyperscript']) && ($is_admin ? !empty($options['load_htmx_backend']) : true)) { // Load if HTMX is likely active or backend HTMX is on
            $cdn_urls = $this->main->get_cdn_urls();
            $asset = $assets_config['hyperscript'];
            $url = $load_from_cdn ? $cdn_urls['hyperscript']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['hyperscript']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);
            wp_enqueue_script('hmapi-hyperscript', $url, ($htmx_loaded ? ['hmapi-htmx'] : []), $ver, true);
        }

        // --- Alpine.js Core ---
        $should_load_alpine_core = false;
        if ($is_admin) {
            $should_load_alpine_core = !empty($options['load_alpinejs_backend']);
        } else {
            if ($active_library === 'alpinejs' && !empty($options['enable_alpinejs_core'])) {
                $should_load_alpine_core = true;
            }
            if (!empty($options['load_alpinejs_with_htmx'])) { // HTMX companion
                $should_load_alpine_core = true;
            }
        }

        if ($should_load_alpine_core) {
            $cdn_urls = $this->main->get_cdn_urls();
            $asset = $assets_config['alpine_core'];
            $url = $load_from_cdn ? $cdn_urls['alpinejs']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['alpinejs']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);
            wp_enqueue_script('hmapi-alpinejs-core', $url, [], $ver, true);
            $alpine_core_loaded = true;
        }

        // --- Alpine Ajax (Frontend only, depends on Alpine Core) ---
        if (!$is_admin && $active_library === 'alpinejs' && $alpine_core_loaded && !empty($options['enable_alpine_ajax'])) {
            $cdn_urls = $this->main->get_cdn_urls();
            $asset = $assets_config['alpine_ajax'];
            $url = '';
            $ver = $plugin_version;
            if ($load_from_cdn) {
                $url = $cdn_urls['alpine_ajax']['url'];
                $ver = $cdn_urls['alpine_ajax']['version'];
            } elseif (file_exists($asset['local_path'])) {
                $url = $asset['local_url'];
                $ver = filemtime($asset['local_path']);
            } // If local not found and CDN not selected, it won't load.

            if ($url) {
                wp_enqueue_script('hmapi-alpine-ajax', $url, ['hmapi-alpinejs-core'], $ver, true);
                $alpine_ajax_loaded = true;
            }
        }

        // --- Datastar ---
        $should_load_datastar = false;
        if ($is_admin) {
            $should_load_datastar = !empty($options['load_datastar_backend']);
        } else {
            $should_load_datastar = ($active_library === 'datastar' && !empty($options['load_datastar']));
        }

        if ($should_load_datastar) {
            $cdn_urls = $this->main->get_cdn_urls();
            $asset = $assets_config['datastar'];
            $url = $load_from_cdn ? $cdn_urls['datastar']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['datastar']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);
            wp_enqueue_script('hmapi-datastar', $url, [], $ver, true);
            $datastar_loaded = true;
        }

        // --- HTMX Extensions ---
        if ($htmx_loaded && ($is_admin ? !empty($options['load_htmx_backend']) : $active_library === 'htmx')) {
            $extensions_dir_local = $plugin_path . 'assets/js/libs/htmx-extensions/';
            $extensions_dir_url = $plugin_url . 'assets/js/libs/htmx-extensions/';
            $cdn_urls = $this->main->get_cdn_urls();

            foreach ($options as $option_key => $option_value) {
                if (strpos($option_key, 'load_extension_') === 0 && !empty($option_value)) {
                    $ext_slug = str_replace('load_extension_', '', $option_key);
                    $ext_slug = str_replace('_', '-', $ext_slug); // Convert option key format to slug format

                    $ext_url = '';
                    $ext_ver = $plugin_version;

                    if ($load_from_cdn) {
                        if (isset($cdn_urls['htmx_extensions'][$ext_slug])) {
                            $ext_url = $cdn_urls['htmx_extensions'][$ext_slug]['url'];
                            $ext_ver = $cdn_urls['htmx_extensions'][$ext_slug]['version'];
                        }
                    } else {
                        // Assumes extension file is $ext_slug.js inside a folder $ext_slug
                        $local_file_path = $extensions_dir_local . $ext_slug . '.js';
                        if (file_exists($local_file_path)) {
                            $ext_url = $extensions_dir_url . $ext_slug . '.js';
                            $ext_ver = filemtime($local_file_path);
                        }
                    }

                    if ($ext_url) {
                        wp_enqueue_script('hmapi-htmx-ext-' . $ext_slug, $ext_url, ['hmapi-htmx'], $ext_ver, true);
                    }
                }
            }
        }

        // --- Centralized Hypermedia Library Configuration ---
        $this->configure_hypermedia_libraries($htmx_loaded, $alpine_ajax_loaded, $datastar_loaded, $is_admin, $options);

        if ($is_admin) {
            do_action('hmapi/enqueue/backend_scripts_end', $options);
        } else {
            do_action('hmapi/enqueue/frontend_scripts_end', $options);
        }
    }

    /**
     * Configure nonce and library-specific settings for all loaded hypermedia libraries.
     *
     * This method provides centralized configuration for HTMX, Alpine Ajax, and Datastar,
     * automatically adding WordPress nonces to all requests and setting up library-specific features.
     *
     * @since 2.0.1
     *
     * @param bool $htmx_loaded Whether HTMX was loaded
     * @param bool $alpine_ajax_loaded Whether Alpine Ajax was loaded
     * @param bool $datastar_loaded Whether Datastar was loaded
     * @param bool $is_admin Whether this is admin context
     * @param array $options Plugin options
     *
     * @return void
     */
    private function configure_hypermedia_libraries(bool $htmx_loaded, bool $alpine_ajax_loaded, bool $datastar_loaded, bool $is_admin, array $options): void
    {
        // Only configure if at least one library is loaded
        if (!$htmx_loaded && !$alpine_ajax_loaded && !$datastar_loaded) {
            return;
        }

        // Determine which script to attach the configuration to
        $primary_script_handle = '';
        if ($htmx_loaded) {
            $primary_script_handle = 'hmapi-htmx';
        } elseif ($alpine_ajax_loaded) {
            $primary_script_handle = 'hmapi-alpine-ajax';
        } elseif ($datastar_loaded) {
            $primary_script_handle = 'hmapi-datastar';
        }

        if (empty($primary_script_handle)) {
            return;
        }

        // Localize script with shared parameters for all libraries
        wp_localize_script($primary_script_handle, 'hmapi_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_url' => hmapi_get_endpoint_url(),
            'legacy_api_url' => @hxwp_api_url(),
            'nonce' => wp_create_nonce('hmapi_nonce'),
            'rest_url' => rest_url(),
            'legacy_nonce' => wp_create_nonce('hxwp_nonce'),
            'is_legacy_theme' => !empty($GLOBALS['hmapi_is_legacy_theme']),
            'libraries_loaded' => [
                'htmx' => $htmx_loaded,
                'alpine_ajax' => $alpine_ajax_loaded,
                'datastar' => $datastar_loaded,
            ],
        ]);

        // Build the comprehensive inline script
        $inline_script_parts = [];

        // Common nonce getter function
        $inline_script_parts[] = "
// Hypermedia API nonce configuration for all libraries
(function() {
    'use strict';

    // Helper function to get the appropriate nonce
    function getHmapiNonce() {
        if (typeof hmapi_params !== 'undefined' && hmapi_params) {
            return hmapi_params.is_legacy_theme ? hmapi_params.legacy_nonce : hmapi_params.nonce;
        }
        return null;
    }";

        // HTMX Configuration
        if ($htmx_loaded) {
            $inline_script_parts[] = "
    // HTMX: Auto-configure nonces for all requests
    if (typeof htmx !== 'undefined') {
        document.body.addEventListener('htmx:configRequest', function(evt) {
            const nonce = getHmapiNonce();
            if (nonce) {
                evt.detail.headers['X-WP-Nonce'] = nonce;
            }
        });
    }";

            // Add hx-boost configuration if enabled
            if (!$is_admin && !empty($options['set_htmx_hxboost'])) {
                $inline_script_parts[] = "
    // HTMX: Configure hx-boost
    if (typeof htmx !== 'undefined') {
        document.body.setAttribute('hx-boost', 'true');
        const adminBar = document.getElementById('wpadminbar');
        if (adminBar) {
            adminBar.setAttribute('hx-boost', 'false');
        }
    }";
            }
        }

        // Alpine Ajax Configuration
        if ($alpine_ajax_loaded) {
            $inline_script_parts[] = "
    // Alpine Ajax: Auto-configure nonces using official method
    document.addEventListener('alpine:init', function() {
        if (typeof Alpine !== 'undefined' && Alpine.ajaxConfig) {
            // Use Alpine Ajax's official global configuration
            const nonce = getHmapiNonce();
            if (nonce) {
                Alpine.ajaxConfig({
                    headers: {
                        'X-WP-Nonce': nonce
                    }
                });
            }
        }
    });";
        }

        // Datastar Configuration
        if ($datastar_loaded) {
            $inline_script_parts[] = "
    // Datastar: Auto-configure nonces using official method
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.ds !== 'undefined') {
            // Set global fetch headers for all Datastar requests
            const nonce = getHmapiNonce();
            if (nonce) {
                // Use Datastar's official method to set default headers
                window.ds.store.upsertSignal('fetchHeaders', {
                    'X-WP-Nonce': nonce
                });
            }
        }
    });";
        }

        // Close the IIFE
        $inline_script_parts[] = '
})();';

        // Combine all script parts
        $complete_inline_script = implode('', $inline_script_parts);

        // Apply filters for extensibility
        $complete_inline_script = apply_filters('hmapi/hypermedia/inline_script', $complete_inline_script, [
            'htmx_loaded' => $htmx_loaded,
            'alpine_ajax_loaded' => $alpine_ajax_loaded,
            'datastar_loaded' => $datastar_loaded,
            'is_admin' => $is_admin,
            'options' => $options,
        ]);

        // Legacy filter for backward compatibility
        if ($htmx_loaded) {
            $complete_inline_script = apply_filters('hmapi/htmx/inline_script', $complete_inline_script, $options);
        }

        // Add the inline script
        wp_add_inline_script($primary_script_handle, $complete_inline_script);
    }
}
