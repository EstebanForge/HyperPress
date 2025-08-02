<?php

declare(strict_types=1);

namespace HMApi\Admin;

use HMApi\Fields\HyperFields;
use HMApi\Libraries\AlpineAjaxLib;
use HMApi\Libraries\DatastarLib;
use HMApi\Libraries\HTMXLib;
use HMApi\Main;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * New Options Class using Hyper Fields System.
 * Replaces wp-settings dependency with our Hyper fields system.
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
        $this->datastar_manager = new DatastarLib($main);
        $this->htmx_manager = new HTMXLib($main);
        $this->alpine_ajax_manager = new AlpineAjaxLib($main);

        // Initialize the options page using HyperFields system
        add_action('init', [$this, 'init_options_page']);
    }

    public function init_options_page(): void
    {
        $current_options = $this->get_current_options();

        // Create the options page
        $options_page = \HMApi\Fields\HyperFields::makeOptionPage('Hypermedia API Options', 'hypermedia-api-options')
            ->set_menu_title('Hypermedia API')
            ->set_parent_slug('options-general.php')
            ->set_capability('manage_options');

        // Build and add sections
        $general_sections = $this->build_general_tab($current_options);
        $htmx_sections = $this->build_htmx_tab($current_options);
        $alpine_sections = $this->build_alpine_tab($current_options);
        $datastar_sections = $this->build_datastar_tab($current_options);
        $about_sections = $this->build_about_tab($current_options);

        // Add all sections to the options page
        $all_sections = array_merge($general_sections, $htmx_sections, $alpine_sections, $datastar_sections, $about_sections);

        foreach ($all_sections as $section) {
            if ($section instanceof \HMApi\Fields\OptionsSection) {
                // Add the section directly to the options page
                $section_obj = $options_page->add_section($section->get_id(), $section->get_title(), $section->get_description());

                // Add all fields from the section
                foreach ($section->get_fields() as $field) {
                    $section_obj->add_field($field);
                }
            }
        }

        // Register the options page
        $options_page->register();
    }

    private function build_general_tab(array $options)
    {
        $section = HyperFields::makeSection('general_settings', 'General Settings')
            ->set_description('Configure which hypermedia library to use and CDN loading preferences.');

        // API Endpoint URL (read-only)
        $api_url = home_url('/' . HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION . '/');
        $section->add_field(
            HyperFields::makeField('html', 'api_url_display', 'Hypermedia API Endpoint')
                ->set_html(sprintf(
                    '<div class="field hmapi-api-url">
    <div class="api-url-header">
        <strong>%s</strong>
        <button type="button" class="button button-secondary copy-api-url" data-clipboard-text="%s">%s</button>
    </div>
    <div class="api-url-container">
        <code class="api-url-code">%s</code>
    </div>
    <p class="description">%s</p>
</div>',
                    esc_html__('API Endpoint URL:', 'api-for-htmx'),
                    esc_attr($api_url),
                    esc_html__('Copy URL', 'api-for-htmx'),
                    esc_url($api_url),
                    esc_html__('Use this base URL to make requests to the hypermedia API endpoints from your frontend code.', 'api-for-htmx')
                ))
        );

        // Active Library Selection
        $section->add_field(
            HyperFields::makeField('select', 'active_library', 'Active Hypermedia Library')
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
            HyperFields::makeField('checkbox', 'load_from_cdn', 'Load active library from CDN')
                ->set_default($options['load_from_cdn'] ?? false)
                ->set_description('Load libraries from CDN for better performance, or disable to use local copies for version consistency.')
        );

        return [$section];
    }

    private function build_htmx_tab(array $options)
    {
        $section = HyperFields::makeSection('htmx_settings', 'HTMX Settings')
            ->set_description('Configure HTMX-specific settings and features.');

        $section->add_field(
            HyperFields::makeField('checkbox', 'load_hyperscript', 'Load Hyperscript with HTMX')
                ->set_default($options['load_hyperscript'] ?? true)
                ->set_description('Automatically load Hyperscript when HTMX is active.')
        );

        $section->add_field(
            HyperFields::makeField('checkbox', 'load_alpinejs_with_htmx', 'Load Alpine.js with HTMX')
                ->set_default($options['load_alpinejs_with_htmx'] ?? false)
                ->set_description('Load Alpine.js alongside HTMX for enhanced interactivity.')
        );

        $section->add_field(
            HyperFields::makeField('checkbox', 'set_htmx_hxboost', 'Enable hx-boost on body')
                ->set_default($options['set_htmx_hxboost'] ?? false)
                ->set_description('Automatically add `hx-boost="true"` to the `<body>` tag for progressive enhancement.')
        );

        $section->add_field(
            HyperFields::makeField('checkbox', 'load_htmx_backend', 'Load HTMX in WP Admin')
                ->set_default($options['load_htmx_backend'] ?? false)
                ->set_description('Enable HTMX functionality within the WordPress admin area.')
        );

        // HTMX Extensions
        $extensions_section = HyperFields::makeSection('htmx_extensions', 'HTMX Extensions')
            ->set_description('Enable specific HTMX extensions for enhanced functionality.');

        $available_extensions = $this->get_htmx_extensions();
        foreach ($available_extensions as $key => $details) {
            $extensions_section->add_field(
                HyperFields::makeField('checkbox', 'load_extension_' . $key, $details['label'])
                    ->set_default($options['load_extension_' . $key] ?? false)
                    ->set_description($details['description'])
            );
        }

        return [$section, $extensions_section];
    }

    private function build_alpine_tab(array $options)
    {
        $section = HyperFields::makeSection('alpine_settings', 'Alpine Ajax Settings')
            ->set_description('Configure Alpine Ajax-specific settings.');

        $section->add_field(
            HyperFields::makeField('checkbox', 'load_alpinejs_backend', 'Load Alpine.js in WP Admin')
                ->set_default($options['load_alpinejs_backend'] ?? false)
                ->set_description('Enable Alpine.js functionality within the WordPress admin area.')
        );

        return [$section];
    }

    private function build_datastar_tab(array $options)
    {
        $section = HyperFields::makeSection('datastar_settings', 'Datastar Settings')
            ->set_description('Datastar automatically loads when selected as the active library. Configure backend loading below.');

        $section->add_field(
            HyperFields::makeField('checkbox', 'load_datastar_backend', 'Load Datastar in WP Admin')
                ->set_default($options['load_datastar_backend'] ?? false)
                ->set_description('Enable Datastar functionality within the WordPress admin area.')
        );

        // Datastar SDK Status
        $sdk_status = $this->datastar_manager->get_sdk_status($options);
        $section->add_field(
            HyperFields::makeField('html', 'datastar_sdk_status', 'Datastar PHP SDK Status')
                ->set_html($sdk_status['html'])
        );

        return [$section];
    }

    private function build_about_tab(array $options)
    {
        $section = HyperFields::makeSection('about_info', 'About')
            ->set_description('Information about the Hypermedia API plugin.');

        $section->add_field(
            HyperFields::makeField('html', 'about_content', 'About Hypermedia API')
                ->set_html(sprintf(
                    '<div class="hmapi-about-content">
                        <p>%s</p>
                        <p>%s</p>
                        <p>%s</p>
                        <h3>%s</h3>
                        <ul>
                            <li><strong>%s</strong> - %s</li>
                            <li><strong>%s</strong> - %s</li>
                        </ul>
                        <p><a href="https://htmx.org" target="_blank" rel="noopener">%s</a> |
                        <a href="https://alpinejs.dev" target="_blank" rel="noopener">%s</a> |
                        <a href="https://data-star.org" target="_blank" rel="noopener">%s</a></p>
                    </div>',
                    esc_html__('The Hypermedia API plugin provides a modern, declarative approach to building interactive WordPress sites using hypermedia principles.', 'api-for-htmx'),
                    esc_html__('It supports multiple libraries including HTMX, Alpine Ajax, and Datastar, allowing you to choose the best tool for your project.', 'api-for-htmx'),
                    esc_html__('All libraries are automatically loaded with proper versioning and CDN support.', 'api-for-htmx'),
                    esc_html__('Key Features:', 'api-for-htmx'),
                    esc_html__('REST API Endpoints', 'api-for-htmx'),
                    esc_html__('Pre-built endpoints for common WordPress data', 'api-for-htmx'),
                    esc_html__('Frontend Libraries', 'api-for-htmx'),
                    esc_html__('Automatic loading with CDN support', 'api-for-htmx'),
                    esc_html__('HTMX Documentation', 'api-for-htmx'),
                    esc_html__('Alpine.js Documentation', 'api-for-htmx'),
                    esc_html__('Datastar Documentation', 'api-for-htmx')
                ))
        );

        $system_info = $this->get_system_information();
        $section2 = HyperFields::makeSection('system_info', 'System Information')
            ->set_description('Technical details about your WordPress installation and plugin configuration.');

        $section2->add_field(
            HyperFields::makeField('html', 'system_information', 'System Information')
                ->set_html($this->render_system_info($system_info))
        );

        $section = HyperFields::makeSection('about_info', 'About')
            ->set_description('Information about the Hypermedia API plugin.');

        $section->add_field(
            HyperFields::makeField('html', 'about_content', 'About Hypermedia API')
                ->set_html(sprintf(
                    '<div class="hmapi-about-content">
                    <p>%s</p>
                    <p>%s</p>
                    <p>%s</p>
                    <h3>%s</h3>
                    <ul>
                        <li><strong>%s</strong> - %s</li>
                        <li><strong>%s</strong> - %s</li>
                    </ul>
                    <p><a href="https://htmx.org" target="_blank" rel="noopener">%s</a> |
                    <a href="https://alpinejs.dev" target="_blank" rel="noopener">%s</a> |
                    <a href="https://data-star.org" target="_blank" rel="noopener">%s</a></p>
                </div>',
                    esc_html__('The Hypermedia API plugin provides a modern, declarative approach to building interactive WordPress sites using hypermedia principles.', 'api-for-htmx'),
                    esc_html__('It supports multiple libraries including HTMX, Alpine Ajax, and Datastar, allowing you to choose the best tool for your project.', 'api-for-htmx'),
                    esc_html__('All libraries are automatically loaded with proper versioning and CDN support.', 'api-for-htmx'),
                    esc_html__('Key Features:', 'api-for-htmx'),
                    esc_html__('REST API Endpoints', 'api-for-htmx'),
                    esc_html__('Pre-built endpoints for common WordPress data', 'api-for-htmx'),
                    esc_html__('Frontend Libraries', 'api-for-htmx'),
                    esc_html__('Automatic loading with CDN support', 'api-for-htmx'),
                    esc_html__('HTMX Documentation', 'api-for-htmx'),
                    esc_html__('Alpine.js Documentation', 'api-for-htmx'),
                    esc_html__('Datastar Documentation', 'api-for-htmx')
                ))
        );

        $system_info = $this->get_system_information();
        $section2 = HyperFields::makeSection('system_info', 'System Information')
            ->set_description('Technical details about your WordPress installation and plugin configuration.');

        $section2->add_field(
            HyperFields::makeField('html', 'system_information', 'System Information')
                ->set_html($this->render_system_info($system_info))
        );

        return [$section, $section2];
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
            wp_enqueue_style(
                'hmapi-admin-options',
                plugin_dir_url(__FILE__) . 'assets/css/admin-options.css',
                [],
                HMAPI_VERSION
            );

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
