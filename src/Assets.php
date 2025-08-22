<?php

/**
 * Load plugin assets.
 *
 * @since   2023-11-22
 */

namespace HyperPress; // Updated namespace

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

        add_action('wp_enqueue_scripts', $this->enqueueFrontendScripts(...));
        add_action('admin_enqueue_scripts', $this->enqueueBackendScripts(...));
        // Enqueue HyperBlocks editor integration for Gutenberg inspector controls
        add_action('enqueue_block_editor_assets', $this->enqueueEditorAssets(...));
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
    public function getOptions()
    {
        if ($this->options === null) {
            $default_options_fallback = [
                'active_library' => 'datastar',
                'load_from_cdn' => 0,
                'load_hyperscript' => 0,
                'load_alpinejs_with_htmx' => 0,
                'set_htmx_hxboost' => 0,
                'load_htmx_backend' => 0,
                'enable_alpinejs_core' => 0,
                'enable_alpine_ajax' => 0,
                'load_alpinejs_backend' => 0,
                'load_datastar_backend' => 0,
            ];

            // Add all HTMX extensions to the defaults
            $htmx_extensions = $this->main->getCdnUrls()['htmx_extensions'] ?? [];
            foreach (array_keys($htmx_extensions) as $extension_key) {
                $default_options_fallback['load_extension_' . $extension_key] = 0;
            }

            // Apply filter to allow programmatic configuration
            $default_options_fallback = apply_filters('hyperpress/assets/default_options', $default_options_fallback);

            $this->options = get_option('hyperpress_options', $default_options_fallback);
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
    public function enqueueFrontendScripts()
    {
        $this->enqueueScriptsLogic(false);
    }

    /**
     * Enqueue backend/admin scripts.
     * WordPress hook callback for admin_enqueue_scripts.
     *
     * @since 2023-11-22
     *
     * @return void
     */
    public function enqueueBackendScripts()
    {
        $this->enqueueScriptsLogic(true);
    }

    /**
     * Enqueue editor-only assets for the block editor (Gutenberg).
     * Mirrors prior bootstrap closure but centralized here.
     */
    public function enqueueEditorAssets(): void
    {
        // Require a valid plugin URL; skip in library mode where URL is unavailable
        if (!defined('HYPERPRESS_PLUGIN_URL') || empty(HYPERPRESS_PLUGIN_URL)) {
            return;
        }

        wp_enqueue_script(
            'hyperpress-hyperblocks-editor',
            HYPERPRESS_PLUGIN_URL . 'assets/js/hyperblocks-editor.js',
            [
                'wp-api-fetch',
                'wp-hooks',
                'wp-element',
                'wp-components',
                'wp-block-editor',
                'wp-editor',
            ],
            defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : false,
            true
        );
    }

    /**
     * Core script enqueuing logic with dynamic URL management.
     *
     * This method handles the intelligent loading of hypermedia libraries and HTMX extensions
     * based on user settings and CDN availability. It uses the centralized URL management
     * system from Main::getCdnUrls() to ensure consistent versioning and availability.
     *
     * Key features:
     * - Dynamic CDN vs local file loading based on user preferences
     * - Automatic fallback to local files when CDN is unavailable
     * - Proper dependency management between libraries and extensions
     * - Version-aware loading with correct cache busting
     * - Conditional loading based on active library and admin settings
     *
     * URL Management Flow:
     * 1. Retrieve centralized CDN URLs and versions from Main::getCdnUrls()
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
     * @see Main::getCdnUrls() For centralized URL and version management
     * @see Admin\Options::getOptions() For user configuration settings
     *
     * @example
     * // Frontend script loading
     * $this->enqueueScriptsLogic(false);
     *
     * // Backend/admin script loading
     * $this->enqueueScriptsLogic(true);
     */
    private function enqueueScriptsLogic(bool $is_admin)
    {
        $options = $this->getOptions();
        $load_from_cdn = !empty($options['load_from_cdn']);
        $active_library = $options['active_library'] ?? 'datastar';

        $htmx_loaded = false;
        $alpine_core_loaded = false;
        $alpine_ajax_loaded = false;
        $datastar_loaded = false;

        // Define base URLs and paths - ensure HYPERPRESS_PLUGIN_URL and HYPERPRESS_ABSPATH are defined
        $plugin_url = defined('HYPERPRESS_PLUGIN_URL') ? HYPERPRESS_PLUGIN_URL : '';
        $plugin_path = defined('HYPERPRESS_ABSPATH') ? HYPERPRESS_ABSPATH : '';
        $plugin_version = defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : null;

        // Detect library mode (when plugin URL is empty)
        $is_library_mode = empty($plugin_url);

        // In library mode, construct URLs using vendor directory detection
        if ($is_library_mode) {
            $plugin_url = $this->getLibraryModeUrl($plugin_path);
        }

        // Asset definitions
        $assets_config = [
            'htmx' => [
                'local_url' => $plugin_url . 'assets/libs/htmx.min.js',
                'local_path' => $plugin_path . 'assets/libs/htmx.min.js',
            ],
            'hyperscript' => [
                'local_url' => $plugin_url . 'assets/libs/_hyperscript.min.js',
                'local_path' => $plugin_path . 'assets/libs/_hyperscript.min.js',
            ],
            'alpine_core' => [
                'local_url' => $plugin_url . 'assets/libs/alpinejs.min.js',
                'local_path' => $plugin_path . 'assets/libs/alpinejs.min.js',
            ],
            'alpine_ajax' => [
                'local_url' => $plugin_url . 'assets/libs/alpine-ajax.min.js',
                'local_path' => $plugin_path . 'assets/libs/alpine-ajax.min.js',
            ],
            'datastar' => [
                'local_url' => $plugin_url . 'assets/libs/datastar.min.js',
                'local_path' => $plugin_path . 'assets/libs/datastar.min.js',
            ],
        ];

        // Filter: Allow developers to completely override asset configuration
        $assets_config = apply_filters('hyperpress/assets/config', $assets_config, $plugin_url, $plugin_path, $is_library_mode, $load_from_cdn);

        // --- HTMX ---
        $should_load_htmx = false;
        if ($is_admin) {
            $should_load_htmx = !empty($options['load_htmx_backend']);
        } else {
            // Frontend: only load HTMX when it is the active library
            $should_load_htmx = ($active_library === 'htmx');
        }

        if ($should_load_htmx) {
            $cdn_urls = $this->main->getCdnUrls();
            $asset = $assets_config['htmx'];
            $url = $load_from_cdn ? $cdn_urls['htmx']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['htmx']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);

            // Filter: Allow developers to override HTMX library URL
            $url = apply_filters('hyperpress/assets/htmx_url', $url, $load_from_cdn, $asset, $is_library_mode);
            $ver = apply_filters('hyperpress/assets/htmx_version', $ver, $load_from_cdn, $asset, $is_library_mode);

            wp_enqueue_script('hyperpress-htmx', $url, [], $ver, true);
            $htmx_loaded = true;
        }

        // --- Hyperscript ---
        if (!empty($options['load_hyperscript']) && ($is_admin ? !empty($options['load_htmx_backend']) : ($active_library === 'htmx'))) { // Load only with HTMX or when backend HTMX is on
            $cdn_urls = $this->main->getCdnUrls();
            $asset = $assets_config['hyperscript'];
            $url = $load_from_cdn ? $cdn_urls['hyperscript']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['hyperscript']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);

            // Filter: Allow developers to override Hyperscript library URL
            $url = apply_filters('hyperpress/assets/hyperscript_url', $url, $load_from_cdn, $asset, $is_library_mode);
            $ver = apply_filters('hyperpress/assets/hyperscript_version', $ver, $load_from_cdn, $asset, $is_library_mode);

            wp_enqueue_script('hyperpress-hyperscript', $url, ($htmx_loaded ? ['hyperpress-htmx'] : []), $ver, true);
        }

        // --- Alpine.js Core ---
        $should_load_alpine_core = false;
        if ($is_admin) {
            $should_load_alpine_core = !empty($options['load_alpinejs_backend']);
        } else {
            if ($active_library === 'alpinejs' && !empty($options['enable_alpinejs_core'])) {
                $should_load_alpine_core = true;
            }
            if (!empty($options['load_alpinejs_with_htmx']) && $active_library === 'htmx') { // HTMX companion only when HTMX is active
                $should_load_alpine_core = true;
            }
        }

        if ($should_load_alpine_core) {
            $cdn_urls = $this->main->getCdnUrls();
            $asset = $assets_config['alpine_core'];
            $url = $load_from_cdn ? $cdn_urls['alpinejs']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['alpinejs']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);

            // Filter: Allow developers to override Alpine.js library URL
            $url = apply_filters('hyperpress/assets/alpinejs_url', $url, $load_from_cdn, $asset, $is_library_mode);
            $ver = apply_filters('hyperpress/assets/alpinejs_version', $ver, $load_from_cdn, $asset, $is_library_mode);

            wp_enqueue_script('hyperpress-alpinejs-core', $url, [], $ver, true);
            $alpine_core_loaded = true;
        }

        // --- Alpine Ajax (Frontend only, depends on Alpine Core) ---
        if (!$is_admin && $active_library === 'alpinejs' && $alpine_core_loaded && !empty($options['enable_alpine_ajax'])) {
            $cdn_urls = $this->main->getCdnUrls();
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

            // Filter: Allow developers to override Alpine Ajax library URL
            $url = apply_filters('hyperpress/assets/alpine_ajax_url', $url, $load_from_cdn, $asset, $is_library_mode);
            $ver = apply_filters('hyperpress/assets/alpine_ajax_version', $ver, $load_from_cdn, $asset, $is_library_mode);

            if ($url) {
                wp_enqueue_script('hyperpress-alpine-ajax', $url, ['hyperpress-alpinejs-core'], $ver, true);
                $alpine_ajax_loaded = true;
            }
        }

        // --- Datastar ---
        $should_load_datastar = false;
        if ($is_admin) {
            $should_load_datastar = !empty($options['load_datastar_backend']);
        } else {
            $should_load_datastar = ($active_library === 'datastar');
        }

        if ($should_load_datastar) {
            $cdn_urls = $this->main->getCdnUrls();
            $asset = $assets_config['datastar'];
            $url = $load_from_cdn ? $cdn_urls['datastar']['url'] : $asset['local_url'];
            $ver = $load_from_cdn ? $cdn_urls['datastar']['version'] : (file_exists($asset['local_path']) ? filemtime($asset['local_path']) : $plugin_version);

            // Filter: Allow developers to override Datastar library URL
            $url = apply_filters('hyperpress/assets/datastar_url', $url, $load_from_cdn, $asset, $is_library_mode);
            $ver = apply_filters('hyperpress/assets/datastar_version', $ver, $load_from_cdn, $asset, $is_library_mode);

            wp_enqueue_script_module('hyperpress-datastar', $url, [], $ver);
            $datastar_loaded = true;
        }

        // --- HTMX Extensions ---
        if ($htmx_loaded && ($is_admin ? !empty($options['load_htmx_backend']) : $active_library === 'htmx')) {
            $extensions_dir_local = $plugin_path . 'assets/libs/htmx-extensions/';
            $extensions_dir_url = $plugin_url . 'assets/libs/htmx-extensions/';

            // Filter: Allow developers to override HTMX extensions directory
            $extensions_dir_url = apply_filters('hyperpress/assets/htmx_extensions_url', $extensions_dir_url, $extensions_dir_local, $plugin_url, $plugin_path, $is_library_mode);

            $cdn_urls = $this->main->getCdnUrls();

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
                        // Try local files (works for both plugin and library mode now)
                        $local_file_path = $extensions_dir_local . $ext_slug . '.js';
                        if (file_exists($local_file_path)) {
                            $ext_url = $extensions_dir_url . $ext_slug . '.js';
                            $ext_ver = filemtime($local_file_path);
                        }
                    }

                    // Filter: Allow developers to override HTMX extension URLs
                    $ext_url = apply_filters('hyperpress/assets/htmx_extension_url', $ext_url, $ext_slug, $load_from_cdn, $is_library_mode);
                    $ext_ver = apply_filters('hyperpress/assets/htmx_extension_version', $ext_ver, $ext_slug, $load_from_cdn, $is_library_mode);

                    if ($ext_url) {
                        wp_enqueue_script('hyperpress-htmx-ext-' . $ext_slug, $ext_url, ['hyperpress-htmx'], $ext_ver, true);
                    }
                }
            }
        }

        // --- Centralized Hypermedia Library Configuration ---
        $this->configureHypermediaLibraries($htmx_loaded, $alpine_ajax_loaded, $datastar_loaded, $is_admin, $options);

        if ($is_admin) {
            do_action('hyperpress/enqueue/backend_scripts_end', $options);
        } else {
            do_action('hyperpress/enqueue/frontend_scripts_end', $options);
        }
    }

    /**
     * Construct the proper URL for assets when running in library mode.
     *
     * When the plugin is loaded as a Composer library, assets are available at paths like:
     * wp-content/plugins/some-plugin/vendor-dist/estebanforge/hyperpress/assets/libs/
     *
     * This method detects the vendor directory {
     *      vendor-dist
     *      vendor-prefixed
     *      vendor-prefix
     *      vendor-custom
     *      vendor
     * }
     * And constructs the public URL to reach the plugin's assets, respecting privacy by avoiding CDN.
     *
     * @since 2.0.5
     *
     * @param string $plugin_path The absolute filesystem path to the plugin directory
     * @return string The public URL to the plugin directory, or empty string if unable to detect
     */
    private function getLibraryModeUrl(string $plugin_path): string
    {
        // Normalize the plugin path
        $plugin_path = rtrim($plugin_path, '/');

        // Get WordPress content directory paths
        $content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
        $content_url = defined('WP_CONTENT_URL') ? WP_CONTENT_URL : get_site_url() . '/wp-content';

        // Normalize content directory path
        $content_dir = rtrim($content_dir, '/');
        $content_url = rtrim($content_url, '/');

        // Supported vendor directory names for explicit detection
        $vendor_directories = [
            'vendor-dist',
            'vendor-prefixed',
            'vendor-prefix',
            'vendor-custom',
            'vendor',
        ];

        // Check if plugin path is within wp-content directory
        if (strpos($plugin_path, $content_dir) === 0) {
            // Get the relative path from wp-content
            $relative_path = substr($plugin_path, strlen($content_dir));

            // Explicitly validate that this is a supported vendor directory structure
            foreach ($vendor_directories as $vendor_dir) {
                if (strpos($relative_path, '/' . $vendor_dir . '/') !== false) {
                    // Construct and return the URL for valid vendor directory
                    return $content_url . $relative_path . '/';
                }
            }

            // For non-vendor paths (direct plugin installation), also allow
            if (strpos($relative_path, '/plugins/') === 0) {
                return $content_url . $relative_path . '/';
            }
        }

        // Fallback: try to detect plugin directory pattern with explicit vendor directory validation
        // Look for patterns like: /wp-content/plugins/some-plugin/vendor-*/estebanforge/hyperpress/
        foreach ($vendor_directories as $vendor_dir) {
            $pattern = '#/wp-content/(.+/' . preg_quote($vendor_dir, '#') . '/.+)$#';
            if (preg_match($pattern, $plugin_path, $matches)) {
                return $content_url . '/' . $matches[1] . '/';
            }
        }

        // Final fallback for any wp-content path (maintains backward compatibility)
        if (preg_match('#/wp-content/(.+)$#', $plugin_path, $matches)) {
            return $content_url . '/' . $matches[1] . '/';
        }

        // If all else fails, return empty string to maintain current behavior
        return '';
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
    private function configureHypermediaLibraries(bool $htmx_loaded, bool $alpine_ajax_loaded, bool $datastar_loaded, bool $is_admin, array $options): void
    {
        // Only configure if at least one library is loaded
        if (!$htmx_loaded && !$alpine_ajax_loaded && !$datastar_loaded) {
            return;
        }

        // Determine which script to attach the configuration to
        $primary_script_handle = '';
        if ($htmx_loaded) {
            $primary_script_handle = 'hyperpress-htmx';
        } elseif ($alpine_ajax_loaded) {
            $primary_script_handle = 'hyperpress-alpine-ajax';
        } elseif ($datastar_loaded) {
            $primary_script_handle = 'hyperpress-datastar';
        }

        if (empty($primary_script_handle)) {
            return;
        }

        // Localize script with shared parameters for all libraries
        wp_localize_script($primary_script_handle, 'hyperpress_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'api_url' => hp_get_endpoint_url(),
            'legacy_api_url' => @hxwp_api_url(),
            'nonce' => wp_create_nonce('hyperpress_nonce'),
            'rest_url' => rest_url(),
            'legacy_nonce' => wp_create_nonce('hxwp_nonce'),
            'is_legacy_theme' => !empty($GLOBALS['hyperpress_is_legacy_theme']),
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
// HyperPress nonce configuration for all libraries
(function() {
    'use strict';

    // Helper function to get the appropriate nonce
    function getHyperPressNonce() {
        if (typeof hyperpress_params !== 'undefined' && hyperpress_params) {
            return hyperpress_params.is_legacy_theme ? hyperpress_params.legacy_nonce : hyperpress_params.nonce;
        }
        return null;
    }";

        // HTMX Configuration
        if ($htmx_loaded) {
            $inline_script_parts[] = "
    // HTMX: Auto-configure nonces for all requests
    if (typeof htmx !== 'undefined') {
        document.body.addEventListener('htmx:configRequest', function(evt) {
            const nonce = getHyperPressNonce();
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
            const nonce = getHyperPressNonce();
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
            const nonce = getHyperPressNonce();
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
        $complete_inline_script = apply_filters('hyperpress/assets/inline_script', $complete_inline_script, [
            'htmx_loaded' => $htmx_loaded,
            'alpine_ajax_loaded' => $alpine_ajax_loaded,
            'datastar_loaded' => $datastar_loaded,
            'is_admin' => $is_admin,
            'options' => $options,
        ]);

        // Legacy filter for backward compatibility
        if ($htmx_loaded) {
            $complete_inline_script = apply_filters('hyperpress/assets/htmx_inline_script', $complete_inline_script, $options);
        }

        // Add the inline script
        wp_add_inline_script($primary_script_handle, $complete_inline_script);
    }
}
