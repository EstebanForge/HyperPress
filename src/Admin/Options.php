<?php

declare(strict_types=1);

namespace HyperPress\Admin;

use HyperPress\Fields\HyperFields;
use HyperPress\Libraries\HTMXLib;
use HyperPress\Main;

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
    private string $option_name = 'hyperpress_options';
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;

        add_action('init', $this->initOptionsPage(...));
    }

    public function initOptionsPage(): void
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
            'menu_title' => 'api-for-htmx',
            'parent_slug' => 'options-general.php',
            'capability' => 'manage_options',
            'option_name' => $this->option_name,
            'sections' => $sections,
            'footer_content' => $this->get_footer_content(),
        ]);
    }

    private function build_general_tab_config(): array
    {
        return [
            [
                'id' => 'general_settings',
                'title' => __('General Settings', 'api-for-htmx'),
                'description' => __('Configure the general settings for the HyperPress plugin.', 'api-for-htmx'),
                'fields' => [
                    [
                        'type' => 'html',
                        'name' => 'api_endpoint',
                        'label' => '',
                        'html_content' => $this->render_api_endpoint_html(),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'active_library',
                        'label' => __('Active Library', 'api-for-htmx'),
                        'options' => [
                            'htmx' => 'HTMX',
                            'alpine-ajax' => 'Alpine Ajax',
                            'datastar' => 'Datastar',
                        ],
                        'default' => 'htmx',
                        'help' => __('Select the primary hypermedia library to use.', 'api-for-htmx'),
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'load_from_cdn',
                        'label' => __('Load from CDN', 'api-for-htmx'),
                        'default' => false,
                        'help' => __('Load libraries from a CDN instead of the local copies.', 'api-for-htmx'),
                    ],
                ],
            ],
        ];
    }

    private function build_htmx_tab_config(): array
    {
        $available_extensions = HTMXLib::get_extensions($this->main);

        $fields = [
            [
                'type' => 'checkbox',
                'name' => 'load_hyperscript',
                'label' => __('Load Hyperscript with HTMX', 'api-for-htmx'),
                'default' => true,
                'help' => __('Automatically load Hyperscript when HTMX is active.', 'api-for-htmx'),
            ],
            [
                'type' => 'checkbox',
                'name' => 'load_alpinejs_with_htmx',
                'label' => __('Load Alpine.js with HTMX', 'api-for-htmx'),
                'default' => false,
                'help' => __('Load Alpine.js alongside HTMX for enhanced interactivity.', 'api-for-htmx'),
            ],
            [
                'type' => 'checkbox',
                'name' => 'set_htmx_hxboost',
                'label' => __('Enable hx-boost on body', 'api-for-htmx'),
                'default' => false,
                'help' => __('Automatically add `hx-boost="true"` to the `<body>` tag for progressive enhancement.', 'api-for-htmx'),
            ],
            [
                'type' => 'checkbox',
                'name' => 'load_htmx_backend',
                'label' => __('Load HTMX in WP Admin', 'api-for-htmx'),
                'default' => false,
                'help' => __('Enable HTMX functionality within the WordPress admin area.', 'api-for-htmx'),
            ],
            [
                'type' => 'separator',
                'name' => 'htmx_ext_separator',
            ],
            [
                'type' => 'html',
                'name' => 'htmx_ext_heading',
                'html_content' => '<h2 style="margin-top:1.5em">' . esc_html__('HTMX Extensions', 'api-for-htmx') . '</h2><p>' . esc_html__('Enable specific HTMX extensions for enhanced functionality.', 'api-for-htmx') . '</p>',
            ],
        ];

        foreach ($available_extensions as $extension_key => $extension_details) {
            $fields[] = [
                'type' => 'checkbox',
                'name' => 'load_extension_' . str_replace('-', '_', $extension_key),
                'label' => esc_html($extension_details['label']),
                'default' => false,
                'help' => esc_html($extension_details['description']),
            ];
        }

        return [
            [
                'id' => 'htmx_settings',
                'title' => __('HTMX Settings', 'api-for-htmx'),
                'visible_if' => ['field' => 'active_library', 'value' => 'htmx'],
                'description' => __('Configure HTMX-specific settings and features.', 'api-for-htmx'),
                'fields' => $fields,
            ],
        ];
    }

    private function build_alpine_tab_config(): array
    {
        return [
            [
                'id' => 'alpine_settings',
                'title' => __('Alpine Ajax Settings', 'api-for-htmx'),
                'visible_if' => ['field' => 'active_library', 'value' => 'alpine-ajax'],
                'description' => __('Alpine.js automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
                'fields' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'load_alpinejs_backend',
                        'label' => __('Load Alpine Ajax in WP Admin', 'api-for-htmx'),
                        'default' => false,
                        'help' => __('Enable Alpine Ajax functionality within the WordPress admin area.', 'api-for-htmx'),
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
                'title' => __('Datastar Settings', 'api-for-htmx'),
                'visible_if' => ['field' => 'active_library', 'value' => 'datastar'],
                'description' => __('Datastar automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
                'fields' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'load_datastar_backend',
                        'label' => __('Load Datastar in WP Admin', 'api-for-htmx'),
                        'default' => false,
                        'help' => __('Enable Datastar functionality within the WordPress admin area.', 'api-for-htmx'),
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
                'title' => __('About', 'api-for-htmx'),
                'description' => '',
                'fields' => [
                    [
                        'type' => 'html',
                        'name' => 'about_content',
                        'label' => '',
                        'html_content' => $this->get_about_html(),
                    ],
                    [
                        'type' => 'html',
                        'name' => 'system_info',
                        'label' => '',
                        'html_content' => $this->get_system_info_html(),
                    ],
                ],
            ],
        ];
    }

    private function get_about_html(): string
    {
        return '<div class="hyperpress-about-content">'
            . '<p>' . __('Designed for developers, HyperPress brings the power and simplicity of hypermedia to your WordPress projects. It seamlessly integrates popular libraries like HTMX, Alpine AJAX, and Datastar, empowering you to create rich, dynamic user interfaces without the complexity of traditional JavaScript frameworks.', 'api-for-htmx') . '</p>'
            . '<p>' . __('Adds a new endpoint /wp-html/v1/ from which you can load any hypermedia template partial.', 'api-for-htmx') . '</p>'
            . '<p>' . __('At its core, hypermedia is an approach that empowers you to build modern, dynamic applications by extending the capabilities of HTML. Libraries like HTMX, Alpine AJAX, and Datastar allow you to harness advanced browser technologies—such as AJAX, WebSockets, and Server-Sent Events, simply by adding special attributes to your HTML, minimizing or eliminating the need for a complex JavaScript layer.', 'api-for-htmx') . '</p>'
            . '<p>' . __('Plugin repository and documentation:', 'api-for-htmx') . ' <a href="https://github.com/EstebanForge/HyperPress" target="_blank">https://github.com/EstebanForge/HyperPress</a></p>'
            . '</div>';
    }

    private function get_system_info_html(): string
    {
        $system_info_table = $this->render_system_info($this->get_system_information());

        return '<hr style="margin: 1rem 0;"><div class="hyperpress-system-info-section">
            <p>' . __('General information about your WordPress installation and this plugin status:', 'api-for-htmx') . '</p>
            ' . $system_info_table . '
        </div>';
    }

    private function render_system_info(array $system_info): string
    {
        $html = '<div class="hyperpress-system-info"><table class="widefat">';
        $html .= '<thead><tr><th>' . __('Setting', 'api-for-htmx') . '</th><th>' . __('Value', 'api-for-htmx') . '</th></tr></thead><tbody>';

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

        $options = HyperFields::getOptions($this->option_name, []);
        $plugin_version = defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : '2.0.7';
        $php_version = PHP_VERSION;
        $wp_ver = $wp_version ?? get_bloginfo('version');

        return [
            __('WordPress Version', 'api-for-htmx') => $wp_ver,
            __('PHP Version', 'api-for-htmx') => $php_version,
            __('Plugin Version', 'api-for-htmx') => $plugin_version,
            __('Active Library', 'api-for-htmx') => ucfirst($options['active_library'] ?? 'datastar'),
            __('Datastar SDK', 'api-for-htmx') => __('Available (v1.0.0-RC.3)', 'api-for-htmx'),
        ];
    }

    private function get_footer_content(): string
    {
        $plugin_version = defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : '2.0.7';

        return '<span>' . __('Active Instance: Plugin v', 'api-for-htmx') . esc_html($plugin_version) . '</span><br />'
            . __('Proudly brought to you by', 'api-for-htmx')
            . ' <a href="https://actitud.studio" target="_blank" rel="noopener noreferrer">Actitud Studio</a>.';
    }

    public function plugin_action_links(array $links): array
    {
        $links[] = '<a href="' . esc_url(admin_url('options-general.php?page=hyperpress-options')) . '">' . esc_html__('Settings', 'api-for-htmx') . '</a>';

        return $links;
    }

    private function render_api_endpoint_html(): string
    {
        ob_start();
        $api_url = hp_get_endpoint_url();
?>
        <div class="hyperpress-api-endpoint-box">
            <h2><?php echo esc_html__('HyperPress API Endpoint', 'api-for-htmx'); ?></h2>
            <div style="display:flex;align-items:center;gap:8px;max-width:100%;">
                <input type="text" readonly value="<?php echo esc_attr($api_url); ?>" id="hyperpress-api-endpoint" aria-label="<?php echo esc_attr__('API Endpoint', 'api-for-htmx'); ?>" />
                <button type="button" class="button" id="hyperpress-api-endpoint-copy"><?php echo esc_html__('Copy', 'api-for-htmx'); ?></button>
            </div>
            <p><?php echo esc_html__('Use this base URL to make requests to the HyperPress API endpoints from your frontend code.', 'api-for-htmx'); ?></p>
            <script>
                // Vanilla JS for Copy button (LOC principle)
                (function() {
                    var btn = document.getElementById('hyperpress-api-endpoint-copy');
                    var input = document.getElementById('hyperpress-api-endpoint');
                    if (btn && input) {
                        btn.addEventListener('click', function() {
                            input.select();
                            input.setSelectionRange(0, 99999);
                            try {
                                document.execCommand('copy');
                                btn.textContent = '<?php echo esc_js(__('Copied!', 'api-for-htmx')); ?>';
                                setTimeout(function() {
                                    btn.textContent = '<?php echo esc_js(__('Copy', 'api-for-htmx')); ?>';
                                }, 1200);
                            } catch (e) {
                                btn.textContent = '<?php echo esc_js(__('Error', 'api-for-htmx')); ?>';
                            }
                        });
                    }
                })();
            </script>
        </div>
<?php
        return ob_get_clean();
    }
}
