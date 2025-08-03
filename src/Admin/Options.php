<?php

declare(strict_types=1);

namespace HMApi\Admin;

use HMApi\Fields\HyperFields;
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

    public function __construct(Main $main)
    {
        $this->main = $main;

        // Initialize the options page using HyperFields system
        add_action('init', [$this, 'init_options_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function init_options_page(): void
    {
        $options = HyperFields::getOptions($this->option_name, []);

        $all_sections = array_merge(
            $this->build_general_tab_config(),
            $this->build_htmx_tab_config(),
            $this->build_alpine_tab_config(),
            $this->build_datastar_tab_config(),
            $this->build_about_tab_config()
        );

        // PHP-side tab conditionality: filter by visible_if
        $sections = [];
        foreach ($all_sections as $section) {
            if (!isset($section['visible_if'])) {
                $sections[] = $section;
                continue;
            }
            $field = $section['visible_if']['field'] ?? null;
            $value = $section['visible_if']['value'] ?? null;
            if ($field && isset($options[$field]) && $options[$field] === $value) {
                $sections[] = $section;
            }
        }

        HyperFields::registerOptionsPage([
            'title' => 'HyperPress Options',
            'slug' => 'hyperpress-options',
            'menu_title' => 'HyperPress',
            'parent_slug' => 'options-general.php',
            'capability' => 'manage_options',
            'option_name' => $this->option_name,
            'sections' => $sections,
        ]);
    }

    private function build_general_tab_config(): array
    {
        return [
            [
                'id' => 'general_settings',
                'title' => 'General Settings',
                'description' => 'Configure the general settings for the HyperPress plugin.',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'active_library',
                        'label' => 'Active Library',
                        'options' => [
                            'htmx' => 'HTMX',
                            'alpine-ajax' => 'Alpine Ajax',
                            'datastar' => 'Datastar',
                        ],
                        'default' => 'htmx',
                        'help' => 'Select the primary hypermedia library to use.',
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'load_from_cdn',
                        'label' => 'Load from CDN',
                        'default' => false,
                        'help' => 'Load libraries from a CDN instead of the local copies.',
                    ],
                ],
            ],
        ];
    }

    private function build_htmx_tab_config(): array
    {
        return [
            [
                'id' => 'htmx_settings',
                'title' => 'HTMX Settings',
                'visible_if' => [ 'field' => 'active_library', 'value' => 'htmx' ],
                'description' => 'Configure HTMX specific settings.',
                'fields' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'load_hyperscript',
                        'label' => 'Load Hyperscript',
                        'default' => false,
                        'description' => 'Load Hyperscript library for advanced scripting with HTMX.',
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'load_alpine_with_htmx',
                        'label' => 'Load Alpine.js with HTMX',
                        'default' => false,
                        'description' => 'Load Alpine.js for reactive components alongside HTMX.',
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'hx_boost',
                        'label' => 'Enable hx-boost on body',
                        'default' => false,
                        'description' => 'Enable hx-boost on the body tag to make all links and forms use AJAX.',
                    ],
                ],
            ],
        ];
    }

    private function build_alpine_tab_config(): array
    {
        return [
            [
                'id' => 'alpine_settings',
                'title' => 'Alpine.js Settings',
                'visible_if' => [ 'field' => 'active_library', 'value' => 'alpine-ajax' ],
                'description' => 'Configure Alpine.js specific settings.',
                'fields' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'load_alpinejs_backend',
                        'label' => 'Load Alpine.js in Backend',
                        'default' => false,
                        'description' => 'Load Alpine.js in the WordPress admin area.',
                    ],
                ],
            ],
        ];
    }

    private function build_datastar_tab_config(): array
    {
        return [
            [
                'id' => 'datastar_settings',
                'title' => 'Datastar Settings',
                'visible_if' => [ 'field' => 'active_library', 'value' => 'datastar' ],
                'description' => 'Configure Datastar specific settings.',
                'fields' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'load_datastar_backend',
                        'label' => 'Load Datastar in Backend',
                        'default' => false,
                        'description' => 'Load Datastar in the WordPress admin area.',
                    ],
                ],
            ],
        ];
    }

    private function build_about_tab_config(): array
    {
        return [
            [
                'id' => 'about',
                'title' => 'About',
                'description' => 'Information about the HyperPress plugin.',
                'fields' => [
                    [
                        'type' => 'html',
                        'name' => 'plugin_info',
                        'label' => 'Plugin Information',
                        'default' => '<p><strong>HyperPress</strong> is a plugin that brings the power of hypermedia to WordPress.</p>',
                    ],
                    [
                        'type' => 'html',
                        'name' => 'system_info',
                        'label' => 'System Information',
                        'default' => $this->render_system_info($this->get_system_information()),
                    ],
                ],
            ],
        ];
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

        $options = HyperFields::getOptions($this->option_name);

        return [
            'WordPress Version' => $wp_version,
            'PHP Version' => PHP_VERSION,
            'Plugin Version' => HMAPI_VERSION,
            'Active Library' => $options['active_library'] ?? 'htmx',
            'REST API Base' => home_url('/' . HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION . '/'),
            'Library Mode' => hm_is_library_mode() ? 'Yes' : 'No',
            'CDN Loading' => !empty($options['load_from_cdn']) ? 'Enabled' : 'Disabled',
        ];
    }

    public function plugin_action_links(array $links): array
    {
        $links[] = '<a href="' . esc_url(admin_url('options-general.php?page=hypermedia-api-options')) . '">' . esc_html__('Settings', 'api-for-htmx') . '</a>';

        return $links;
    }

    public function enqueue_admin_scripts(string $hook_suffix): void
    {
        if ($hook_suffix === 'settings_page_hyperpress-options') {
            // Enqueue admin options JS
            wp_enqueue_script(
                'hmapi-admin-options',
                HMAPI_PLUGIN_URL . 'assets/js/admin-options.js',
                ['jquery'],
                HMAPI_VERSION,
                true
            );

            // Enqueue fields CSS for tabs functionality
            wp_enqueue_style(
                'hmapi-fields',
                HMAPI_PLUGIN_URL . 'assets/css/fields.css',
                [],
                HMAPI_VERSION
            );

            wp_localize_script('hmapi-admin-options', 'hmapiOptions', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hmapi_options'),
            ]);
        }
    }
}
