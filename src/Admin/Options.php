<?php

declare(strict_types=1);

namespace HMApi\Admin;

use HMApi\Fields\Field;
use HMApi\Fields\OptionsPage;
use HMApi\Fields\OptionsSection;
use HMApi\Fields\TabsField;
use HMApi\Libraries\AlpineAjaxLib;
use HMApi\Libraries\DatastarLib;
use HMApi\Libraries\HTMXLib;
use HMApi\Main;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * New Options Class using Universal Field System.
 * Replaces wp-settings dependency with our universal field system.
 *
 * @since 2025-07-21
 */
class Options
{
    private string $option_name = 'hmapi_options';
    private Main $main;
    private DatastarLib $datastar_manager;
    private HTMXLib $htmx_manager;
    private AlpineAjaxLib $alpine_ajax_manager;

    public function __construct(Main $main)
    {
        $this->main = $main;
        $this->datastar_manager = new DatastarLib($this->main);
        $this->htmx_manager = new HTMXLib($this->main);
        $this->alpine_ajax_manager = new AlpineAjaxLib($this->main);

        if (!hm_is_library_mode()) {
            add_action('admin_menu', [$this, 'register_options_page']);
            add_action('admin_init', [$this, 'register_settings']);
            add_filter('plugin_action_links_' . HMAPI_BASENAME, [$this, 'plugin_action_links']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        }
    }

    public function register_options_page(): void
    {
        $options_page = OptionsPage::make('Hypermedia API Options', 'hypermedia-api-options')
            ->set_menu_title('Hypermedia API')
            ->set_parent_slug('options-general.php')
            ->set_capability('manage_options')
            ->set_icon_url('dashicons-admin-generic');

        $this->build_options_page($options_page);
    }

    private function build_options_page(OptionsPage $page): void
    {
        $current_options = $this->get_current_options();

        // Create tabs using our TabsField
        $tabs_field = TabsField::make('settings_tabs', 'Settings Tabs')
            ->set_layout('horizontal');

        // General Tab
        $general_tab = $this->build_general_tab($current_options);
        $tabs_field->add_tab('general', 'General Settings', [$general_tab]);

        // Library-specific tabs based on conditional logic
        $active_library = $current_options['active_library'] ?? 'htmx';

        if ($active_library === 'htmx') {
            $htmx_tab = $this->build_htmx_tab($current_options);
            $tabs_field->add_tab('htmx', 'HTMX Settings', [$htmx_tab]);
        } elseif ($active_library === 'alpinejs') {
            $alpine_tab = $this->build_alpine_tab($current_options);
            $tabs_field->add_tab('alpine', 'Alpine Ajax Settings', [$alpine_tab]);
        } elseif ($active_library === 'datastar') {
            $datastar_tab = $this->build_datastar_tab($current_options);
            $tabs_field->add_tab('datastar', 'Datastar Settings', [$datastar_tab]);
        }

        // About Tab
        $about_tab = $this->build_about_tab($current_options);
        $tabs_field->add_tab('about', 'About', [$about_tab]);

        $page->add_field($tabs_field);
    }

    private function build_general_tab(array $options): OptionsSection
    {
        $section = OptionsSection::make('general_settings', 'General Settings')
            ->set_description('Configure which hypermedia library to use and CDN loading preferences.');

        // API Endpoint URL (read-only)
        $api_url = home_url('/' . HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION . '/');
        $section->add_field(
            Field::make('html', 'api_url_display', 'Hypermedia API Endpoint')
                ->set_html(sprintf(
                    '<div class="hmapi-api-url">
                        <strong>%s</strong><br>
                        <code>%s</code><br>
                        <small>%s</small>
                    </div>',
                    esc_html__('API Endpoint URL:', 'api-for-htmx'),
                    esc_url($api_url),
                    esc_html__('Use this base URL to make requests to the hypermedia API endpoints from your frontend code.', 'api-for-htmx')
                ))
        );

        // Active Library Selection
        $section->add_field(
            Field::make('select', 'active_library', 'Active Hypermedia Library')
                ->set_options([
                    'htmx' => 'HTMX',
                    'alpinejs' => 'Alpine Ajax',
                    'datastar' => 'Datastar',
                ])
                ->set_default($options['active_library'] ?? 'htmx')
                ->set_description('Select the primary hypermedia library to activate and configure.')
        );

        // CDN Loading
        $section->add_field(
            Field::make('checkbox', 'load_from_cdn', 'Load active library from CDN')
                ->set_default($options['load_from_cdn'] ?? false)
                ->set_description('Load libraries from CDN for better performance, or disable to use local copies for version consistency.')
        );

        return $section;
    }

    private function build_htmx_tab(array $options): OptionsSection
    {
        $section = OptionsSection::make('htmx_settings', 'HTMX Settings')
            ->set_description('Configure HTMX-specific settings and features.');

        $section->add_field(
            Field::make('checkbox', 'load_hyperscript', 'Load Hyperscript with HTMX')
                ->set_default($options['load_hyperscript'] ?? true)
                ->set_description('Automatically load Hyperscript when HTMX is active.')
        );

        $section->add_field(
            Field::make('checkbox', 'load_alpinejs_with_htmx', 'Load Alpine.js with HTMX')
                ->set_default($options['load_alpinejs_with_htmx'] ?? false)
                ->set_description('Load Alpine.js alongside HTMX for enhanced interactivity.')
        );

        $section->add_field(
            Field::make('checkbox', 'set_htmx_hxboost', 'Enable hx-boost on body')
                ->set_default($options['set_htmx_hxboost'] ?? false)
                ->set_description('Automatically add `hx-boost="true"` to the `<body>` tag for progressive enhancement.')
        );

        $section->add_field(
            Field::make('checkbox', 'load_htmx_backend', 'Load HTMX in WP Admin')
                ->set_default($options['load_htmx_backend'] ?? false)
                ->set_description('Enable HTMX functionality within the WordPress admin area.')
        );

        // HTMX Extensions
        $extensions_section = OptionsSection::make('htmx_extensions', 'HTMX Extensions')
            ->set_description('Enable specific HTMX extensions for enhanced functionality.');

        $available_extensions = $this->get_htmx_extensions();
        foreach ($available_extensions as $key => $details) {
            $extensions_section->add_field(
                Field::make('checkbox', 'load_extension_' . $key, $details['label'])
                    ->set_default($options['load_extension_' . $key] ?? false)
                    ->set_description($details['description'])
            );
        }

        // Group extensions under a repeater for better organization
        $repeater = Field::make('repeater', 'htmx_extensions_group', 'HTMX Extensions')
            ->set_collapsible(true)
            ->set_collapsed(true);

        foreach ($available_extensions as $key => $details) {
            $repeater->add_sub_field(
                Field::make('checkbox', $key, $details['label'])
                    ->set_description($details['description'])
            );
        }

        return $section;
    }

    private function build_alpine_tab(array $options): OptionsSection
    {
        $section = OptionsSection::make('alpine_settings', 'Alpine Ajax Settings')
            ->set_description('Alpine.js automatically loads when selected as the active library. Configure backend loading below.');

        $section->add_field(
            Field::make('checkbox', 'load_alpinejs_backend', 'Load Alpine Ajax in WP Admin')
                ->set_default($options['load_alpinejs_backend'] ?? false)
                ->set_description('Enable Alpine Ajax functionality within the WordPress admin area.')
        );

        return $section;
    }

    private function build_datastar_tab(array $options): OptionsSection
    {
        $section = OptionsSection::make('datastar_settings', 'Datastar Settings')
            ->set_description('Datastar automatically loads when selected as the active library. Configure backend loading below.');

        $section->add_field(
            Field::make('checkbox', 'load_datastar_backend', 'Load Datastar in WP Admin')
                ->set_default($options['load_datastar_backend'] ?? false)
                ->set_description('Enable Datastar functionality within the WordPress admin area.')
        );

        // Datastar SDK Status
        $sdk_status = $this->datastar_manager->get_sdk_status($options);
        $section->add_field(
            Field::make('html', 'datastar_sdk_status', 'Datastar PHP SDK Status')
                ->set_html($sdk_status['html'])
        );

        return $section;
    }

    private function build_about_tab(array $options): OptionsSection
    {
        $section = OptionsSection::make('about_info', 'About')
            ->set_description('Information about the Hypermedia API plugin.');

        $section->add_field(
            Field::make('html', 'about_content', 'About Hypermedia API')
                ->set_html(sprintf(
                    '<div class="hmapi-about-content">
                        <p>%s</p>
                        <p>%s</p>
                        <p>%s</p>
                        <p>%s <a href="%s" target="_blank">%s</a></p>
                    </div>',
                    esc_html__('Hypermedia API for WordPress is an unofficial plugin that enables the use of HTMX, Alpine AJAX, Datastar, and other hypermedia libraries on your WordPress site, theme, and/or plugins. Intended for software developers.', 'api-for-htmx'),
                    esc_html__('Adds a new endpoint /wp-html/v1/ from which you can load any hypermedia template.', 'api-for-htmx'),
                    esc_html__('Hypermedia is a concept that allows you to build modern web applications, even SPAs, without writing JavaScript. HTMX, Alpine Ajax, and Datastar let you use AJAX, WebSockets, and Server-Sent Events directly in HTML using attributes.', 'api-for-htmx'),
                    esc_html__('Plugin repository and documentation:', 'api-for-htmx'),
                    'https://github.com/EstebanForge/Hypermedia-API-WordPress',
                    'https://github.com/EstebanForge/Hypermedia-API-WordPress'
                ))
        );

        $system_info = $this->get_system_information();
        $section->add_field(
            Field::make('html', 'system_information', 'System Information')
                ->set_html($this->render_system_info($system_info))
        );

        return $section;
    }

    public function register_settings(): void
    {
        register_setting('hmapi_options', $this->option_name, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_options'],
            'default' => $this->get_default_options(),
        ]);
    }

    public function sanitize_options(array $input): array
    {
        $sanitized = [];

        // Sanitize each field based on type
        $sanitized['active_library'] = sanitize_text_field($input['active_library'] ?? 'htmx');
        $sanitized['load_from_cdn'] = isset($input['load_from_cdn']) ? (bool) $input['load_from_cdn'] : false;
        $sanitized['load_hyperscript'] = isset($input['load_hyperscript']) ? (bool) $input['load_hyperscript'] : false;
        $sanitized['load_alpinejs_with_htmx'] = isset($input['load_alpinejs_with_htmx']) ? (bool) $input['load_alpinejs_with_htmx'] : false;
        $sanitized['set_htmx_hxboost'] = isset($input['set_htmx_hxboost']) ? (bool) $input['set_htmx_hxboost'] : false;
        $sanitized['load_htmx_backend'] = isset($input['load_htmx_backend']) ? (bool) $input['load_htmx_backend'] : false;
        $sanitized['load_alpinejs_backend'] = isset($input['load_alpinejs_backend']) ? (bool) $input['load_alpinejs_backend'] : false;
        $sanitized['load_datastar_backend'] = isset($input['load_datastar_backend']) ? (bool) $input['load_datastar_backend'] : false;

        // Sanitize HTMX extensions
        $extensions = $this->get_htmx_extensions();
        foreach ($extensions as $key => $details) {
            $sanitized['load_extension_' . $key] = isset($input['load_extension_' . $key]) ? (bool) $input['load_extension_' . $key] : false;
        }

        return $sanitized;
    }

    private function get_default_options(): array
    {
        return [
            'active_library' => 'htmx',
            'load_from_cdn' => false,
            'load_hyperscript' => true,
            'load_alpinejs_with_htmx' => false,
            'set_htmx_hxboost' => false,
            'load_htmx_backend' => false,
            'load_alpinejs_backend' => false,
            'load_datastar_backend' => false,
            'load_extension_sse' => false,
            'load_extension_head-support' => false,
            'load_extension_response-targets' => false,
            'load_extension_loading-states' => false,
            'load_extension_debug' => false,
            'load_extension_path-deps' => false,
            'load_extension_class-tools' => false,
            'load_extension_multi-swap' => false,
            'load_extension_includes' => false,
            'load_extension_json-enc' => false,
            'load_extension_method-override' => false,
            'load_extension_morphdom-swap' => false,
            'load_extension_client-side-templates' => false,
            'load_extension_preload' => false,
        ];
    }

    private function get_current_options(): array
    {
        $options = get_option($this->option_name, []);

        return array_merge($this->get_default_options(), $options);
    }

    private function get_htmx_extensions(): array
    {
        return $this->htmx_manager::get_extensions($this->main);
    }

    private function render_system_info(array $system_info): string
    {
        $html = '<div class="hmapi-system-info"><table class="widefat">';
        $html .= '<thead><tr><th>Setting</th><th>Value</th></tr></thead><tbody>';

        foreach ($system_info as $key => $value) {
            $html .= sprintf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                esc_html($key),
                esc_html($value)
            );
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function get_system_information(): array
    {
        global $wp_version;
        
        return [
            'WordPress Version' => $wp_version,
            'PHP Version' => PHP_VERSION,
            'Plugin Version' => HMAPI_VERSION,
            'Active Library' => get_option('hmapi_options')['active_library'] ?? 'htmx',
            'REST API Base' => home_url('/' . HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION . '/'),
            'Library Mode' => hm_is_library_mode() ? 'Yes' : 'No',
            'CDN Loading' => get_option('hmapi_options')['load_from_cdn'] ?? false ? 'Enabled' : 'Disabled',
        ];
    }

    public function plugin_action_links(array $links): array
    {
        $links[] = '<a href="' . esc_url(admin_url('options-general.php?page=hypermedia-api-options')) . '">' . esc_html__('Settings', 'api-for-htmx') . '</a>';

        return $links;
    }

    public function enqueue_admin_scripts(string $hook_suffix): void
    {
        if ($hook_suffix === 'settings_page_hypermedia-api-options') {
            wp_enqueue_script(
                'hmapi-admin-options',
                plugin_dir_url(__FILE__) . 'assets/js/admin-options.js',
                ['jquery'],
                HMAPI_VERSION,
                true
            );

            wp_localize_script('hmapi-admin-options', 'hmapiOptions', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hmapi_options'),
            ]);
        }
    }
}
