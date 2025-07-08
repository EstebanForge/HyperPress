<?php

/**
 * Load plugin Options.
 *
 * @since   2023
 */

namespace HMApi\Admin;

use HMApi\Jeffreyvr\WPSettings\WPSettings;
use HMApi\Libraries\Datastar;
use HMApi\Libraries\HTMX;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Options Class.
 * Handles the admin settings page and option management for the plugin.
 *
 * @since 2023-11-22
 */
class Options
{
    /**
     * Main plugin instance for accessing centralized configuration.
     *
     * @var \HMApi\Main
     */
    protected $main;

    /**
     * WordPress option name for storing plugin settings.
     *
     * @var string
     */
    private $option_name = 'hmapi_options';

    /**
     * WP Settings instance for rendering the settings page.
     *
     * @since 1.3.0
     *
     * @var WPSettings
     */
    private $settings;

    /**
     * Datastar SDK Manager instance.
     *
     * @since 2.0.2
     * @var Datastar
     */
    private $datastar_manager;

    /**
     * HTMX Extensions Manager instance.
     *
     * @since 2.0.2
     * @var HTMX
     */
    private $htmx_manager;

    /**
     * Options constructor.
     * Initializes admin hooks and settings page functionality.
     *
     * @since 2023-11-22
     *
     * @param \HMApi\Main $main Main plugin instance for dependency injection.
     */
    public function __construct($main)
    {
        $this->main = $main;
        $this->datastar_manager = new Datastar();
        $this->htmx_manager = new HTMX();

        if (!hm_is_library_mode()) {
            // Register custom option type early, before WPSettings is initialized
            add_filter('wp_settings_option_type_map', [$this, 'register_custom_option_types']);

            add_action('admin_init', [$this, 'page_init'], 100); // Low priority to ensure WP is fully initialized
            add_action('admin_menu', [$this, 'ensure_admin_menu'], 50); // Ensure menu registration
            add_filter('plugin_action_links_' . HMAPI_BASENAME, [$this, 'plugin_action_links']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        }
    }

    /**
     * Register custom option types for WPSettings.
     *
     * @since 2.0.0
     *
     * @param array $options Existing option types
     * @return array Modified option types
     */
    public function register_custom_option_types($options)
    {
        // Ensure WPSettingsOptions class is loaded
        if (!class_exists('HMApi\\Admin\\WPSettingsOptions')) {
            require_once HMAPI_ABSPATH . 'src/Admin/WPSettingsOptions.php';
        }

        // Debug: Check if class exists after loading
        if (class_exists('HMApi\\Admin\\WPSettingsOptions')) {
            error_log('HMAPI: WPSettingsOptions class loaded successfully');
        } else {
            error_log('HMAPI: Failed to load WPSettingsOptions class');
        }

        $options['display'] = 'HMApi\\Admin\\WPSettingsOptions';

        // Debug: Check what we're returning
        error_log('HMAPI: Registered option types: ' . print_r($options, true));

        return $options;
    }

    /**
     * Ensure admin menu registration.
     * Checks if WP Settings registered the menu, and if not, adds it manually.
     * Also ensures settings are initialized if they weren't already.
     *
     * @since 2023-11-22
     *
     * @return void
     */
    public function ensure_admin_menu()
    {
        // Ensure settings are initialized
        if (!isset($this->settings)) {
            $this->page_init();
        }

        // Check if the page was registered by WP Settings
        global $submenu;
        $page_exists = false;

        if (isset($submenu['options-general.php'])) {
            foreach ($submenu['options-general.php'] as $submenu_item) {
                if (isset($submenu_item[2]) && $submenu_item[2] === 'hypermedia-api-options') {
                    $page_exists = true;
                    break;
                }
            }
        }

        if (!$page_exists) {
            // WP Settings didn't register the page, add it manually
            add_options_page(
                esc_html__('Hypermedia API Options', 'api-for-htmx'),
                esc_html__('Hypermedia API', 'api-for-htmx'),
                'manage_options',
                'hypermedia-api-options',
                [$this, 'render_fallback_page']
            );
        }
    }

    /**
     * Render fallback settings page.
     * Uses WP Settings library render method if available, otherwise shows basic page.
     *
     * @since 2023-11-22
     *
     * @return void
     */
    public function render_fallback_page()
    {
        if (isset($this->settings)) {
            $this->settings->render();

            // Add our settings footer: active instance, proudly brought to you by Actitud Studio
            $plugin_info_html = $this->get_plugin_info_html(false);
            echo $plugin_info_html;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Hypermedia API Options', 'api-for-htmx') . '</h1>';
            echo '<p>' . esc_html__('Settings are loading... If this message persists, please refresh the page.', 'api-for-htmx') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Enqueue admin-specific JavaScript files.
     * Loads JavaScript only on the plugin's settings page for enhanced functionality.
     *
     * @since 2023-11-22
     *
     * @param string $hook_suffix Current admin page hook suffix.
     *
     * @return void
     */
    public function enqueue_admin_scripts($hook_suffix)
    {
        // No admin scripts needed anymore - WP Settings library handles everything

    }

    /**
     * Get available HTMX extensions with descriptions using centralized URL management.
     *
     * This method dynamically retrieves the list of available HTMX extensions from the
     * centralized CDN URL system in Main::get_cdn_urls(). It ensures that only extensions
     * that are actually available in the CDN configuration can be displayed and enabled
     * in the admin interface.
     *
     * Features:
     * - Dynamic extension discovery from centralized URL management
     * - Fallback descriptions for better user experience
     * - Automatic filtering to show only available extensions
     * - Consistent naming and description formatting
     *
     * The method maintains a local array of extension descriptions for user-friendly
     * display purposes, but the actual availability is determined by the CDN URLs
     * configured in the Main class.
     *
     * @since 2023-11-22
     * @since 1.3.0 Refactored to use centralized URL management for dynamic extension discovery
     * @since 2.0.2 Moved to HTMX class
     *
     * @return array {
     *     Array of available HTMX extensions with descriptions.
     *
     *     @type string $extension_key Extension description for display in admin interface.
     * }
     *
     * @see Main::get_cdn_urls() For centralized extension URL management
     * @see page_init() For usage in settings page generation
     * @see sanitize() For validation against available extensions
     *
     * @example
     * // Get available extensions for admin interface
     * $extensions = $this->get_htmx_extensions();
     *
     * // Check if specific extension is available
     * if (isset($extensions['sse'])) {
     *     // SSE extension is available
     * }
     */
    private function get_htmx_extensions(): array
    {
        return $this->htmx_manager::get_extensions($this->main);
    }

    /**
     * Get Datastar SDK status information.
     *
     * Checks if the Datastar PHP SDK is available and provides status information
     * for display in the admin interface. Also handles automatic loading when
     * Datastar is selected as the active library.
     *
     * @since 2.0.1
     * @since 2.0.2 Moved to Datastar class
     *
     * @return array {
     *     SDK status information array.
     *
     *     @type bool   $loaded  Whether the SDK is loaded and available.
     *     @type string $version SDK version if available, empty if not.
     *     @type string $html    HTML content for admin display.
     *     @type string $message Status message for logging/debugging.
     * }
     */
    private function get_datastar_sdk_status(): array
    {
        return $this->datastar_manager::get_sdk_status($this->option_name);
    }

    /**
     * Load Datastar PHP SDK if available.
     *
     * Attempts to load the Datastar PHP SDK through Composer autoloader.
     * Only loads if not already available to prevent conflicts.
     *
     * @since 2.0.1
     * @since 2.0.2 Moved to Datastar class
     *
     * @return bool True if SDK is loaded and available, false otherwise.
     */
    private function load_datastar_sdk(): bool
    {
        return $this->datastar_manager::load_sdk();
    }

    /**
     * Initialize settings page sections and fields.
     * Registers all settings fields, sections, and tabs using WPSettings library.
     *
     * @since 2023-11-22
     *
     * @return void
     */
    public function page_init()
    {
        $this->settings = new WPSettings(esc_html__('Hypermedia API Options', 'api-for-htmx'), 'hypermedia-api-options');
        $this->settings->set_option_name($this->option_name);
        $this->settings->set_menu_parent_slug('options-general.php');
        $this->settings->set_menu_title(esc_html__('Hypermedia API', 'api-for-htmx'));

        // Create tabs
        $general_tab = $this->settings->add_tab(esc_html__('General Settings', 'api-for-htmx'));
        $htmx_tab = $this->settings->add_tab(esc_html__('HTMX Settings', 'api-for-htmx'));
        $alpinejs_tab = $this->settings->add_tab(esc_html__('Alpine Ajax Settings', 'api-for-htmx'));
        $datastar_tab = $this->settings->add_tab(esc_html__('Datastar Settings', 'api-for-htmx'));
        $about_tab = $this->settings->add_tab(esc_html__('About', 'api-for-htmx'));

        // Create sections with descriptions
        $general_section = $general_tab->add_section(esc_html__('General Settings', 'api-for-htmx'), [
            'description' => esc_html__('Configure which hypermedia library to use and CDN loading preferences.', 'api-for-htmx'),
        ]);

        // Custom option type is now registered in constructor

        $api_url = home_url('/' . HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION . '/');
        $general_section->add_option('display', [
            'name' => 'api_url_info',
            'api_url' => $api_url,
            'title' => esc_html__('Hypermedia API Endpoint', 'api-for-htmx'),
            'description' => esc_html__('Use this base URL to make requests to the hypermedia API endpoints from your frontend code.', 'api-for-htmx'),
        ]);
        $htmx_section = $htmx_tab->add_section(esc_html__('HTMX Core Settings', 'api-for-htmx'), [
            'description' => esc_html__('Configure HTMX-specific settings and features.', 'api-for-htmx'),
        ]);
        $alpinejs_section = $alpinejs_tab->add_section(esc_html__('Alpine Ajax Settings', 'api-for-htmx'), [
            'description' => esc_html__('Alpine.js automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
        ]);
        $datastar_section = $datastar_tab->add_section(esc_html__('Datastar Settings', 'api-for-htmx'), [
            'description' => esc_html__('Datastar automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
        ]);
        $about_section = $about_tab->add_section(esc_html__('About', 'api-for-htmx'), [
            'description' => esc_html__('Hypermedia API for WordPress is an unofficial plugin that enables the use of HTMX, Alpine AJAX, Datastar, and other hypermedia libraries on your WordPress site, theme, and/or plugins. Intended for software developers.', 'api-for-htmx') . '<br>' .
                esc_html__('Adds a new endpoint /wp-html/v1/ from which you can load any hypermedia template.', 'api-for-htmx') . '<br><br>' .
                esc_html__('Hypermedia is a concept that allows you to build modern web applications, even SPAs, without writing JavaScript. HTMX, Alpine Ajax, and Datastar let you use AJAX, WebSockets, and Server-Sent Events directly in HTML using attributes.', 'api-for-htmx') . '<br><br>' .
                esc_html__('Plugin repository and documentation:', 'api-for-htmx') . ' <a href="https://github.com/EstebanForge/Hypermedia-API-WordPress" target="_blank">https://github.com/EstebanForge/Hypermedia-API-WordPress</a>',
        ]);
        $system_info_section = $about_tab->add_section(esc_html__('System Information', 'api-for-htmx'), [
            'description' => esc_html__('General information about your WordPress installation and this plugin status.', 'api-for-htmx'),
        ]);
        $extensions_section = $htmx_tab->add_section(esc_html__('HTMX Extensions', 'api-for-htmx'), [
            'description' => esc_html__('Enable specific HTMX extensions for enhanced functionality.', 'api-for-htmx'),
        ]);

        // Add options to sections - use 'select' instead of 'choices' for radio buttons
        $general_section->add_option('select', [
            'name' => 'active_library',
            'label' => esc_html__('Active Hypermedia Library', 'api-for-htmx'),
            'description' => esc_html__('Select the primary hypermedia library to activate and configure.', 'api-for-htmx'),
            'options' => [
                'htmx'     => esc_html__('HTMX', 'api-for-htmx'),
                'alpinejs' => esc_html__('Alpine Ajax', 'api-for-htmx'),
                'datastar' => esc_html__('Datastar', 'api-for-htmx'),
            ],
            'default' => 'htmx',
        ]);

        $general_section->add_option('checkbox', [
            'name' => 'load_from_cdn',
            'label' => esc_html__('Load active library from CDN', 'api-for-htmx'),
            'description' => esc_html__('Load libraries from CDN for better performance, or disable to use local copies for version consistency.', 'api-for-htmx'),
        ]);

        $htmx_section->add_option('checkbox', [
            'name' => 'load_hyperscript',
            'label' => esc_html__('Load Hyperscript', 'api-for-htmx'),
            'description' => esc_html__('Enable Hyperscript, a companion scripting language for HTMX.', 'api-for-htmx'),
        ]);

        $htmx_section->add_option('checkbox', [
            'name' => 'load_alpinejs_with_htmx',
            'label' => esc_html__('Load Alpine.js (for HTMX integration)', 'api-for-htmx'),
            'description' => esc_html__('Load Alpine.js alongside HTMX for enhanced reactive functionality.', 'api-for-htmx'),
        ]);

        $htmx_section->add_option('checkbox', [
            'name' => 'set_htmx_hxboost',
            'label' => esc_html__('Auto hx-boost="true" on body', 'api-for-htmx'),
            'description' => esc_html__('Automatically add hx-boost="true" to the body tag for progressive enhancement.', 'api-for-htmx'),
        ]);

        $htmx_section->add_option('checkbox', [
            'name' => 'load_htmx_backend',
            'label' => esc_html__('Load HTMX & Hyperscript in WP Admin', 'api-for-htmx'),
            'description' => esc_html__('Load HTMX and Hyperscript in the WordPress admin area.', 'api-for-htmx'),
        ]);

        // Only backend loading option for Alpine.js
        $alpinejs_section->add_option('checkbox', [
            'name' => 'load_alpinejs_backend',
            'label' => esc_html__('Load Alpine.js in WP Admin', 'api-for-htmx'),
            'description' => esc_html__('Load Alpine.js in the WordPress admin area.', 'api-for-htmx'),
        ]);

        // Only backend loading option for Datastar
        $datastar_section->add_option('checkbox', [
            'name' => 'load_datastar_backend',
            'label' => esc_html__('Load Datastar.js in WP Admin', 'api-for-htmx'),
            'description' => esc_html__('Load Datastar.js in the WordPress admin area.', 'api-for-htmx'),
        ]);

        // Add Datastar PHP SDK information
        $datastar_sdk_status = $this->get_datastar_sdk_status();
        $datastar_section->add_option('display', [
            'name' => 'datastar_sdk_info',
            'content' => $datastar_sdk_status['html'],
            'title' => esc_html__('Datastar PHP SDK', 'api-for-htmx'),
            'description' => esc_html__('Server-side SDK for generating Datastar responses and handling signals.', 'api-for-htmx'),
        ]);

        $htmx_extensions = $this->get_htmx_extensions();
        foreach ($htmx_extensions as $key => $extension_desc) {
            $extensions_section->add_option('checkbox', [
                'name' => 'load_extension_' . $key,
                'label' => esc_html__('Load', 'api-for-htmx') . ' ' . esc_html($key),
            ]);
        }

        // Add library information tables
        $cdn_urls = $this->main->get_cdn_urls();

        // Core libraries table for end users
        $core_libraries = [];
        $core_lib_names = ['htmx', 'hyperscript', 'alpinejs', 'alpine_ajax', 'datastar'];
        foreach ($core_lib_names as $lib) {
            if (isset($cdn_urls[$lib])) {
                $lib_data = $cdn_urls[$lib];
                $core_libraries[] = [
                    ucfirst(str_replace('_', ' ', $lib)),
                    $lib_data['version'] ?? 'N/A',
                    '<a href="' . esc_url($lib_data['url'] ?? '') . '" target="_blank">' . esc_html($lib_data['url'] ?? 'N/A') . '</a>',
                ];
            }
        }

        if (!empty($core_libraries)) {
            $system_info_section->add_option('display', [
                'name' => 'core_libraries_debug',
                'debug_data' => $core_libraries,
                'table_title' => esc_html__('Core Libraries', 'api-for-htmx'),
                'table_headers' => [
                    ['text' => esc_html__('Library', 'api-for-htmx'), 'style' => 'width: 150px;'],
                    ['text' => esc_html__('Version', 'api-for-htmx'), 'style' => 'width: 100px;'],
                    ['text' => esc_html__('CDN URL', 'api-for-htmx')],
                ],
            ]);
        }

        // HTMX Extensions table for end users
        $options = get_option($this->option_name);
        if (
            isset($options['active_library']) && $options['active_library'] === 'htmx' &&
            isset($cdn_urls['htmx_extensions']) && !empty($cdn_urls['htmx_extensions'])
        ) {
            $extensions_data = [];
            foreach ($cdn_urls['htmx_extensions'] as $ext_name => $ext_data) {
                $extensions_data[] = [
                    esc_html($ext_name),
                    $ext_data['version'] ?? 'N/A',
                    '<a href="' . esc_url($ext_data['url'] ?? '') . '" target="_blank">' . esc_html($ext_data['url'] ?? 'N/A') . '</a>',
                ];
            }

            $system_info_section->add_option('display', [
                'name' => 'extensions_debug',
                'debug_data' => $extensions_data,
                'table_title' => sprintf(esc_html__('HTMX Extensions (%d available)', 'api-for-htmx'), count($cdn_urls['htmx_extensions'])),
                'table_headers' => [
                    ['text' => esc_html__('Extension', 'api-for-htmx'), 'style' => 'width: 200px;'],
                    ['text' => esc_html__('Version', 'api-for-htmx'), 'style' => 'width: 100px;'],
                    ['text' => esc_html__('CDN URL', 'api-for-htmx')],
                ],
            ]);
        }

        // Additional debug information
        $additional_debug = [
            esc_html__('Plugin Version:', 'api-for-htmx') => defined('HMAPI_VERSION') ? HMAPI_VERSION : 'Unknown',
            esc_html__('Total Libraries:', 'api-for-htmx') => count($cdn_urls) - 1, // -1 for the htmx extensions on the array
            esc_html__('Total Extensions:', 'api-for-htmx') => isset($cdn_urls['htmx_extensions']) ? count($cdn_urls['htmx_extensions']) : 0,
            esc_html__('Generated:', 'api-for-htmx') => current_time('mysql'),
        ];

        $system_info_section->add_option('display', [
            'name' => 'additional_debug',
            'debug_data' => $additional_debug,
            'table_title' => esc_html__('Plugin Status', 'api-for-htmx'),
        ]);

        // Add plugin information to System Information section
        $plugin_info_html = $this->get_plugin_info_html(true);
        $system_info_section->add_option('display', [
            'name' => 'plugin_info',
            'content' => $plugin_info_html,
        ]);

        $this->settings->make();
    }

    /**
     * Add link to plugins settings page on plugins list page.
     *
     * @param array $links
     *
     * @return array
     */
    public function plugin_action_links($links)
    {
        $links[] = '<a href="' . esc_url(admin_url('options-general.php?page=hypermedia-api-options')) . '">' . esc_html__('Settings', 'api-for-htmx') . '</a>';

        return $links;
    }

    /**
     * Generate plugin information HTML.
     *
     * Creates the standardized plugin information display including active instance
     * and attribution that appears throughout the admin interface.
     *
     * @since 2.0.1
     *
     * @param bool $detailed Whether to include detailed information (for About tab)
     *
     * @return string HTML content for plugin information
     */
    private function get_plugin_info_html(bool $detailed = false): string
    {
    $plugin_info_html = '<div class="hmapi-plugin-info" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #555; border-radius: 4px;">';

    if ($detailed) {
        $plugin_info_html .= '<h4 style="margin-top: 0;">' . esc_html__('Plugin Information', 'api-for-htmx') . '</h4>';
    }

    $plugin_info_html .= '<p class="description" style="margin: 0;">';

    if (defined('HMAPI_INSTANCE_LOADED_PATH')) {
        // Normalize paths to handle symlinks and path variations
        $real_instance_path = realpath(HMAPI_INSTANCE_LOADED_PATH);
        $real_plugin_dir = realpath(WP_PLUGIN_DIR);

        if ($real_instance_path && $real_plugin_dir) {
            // Normalize path separators and ensure consistent comparison
            $real_instance_path = wp_normalize_path($real_instance_path);
            $real_plugin_dir = wp_normalize_path($real_plugin_dir);

            // First, check if this looks like our main plugin file regardless of location
            $is_main_plugin_file = (
                str_ends_with($real_instance_path, '/api-for-htmx.php') ||
                str_ends_with($real_instance_path, '\\api-for-htmx.php') ||
                basename($real_instance_path) === 'api-for-htmx.php'
            );

            if ($is_main_plugin_file) {
                // Check if instance is within the WordPress plugins directory
                if (str_starts_with($real_instance_path, $real_plugin_dir)) {
                    $instance_type = esc_html__('Plugin', 'api-for-htmx');
                } else {
                    // It's the main plugin file but loaded from outside plugins dir (development setup)
                    $instance_type = esc_html__('Plugin (development)', 'api-for-htmx');
                }
            } else {
                // Check if instance is within the WordPress plugins directory
                if (str_starts_with($real_instance_path, $real_plugin_dir)) {
                    // Additional check: see if the basename matches our expected plugin structure
                    $instance_basename = plugin_basename($real_instance_path);
                    if ($instance_basename === HMAPI_BASENAME ||
                        str_starts_with($instance_basename, 'api-for-htmx/')) {
                        $instance_type = esc_html__('Plugin', 'api-for-htmx');
                    } else {
                        $instance_type = esc_html__('Library (within plugins dir)', 'api-for-htmx');
                    }
                } else {
                    $instance_type = esc_html__('Library (external)', 'api-for-htmx');
                }
            }

            // Set variables for debug output
            $expected_plugin_path = wp_normalize_path($real_plugin_dir . '/' . HMAPI_BASENAME);
            $instance_basename = str_starts_with($real_instance_path, $real_plugin_dir) ?
                plugin_basename($real_instance_path) :
                basename(dirname($real_instance_path)) . '/' . basename($real_instance_path);
        } else {
            $instance_type = esc_html__('Library (path error)', 'api-for-htmx');
        }

        $plugin_info_html .= '<strong>' . esc_html__('Active Instance:', 'api-for-htmx') . '</strong> ' .
            $instance_type . ' v' . esc_html(HMAPI_LOADED_VERSION) . '<br/>';

        // Add debug information if in detailed mode and WP_DEBUG is enabled
        if ($detailed && defined('WP_DEBUG') && WP_DEBUG) {
            $plugin_info_html .= '<br/><small style="font-family: monospace; color: #666;">';
            $plugin_info_html .= '<strong>Debug Info:</strong><br/>';
            $plugin_info_html .= 'Instance Path: ' . esc_html($real_instance_path ?? 'N/A') . '<br/>';
            $plugin_info_html .= 'Plugin Dir: ' . esc_html($real_plugin_dir ?? 'N/A') . '<br/>';
            $plugin_info_html .= 'Expected Path: ' . esc_html($expected_plugin_path ?? 'N/A') . '<br/>';
            $plugin_info_html .= 'Instance Basename: ' . esc_html($instance_basename ?? 'N/A') . '<br/>';
            $plugin_info_html .= 'HMAPI_BASENAME: ' . esc_html(HMAPI_BASENAME) . '<br/>';
            $plugin_info_html .= '</small>';
        }

    }

    if (!$detailed) {
        $plugin_info_html .= sprintf(
            esc_html__('Proudly brought to you by %s.', 'api-for-htmx'),
            '<a href="https://actitud.xyz" target="_blank">' . esc_html__('Actitud Studio', 'api-for-htmx') . '</a>'
        );
    }

    $plugin_info_html .= '</p></div>';

    return $plugin_info_html;
}
}
