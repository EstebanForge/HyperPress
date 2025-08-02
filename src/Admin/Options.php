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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function init_options_page(): void
    {
        $current_options = $this->get_current_options();
        $active_library = $current_options['active_library'] ?? 'htmx';

        // Create the options page
        $options_page = HyperFields::makeOptionPage('HyperPress Options', 'hyperpress-options')
            ->set_menu_title('HyperPress')
            ->set_parent_slug('options-general.php')
            ->set_capability('manage_options');

        // Add General tab
        $general_sections = $this->build_general_tab($current_options);
        foreach ($general_sections as $section) {
            if ($section instanceof \HMApi\Fields\OptionsSection) {
                $options_page->add_section_object($section);
            }
        }

        // Add library-specific tabs based on active library
        switch ($active_library) {
            case 'htmx':
                // Add HTMX tab
                $htmx_sections = $this->build_htmx_tab($current_options);
                foreach ($htmx_sections as $section) {
                    if ($section instanceof \HMApi\Fields\OptionsSection) {
                        $options_page->add_section_object($section);
                    }
                }
                break;

            case 'alpine-ajax':
                // Add Alpine tab
                $alpine_sections = $this->build_alpine_tab($current_options);
                foreach ($alpine_sections as $section) {
                    if ($section instanceof \HMApi\Fields\OptionsSection) {
                        $options_page->add_section_object($section);
                    }
                }
                break;

            case 'datastar':
                // Add Datastar tab
                $datastar_sections = $this->build_datastar_tab($current_options);
                foreach ($datastar_sections as $section) {
                    if ($section instanceof \HMApi\Fields\OptionsSection) {
                        $options_page->add_section_object($section);
                    }
                }
                break;
        }

        // Add About tab
        $about_sections = $this->build_about_tab($current_options);
        foreach ($about_sections as $section) {
            if ($section instanceof \HMApi\Fields\OptionsSection) {
                $options_page->add_section_object($section);
            }
        }

        // Register the options page
        $options_page->register();
    }

    private function build_general_tab(array $options): array
    {
        $section = HyperFields::makeSection('general_settings', 'General Settings')
            ->set_description('Configure the general settings for the HyperPress plugin.');

        $fields = [
            HyperFields::makeField('select', 'active_library', 'Active Library')
                ->set_options([
                    'htmx' => 'HTMX',
                'alpine-ajax' => 'Alpine Ajax',
                    'datastar' => 'Datastar',
                ])
                ->set_default($options['active_library'] ?? 'htmx')
                ->set_description('Select the primary hypermedia library to use on the front end.'),
            HyperFields::makeField('checkbox', 'load_from_cdn', 'Load from CDN')
                ->set_default($options['load_from_cdn'] ?? false)
                ->set_description('Load library scripts from a CDN instead of serving them locally.'),
        ];

        foreach ($fields as $field) {
            $section->add_field($field);
        }

        return [$section];
    }

    private function build_htmx_tab(array $options): array
    {
        $section = HyperFields::makeSection('htmx_settings', 'HTMX Settings');

        $htmx_fields = [
            HyperFields::makeField('checkbox', 'load_hyperscript', 'Load Hyperscript')
                ->set_default($options['load_hyperscript'] ?? false)
                ->set_description('Load Hyperscript library for advanced scripting with HTMX.'),
            HyperFields::makeField('checkbox', 'load_alpine_with_htmx', 'Load Alpine.js with HTMX')
                ->set_default($options['load_alpine_with_htmx'] ?? false)
                ->set_description('Load Alpine.js for reactive components alongside HTMX.'),
            HyperFields::makeField('checkbox', 'hx_boost', 'Enable hx-boost on body')
                ->set_default($options['hx_boost'] ?? false)
                ->set_description('Apply hx-boost to the body tag to enable SPA-like navigation.'),
            HyperFields::makeField('checkbox', 'load_htmx_backend', 'Load HTMX in WP Admin')
                ->set_default($options['load_htmx_backend'] ?? false)
                ->set_description('Enable HTMX functionality within the WordPress admin area.'),
            HyperFields::makeSeparator('htmx_extensions_separator'),
            HyperFields::makeHeading('htmx_extensions_heading', 'Extensions'),
        ];

        foreach ($htmx_fields as $field) {
            $section->add_field($field);
        }

        $extensions = $this->get_htmx_extensions();
        foreach ($extensions as $extension_key => $extension_details) {
            // Skip if extension_key is not a string or is empty
            if (!is_string($extension_key) || empty($extension_key)) {
                continue;
            }

            $field_name = 'load_extension_' . str_replace('-', '_', $extension_key);
            $field = HyperFields::makeField('checkbox', $field_name, $extension_details['label'])
                ->set_default($options[$field_name] ?? false)
                ->set_description($extension_details['description']);
            $section->add_field($field);
        }

        return [$section];
    }

    private function build_alpine_tab(array $options): array
    {
        $section = HyperFields::makeSection('alpine_settings', 'Alpine Ajax Settings');

        $fields = [
            HyperFields::makeField('checkbox', 'load_alpine_ajax_backend', 'Load Alpine Ajax in WP Admin')
                ->set_default($options['load_alpine_ajax_backend'] ?? false)
                ->set_description('Load Alpine Ajax in the WordPress admin area.'),
        ];

        foreach ($fields as $field) {
            $section->add_field($field);
        }

        return [$section];
    }

    private function build_datastar_tab(array $options): array
    {
        $section = HyperFields::makeSection('datastar_settings', 'Datastar Settings')
            ->set_description('Configure settings for Datastar, a library that provides two-way data binding for Alpine.js and HTMX.');

        $fields = [
            HyperFields::makeField('checkbox', 'load_datastar_backend', 'Load Datastar in WP Admin')
                ->set_default($options['load_datastar_backend'] ?? false)
                ->set_description('Enable Datastar functionality within the WordPress admin area.'),
        ];

        foreach ($fields as $field) {
            $section->add_field($field);
        }

        return [$section];
    }

    private function build_about_tab(array $options): array
    {
        $section = HyperFields::makeSection('about_info', 'About')
            ->set_description('Information about the HyperPress plugin.');

        $section->add_field(
            HyperFields::makeField('html', 'about_content', 'About HyperPress')
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
                    esc_html__('The HyperPress plugin provides a modern, declarative approach to building interactive WordPress sites using hypermedia principles.', 'api-for-htmx'),
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
        $system_info_html = $this->render_system_info($system_info);

        $section->add_field(
            HyperFields::makeField('html', 'system_info')
                ->set_html($system_info_html)
        );

        return [$section];
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
        $extensions = $this->htmx_manager::get_extensions($this->main);

        // Debug log to see what's being returned
        return $extensions;
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
            // Enqueue admin options CSS and JS
            wp_enqueue_style(
                'hmapi-admin-options',
                HMAPI_PLUGIN_URL . 'assets/css/admin-options.css',
                [],
                HMAPI_VERSION
            );

            wp_enqueue_script(
                'hmapi-admin-options',
                HMAPI_PLUGIN_URL . 'assets/js/admin-options.js',
                ['jquery'],
                HMAPI_VERSION,
                true
            );

            // Enqueue fields CSS and JS for tabs functionality
            wp_enqueue_style(
                'hmapi-fields',
                HMAPI_PLUGIN_URL . 'assets/css/fields.css',
                [],
                HMAPI_VERSION
            );

            wp_enqueue_script(
                'hmapi-fields',
                HMAPI_PLUGIN_URL . 'assets/js/fields.js',
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
