<?php

/**
 * Load plugin Options.
 *
 * @since   2023
 */

namespace HMApi\Admin;

use HMApi\Jeffreyvr\WPSettings\WPSettings;
use HMApi\Libraries\AlpineAjaxLib;
use HMApi\Libraries\DatastarLib;
use HMApi\Libraries\HTMXLib;
use HMApi\Main;

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
     * @var Main
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
     * @var DatastarLib
     */
    private DatastarLib $datastar_manager;

    /**
     * HTMX Extensions Manager instance.
     *
     * @since 2.0.2
     * @var HTMXLib
     */
    private HTMXLib $htmx_manager;

    /**
     * AlpineAjax Manager instance.
     *
     * @since 2.0.2
     * @var AlpineAjaxLib
     */
    private AlpineAjaxLib $alpine_ajax_manager;

    /**
     * The hook suffix for the settings page.
     *
     * @var string|false
     */
    private $hook_suffix = false;

    /**
     * Options constructor.
     * Initializes admin hooks and settings page functionality.
     *
     * @since 2023-11-22
     *
     * @param Main $main Main plugin instance for dependency injection.
     */
    public function __construct($main)
    {
        $this->main = $main;
        $this->datastar_manager = new DatastarLib($this->main);
        $this->htmx_manager = new HTMXLib($this->main);
        $this->alpine_ajax_manager = new AlpineAjaxLib($this->main);

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
        if (!class_exists('HMApi\Admin\WPSettingsOptions')) {
            require_once HMAPI_ABSPATH . 'src/Admin/WPSettingsOptions.php';
        }

        $options['display'] = 'HMApi\Admin\WPSettingsOptions';

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

        // If not, add it manually
        if (!$page_exists) {
            $this->hook_suffix = add_options_page(
                esc_html__('HyperPress Options', 'api-for-htmx'),
                esc_html__('HyperPress', 'api-for-htmx'),
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
            echo '<h1>' . esc_html__('HyperPress Options', 'api-for-htmx') . '</h1>';
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
        // The hook_suffix for our page is 'settings_page_hypermedia-api-options'
        // We also need to check our manually added page's hook suffix.
        if ($hook_suffix === 'settings_page_hypermedia-api-options' || $hook_suffix === $this->hook_suffix) {
            wp_enqueue_script(
                'hmapi-admin-options',
                plugin_dir_url(__FILE__) . 'assets/js/admin-options.js',
                [], // No dependencies
                HMAPI_VERSION, // Cache busting
                true // Load in footer
            );
        }
    }

    /**
     * Get available HTMX extensions with descriptions using centralized URL management.
     *
     * @since 2023-11-22
     * @return array
     */
    private function get_htmx_extensions(): array
    {
        return $this->htmx_manager::get_extensions($this->main);
    }

    /**
     * Load Datastar PHP SDK if available.
     *
     * @since 2.0.1
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
        $options = $this->main->assets_manager->get_options();
        $this->settings = new WPSettings(esc_html__('HyperPress Options', 'api-for-htmx'), 'hypermedia-api-options');
        $this->settings->set_option_name($this->option_name);
        $this->settings->set_menu_parent_slug('options-general.php');
        $this->settings->set_menu_title(esc_html__('HyperPress', 'api-for-htmx'));

        // --- General Tab (Always Visible) ---
        $general_tab = $this->settings->add_tab(esc_html__('General Settings', 'api-for-htmx'));
        $general_section = $general_tab->add_section(esc_html__('General Settings', 'api-for-htmx'), [
            'description' => esc_html__('Configure which hypermedia library to use and CDN loading preferences.', 'api-for-htmx'),
        ]);

        $api_url = home_url('/' . HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION . '/');
        $general_section->add_option('display', [
            'name' => 'api_url_info',
            'api_url' => $api_url,
            'title' => esc_html__('HyperPress API Endpoint', 'api-for-htmx'),
            'description' => esc_html__('Use this base URL to make requests to the HyperPress API endpoints from your frontend code.', 'api-for-htmx'),
        ]);

        $general_section->add_option('select', [
            'name' => 'active_library',
            'label' => esc_html__('Active Hypermedia Library', 'api-for-htmx'),
            'description' => esc_html__('Select the primary hypermedia library to activate and configure. The page will reload to show relevant settings.', 'api-for-htmx'),
            'options' => [
                'htmx'     => esc_html__('HTMX', 'api-for-htmx'),
                'alpinejs' => esc_html__('Alpine Ajax', 'api-for-htmx'),
                'datastar' => esc_html__('Datastar', 'api-for-htmx'),
            ],
            'default' => $options['active_library'] ?? 'htmx',
        ]);

        $general_section->add_option('checkbox', [
            'name' => 'load_from_cdn',
            'label' => esc_html__('Load active library from CDN', 'api-for-htmx'),
            'description' => esc_html__('Load libraries from CDN for better performance, or disable to use local copies for version consistency.', 'api-for-htmx'),
            'default' => $options['load_from_cdn'] ?? false,
        ]);

        // --- Library-Specific Tabs (Conditionally Visible) ---
        // Check for a submitted value first to ensure the UI updates immediately after a change,
        // otherwise fall back to the saved option.
        $active_library = isset($_POST['hmapi_options']['active_library']) ?
            sanitize_text_field($_POST['hmapi_options']['active_library']) : ($options['active_library'] ?? 'htmx');

        if ($active_library === 'htmx') {
            $htmx_tab = $this->settings->add_tab(esc_html__('HTMX Settings', 'api-for-htmx'));
            $htmx_section = $htmx_tab->add_section(esc_html__('HTMX Core Settings', 'api-for-htmx'), [
                'description' => esc_html__('Configure HTMX-specific settings and features.', 'api-for-htmx'),
            ]);
            $extensions_section = $htmx_tab->add_section(esc_html__('HTMX Extensions', 'api-for-htmx'), [
                'description' => esc_html__('Enable specific HTMX extensions for enhanced functionality.', 'api-for-htmx'),
            ]);

            $htmx_section->add_option('checkbox', [
                'name' => 'load_hyperscript',
                'label' => esc_html__('Load Hyperscript with HTMX', 'api-for-htmx'),
                'description' => esc_html__('Automatically load Hyperscript when HTMX is active.', 'api-for-htmx'),
                'default' => $options['load_hyperscript'] ?? true,
            ]);
            $htmx_section->add_option('checkbox', [
                'name' => 'load_alpinejs_with_htmx',
                'label' => esc_html__('Load Alpine.js with HTMX', 'api-for-htmx'),
                'description' => esc_html__('Load Alpine.js alongside HTMX for enhanced interactivity.', 'api-for-htmx'),
                'default' => $options['load_alpinejs_with_htmx'] ?? false,
            ]);
            $htmx_section->add_option('checkbox', [
                'name' => 'set_htmx_hxboost',
                'label' => esc_html__('Enable hx-boost on body', 'api-for-htmx'),
                'description' => esc_html__('Automatically add `hx-boost="true"` to the `<body>` tag for progressive enhancement.', 'api-for-htmx'),
                'default' => $options['set_htmx_hxboost'] ?? false,
            ]);
            $htmx_section->add_option('checkbox', [
                'name' => 'load_htmx_backend',
                'label' => esc_html__('Load HTMX in WP Admin', 'api-for-htmx'),
                'description' => esc_html__('Enable HTMX functionality within the WordPress admin area.', 'api-for-htmx'),
                'default' => $options['load_htmx_backend'] ?? false,
            ]);

            $available_extensions = $this->get_htmx_extensions();
            foreach ($available_extensions as $key => $details) {
                $extensions_section->add_option('checkbox', [
                    'name' => 'load_extension_' . $key,
                    'label' => esc_html($details['label']),
                    'description' => esc_html($details['description']),
                    'default' => $options['load_extension_' . $key] ?? false,
                ]);
            }
        } elseif ($active_library === 'alpinejs') {
            $alpinejs_tab = $this->settings->add_tab(esc_html__('Alpine Ajax Settings', 'api-for-htmx'));
            $alpinejs_section = $alpinejs_tab->add_section(esc_html__('Alpine Ajax Settings', 'api-for-htmx'), [
                'description' => esc_html__('Alpine.js automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
            ]);

            $alpinejs_section->add_option('checkbox', [
                'name' => 'load_alpinejs_backend',
                'label' => esc_html__('Load Alpine Ajax in WP Admin', 'api-for-htmx'),
                'description' => esc_html__('Enable Alpine Ajax functionality within the WordPress admin area.', 'api-for-htmx'),
                'default' => $options['load_alpinejs_backend'] ?? false,
            ]);
        } elseif ($active_library === 'datastar') {
            $datastar_tab = $this->settings->add_tab(esc_html__('Datastar Settings', 'api-for-htmx'));
            $datastar_section = $datastar_tab->add_section(esc_html__('Datastar Settings', 'api-for-htmx'), [
                'description' => esc_html__('Datastar automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
            ]);

            $datastar_section->add_option('checkbox', [
                'name' => 'load_datastar_backend',
                'label' => esc_html__('Load Datastar in WP Admin', 'api-for-htmx'),
                'description' => esc_html__('Enable Datastar functionality within the WordPress admin area.', 'api-for-htmx'),
                'default' => $options['load_datastar_backend'] ?? false,
            ]);

            // Add Datastar SDK status section
            $sdk_section = $datastar_tab->add_section(esc_html__('Datastar PHP SDK Status', 'api-for-htmx'));

            $sdk_status = $this->datastar_manager->get_sdk_status($options);

            $sdk_section->add_option('display', [
                'name' => 'datastar_sdk_status',
                'html' => $sdk_status['html'],
            ]);
        }

        // --- About Tab (Always Visible) ---
        $about_tab = $this->settings->add_tab(esc_html__('About', 'api-for-htmx'));
        $about_section = $about_tab->add_section(esc_html__('About', 'api-for-htmx'), [
            'description' => esc_html__('Designed for developers, HyperPress brings the power and simplicity of hypermedia to your WordPress projects. It seamlessly integrates popular libraries like HTMX, Alpine AJAX, and Datastar, empowering you to create rich, dynamic user interfaces without the complexity of traditional JavaScript frameworks.', 'api-for-htmx') . '<br>' .
                esc_html__('Adds a new endpoint /wp-html/v1/ from which you can load any hypermedia template.', 'api-for-htmx') . '<br><br>' .
                esc_html__('At its core, hypermedia is an approach that empowers you to build modern, dynamic applications by extending the capabilities of HTML. Libraries like HTMX, Alpine AJAX, and Datastar allow you to harness advanced browser technologies—such as AJAX, WebSockets, and Server-Sent Events, simply by adding special attributes to your HTML, minimizing or eliminating the need for a complex JavaScript layer.', 'api-for-htmx') . '<br><br>' .
                esc_html__('Plugin repository and documentation:', 'api-for-htmx') . ' <a href="https://github.com/EstebanForge/HyperPress" target="_blank">https://github.com/EstebanForge/HyperPress</a>',
        ]);

        $system_info_section = $about_tab->add_section(esc_html__('System Information', 'api-for-htmx'), [
            'description' => esc_html__('General information about your WordPress installation and this plugin status.', 'api-for-htmx'),
        ]);

        $system_info_section->add_option('display', [
            'name' => 'system_information',
            'debug_data' => $this->get_system_information(),
        ]);

        $this->settings->add_option('display', [
            'name' => 'plugin_info',
            'html' => $this->get_plugin_info_html(),
        ]);

        $this->settings->make();
    }

    /**
     * Get system information for the debug table.
     *
     * @since 2.0.3
     * @return array
     */
    private function get_system_information(): array
    {
        $options = $this->main->get_options();

        $system_info = [
            'WordPress Version' => get_bloginfo('version'),
            'PHP Version'       => PHP_VERSION,
            'Plugin Version'    => HMAPI_VERSION,
            'Active Library'    => ucfirst($options['active_library'] ?? 'htmx'),
        ];

        if (($options['active_library'] ?? 'htmx') === 'datastar') {
            $sdk_status = $this->datastar_manager->get_sdk_status($options);
            $sdk_status_text = $sdk_status['loaded'] ?
                sprintf('Available (v%s)', esc_html($sdk_status['version'])) :
                esc_html__('Not Available', 'api-for-htmx');
            $system_info['Datastar SDK'] = $sdk_status_text;
        }

        return $system_info;
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
     * @since 2.0.0
     *
     * @param bool $detailed Whether to show detailed debug information.
     *
     * @return string The generated HTML for the plugin information display.
     */
    private function get_plugin_info_html(bool $detailed = false): string
    {
        $plugin_info_html = '<div class="hmapi-plugin-info-footer"><p>';

        // Get active instance information
        $main_instance = $this->main;
        if ($main_instance) {
            $reflection = new \ReflectionClass($main_instance);
            $real_instance_path = $reflection->getFileName();
            $real_plugin_dir = defined('WP_PLUGIN_DIR') ? wp_normalize_path(WP_PLUGIN_DIR) : '';

            if (hm_is_library_mode()) {
                $instance_type = esc_html__('Library', 'api-for-htmx');
            } else {
                $instance_type = esc_html__('Plugin', 'api-for-htmx');
            }

            $plugin_info_html .= '<strong>' . esc_html__('Active Instance:', 'api-for-htmx') . '</strong> ' .
                $instance_type . ' v' . esc_html(HMAPI_LOADED_VERSION) . '<br/>';

            // Add debug information if in detailed mode and WP_DEBUG is enabled
            if ($detailed && defined('WP_DEBUG') && WP_DEBUG) {
                $expected_plugin_path = '';
                $instance_basename = '';
                if ($real_instance_path && $real_plugin_dir) {
                    $real_instance_path_norm = wp_normalize_path($real_instance_path);
                    $expected_plugin_path = $real_plugin_dir . '/' . HMAPI_BASENAME;
                    $instance_basename = str_starts_with($real_instance_path_norm, $real_plugin_dir) ?
                        plugin_basename($real_instance_path) :
                        basename(dirname($real_instance_path)) . '/' . basename($real_instance_path);
                }

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
