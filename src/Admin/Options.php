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
        $this->datastar_manager = new DatastarLib($this->main);
        $this->htmx_manager = new HTMXLib($this->main);
        $this->alpine_ajax_manager = new AlpineAjaxLib($this->main);

        if (!hm_is_library_mode()) {
            // Register on admin_menu with default priority to ensure proper order
            add_action('admin_menu', [$this, 'register_options_page']);
            add_action('admin_init', [$this, 'register_settings']);
            add_filter('plugin_action_links_' . HMAPI_BASENAME, [$this, 'plugin_action_links']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        }
    }

    public function register_options_page(): void
    {
        // Add to Settings menu with default position (bottom)
        add_options_page(
            'Hypermedia API Options',
            'Hypermedia API',
            'manage_options',
            'hypermedia-api-options',
            [$this, 'render_options_page']
            // $position omitted → null → bottom of Settings
        );
    }

    public function render_options_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $options_page = OptionsPage::make('Hypermedia API Options', 'hypermedia-api-options')
            ->set_menu_title('Hypermedia API')
            ->set_parent_slug('options-general.php')
            ->set_capability('manage_options');

        $this->build_options_page($options_page);

        // Render the page using Fields API
        echo '<div class="wrap hmapi-options-page">';
        echo '<h1>Hypermedia API Options</h1>';

        // Render the tabs and fields
        $this->render_fields_page();

        echo '</div>';
    }

    private function render_fields_page(): void
    {
        $current_options = $this->get_current_options();

        // Create tabs using our TabsField
        $tabs_field = TabsField::make('settings_tabs', 'Settings Tabs')
            ->set_layout('horizontal');

        // Build sections for each tab
        $general_sections = $this->build_general_tab($current_options);
        $tabs_field->add_tab('general', 'General Settings', $general_sections);

        // Always include all library tabs to ensure all fields are present
        $htmx_sections = $this->build_htmx_tab($current_options);
        $tabs_field->add_tab('htmx', 'HTMX Settings', $htmx_sections);

        $alpine_sections = $this->build_alpine_tab($current_options);
        $tabs_field->add_tab('alpine', 'Alpine Ajax Settings', $alpine_sections);

        $datastar_sections = $this->build_datastar_tab($current_options);
        $tabs_field->add_tab('datastar', 'Datastar Settings', $datastar_sections);

        // About Tab
        $about_sections = $this->build_about_tab($current_options);
        $tabs_field->add_tab('about', 'About', $about_sections);

        // Render the form with tabs
        echo '<form method="post" action="options.php" id="hmapi-options-form">';
        settings_fields('hmapi_options');

        // Render tabs and sections
        $this->render_tabs_field($tabs_field, $current_options);

        submit_button();
        echo '</form>';
    }

    private function render_tabs_field(TabsField $tabs_field, array $options): void
    {
        $tabs = $tabs_field->get_tabs();
        $current_tab = $_GET['tab'] ?? 'general';
        $active_library = $options['active_library'] ?? 'htmx';

        // Determine which tabs to show
        $visible_tabs = [
            'general' => $tabs['general'],
        ];

        // Add the active library tab
        switch ($active_library) {
            case 'htmx':
                $visible_tabs['htmx'] = $tabs['htmx'];
                break;
            case 'alpinejs':
                $visible_tabs['alpine'] = $tabs['alpine'];
                break;
            case 'datastar':
                $visible_tabs['datastar'] = $tabs['datastar'];
                break;
        }

        // Always add About as last tab
        $visible_tabs['about'] = $tabs['about'];

        echo '<h2 class="nav-tab-wrapper">';
        foreach ($visible_tabs as $tab_id => $tab_data) {
            $class = ($tab_id === $current_tab) ? 'nav-tab nav-tab-active' : 'nav-tab';
            $url = add_query_arg('tab', $tab_id, admin_url('options-general.php?page=hypermedia-api-options'));
            echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">' . esc_html($tab_data['label']) . '</a>';
        }
        echo '</h2>';

        // Check if current tab is valid for the active library
        $is_valid_tab = isset($visible_tabs[$current_tab]);
        if (!$is_valid_tab && $current_tab !== 'general') {
            echo '<script>window.location.href = "' . esc_js(admin_url('options-general.php?page=hypermedia-api-options')) . '";</script>';

            return;
        }

        // Render only the current tab content
        echo '<div class="tab-content">';
        if (isset($visible_tabs[$current_tab])) {
            $tab_data = $visible_tabs[$current_tab];
            if (isset($tab_data['fields']) && is_array($tab_data['fields'])) {
                $this->render_tab_content($tab_data['fields'], $options);
            } else {
                echo '<p>No fields available for this tab.</p>';
            }
        }
        echo '</div>';
    }

    private function render_tab_content(array $sections, array $options): void
    {
        echo '<div class="tab-content">';
        $section_count = count($sections);
        $current = 0;

        foreach ($sections as $section) {
            if ($section instanceof OptionsSection) {
                $this->render_section($section, $options);

                // Add separator between sections (but not after the last one)
                $current++;
                if ($current < $section_count) {
                    echo '<hr class="section-separator">';
                }
            } else {
                // Handle direct fields if needed
                echo '<div class="field">';
                echo '<p>Field: ' . esc_html(print_r($section, true)) . '</p>';
                echo '</div>';
            }
        }
        echo '</div>';
    }

    private function render_section(OptionsSection $section, array $options): void
    {
        $section_id = $section->get_id();
        $fields = $section->get_fields();

        echo '<div class="section" id="' . esc_attr($section_id) . '">';
        echo '<h2>' . esc_html($section->get_title()) . '</h2>';
        if ($description = $section->get_description()) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }

        foreach ($fields as $field) {
            $this->render_field($field, $options);
        }

        echo '</div>';
    }

    private function render_field(Field $field, array $options): void
    {
        $name = $field->get_name();
        $value = $options[$name] ?? $field->get_default();

        echo '<div class="field">';

        switch ($field->get_type()) {
            case 'select':
                echo '<label for="' . esc_attr($name) . '">' . esc_html($field->get_label()) . '</label>';
                echo '<select name="hmapi_options[' . esc_attr($name) . ']" id="' . esc_attr($name) . '" class="regular-text">';
                foreach ($field->get_options() as $key => $label) {
                    echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                break;

            case 'checkbox':
                echo '<label><input type="checkbox" name="hmapi_options[' . esc_attr($name) . ']" value="1" ' . checked($value, true, false) . '> ' . esc_html($field->get_label()) . '</label>';
                break;

            case 'html':
                echo $field->get_html();
                break;

            default:
                echo '<label for="' . esc_attr($name) . '">' . esc_html($field->get_label()) . '</label>';
                echo '<input type="text" name="hmapi_options[' . esc_attr($name) . ']" id="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="regular-text">';
        }

        if ($description = $field->get_description()) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }

        echo '</div>';
    }

    private function build_options_page(OptionsPage $page): void
    {
        // This method is now unused - we build tabs directly in render_fields_page
    }

    private function build_general_tab(array $options)
    {
        $section = OptionsSection::make('general_settings', 'General Settings')
            ->set_description('Configure which hypermedia library to use and CDN loading preferences.');

        // API Endpoint URL (read-only)
        $api_url = home_url('/' . HMAPI_ENDPOINT . '/' . HMAPI_ENDPOINT_VERSION . '/');
        $section->add_field(
            Field::make('html', 'api_url_display', 'Hypermedia API Endpoint')
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

        return [$section];
    }

    private function build_htmx_tab(array $options)
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

        return [$section, $extensions_section];
    }

    private function build_alpine_tab(array $options)
    {
        $section = OptionsSection::make('alpine_settings', 'Alpine Ajax Settings')
            ->set_description('Alpine.js automatically loads when selected as the active library. Configure backend loading below.');

        $section->add_field(
            Field::make('checkbox', 'load_alpinejs_backend', 'Load Alpine Ajax in WP Admin')
                ->set_default($options['load_alpinejs_backend'] ?? false)
                ->set_description('Enable Alpine Ajax functionality within the WordPress admin area.')
        );

        return [$section];
    }

    private function build_datastar_tab(array $options)
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

        return [$section];
    }

    private function build_about_tab(array $options)
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
        $section2 = OptionsSection::make('system_info', 'System Information')
            ->set_description('Technical details about your WordPress installation and plugin configuration.');

        $section2->add_field(
            Field::make('html', 'system_information', 'System Information')
                ->set_html($this->render_system_info($system_info))
        );

        return [$section, $section2];
    }

    public function register_settings(): void
    {
        register_setting('hmapi_options', $this->option_name, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_options'],
            'default' => $this->get_default_options(),
        ]);

        // Add settings sections
        add_settings_section(
            'hmapi_general_section',
            'General Settings',
            [$this, 'render_general_section'],
            'hypermedia-api-options'
        );

        // Add settings fields
        add_settings_field(
            'hmapi_active_library',
            'Active Hypermedia Library',
            [$this, 'render_active_library_field'],
            'hypermedia-api-options',
            'hmapi_general_section'
        );

        add_settings_field(
            'hmapi_load_from_cdn',
            'Load from CDN',
            [$this, 'render_load_from_cdn_field'],
            'hypermedia-api-options',
            'hmapi_general_section'
        );
    }

    public function render_general_section(): void
    {
        echo '<p>Configure which hypermedia library to use and CDN loading preferences.</p>';
    }

    public function render_active_library_field(): void
    {
        $options = $this->get_current_options();
        $current = $options['active_library'] ?? 'htmx';

        echo '<select name="hmapi_options[active_library]">';
        echo '<option value="htmx" ' . selected($current, 'htmx', false) . '>HTMX</option>';
        echo '<option value="alpinejs" ' . selected($current, 'alpinejs', false) . '>Alpine Ajax</option>';
        echo '<option value="datastar" ' . selected($current, 'datastar', false) . '>Datastar</option>';
        echo '</select>';
        echo '<p class="description">Select the primary hypermedia library to activate and configure.</p>';
    }

    public function render_load_from_cdn_field(): void
    {
        $options = $this->get_current_options();
        $current = $options['load_from_cdn'] ?? false;

        echo '<label><input type="checkbox" name="hmapi_options[load_from_cdn]" value="1" ' . checked($current, true, false) . '> Load active library from CDN</label>';
        echo '<p class="description">Load libraries from CDN for better performance, or disable to use local copies for version consistency.</p>';
    }

    public function sanitize_options($input): array
    {
        // Handle case when no input is provided
        if (!is_array($input)) {
            return $this->get_current_options();
        }
        
        // Get current options to preserve values not in input
        $current_options = $this->get_current_options();
        $sanitized = $current_options;

        // Update all fields that are present in the input
        if (isset($input['active_library'])) {
            $sanitized['active_library'] = sanitize_text_field($input['active_library']);
        }
        
        if (isset($input['load_from_cdn'])) {
            $sanitized['load_from_cdn'] = (bool) $input['load_from_cdn'];
        }
        
        if (isset($input['load_hyperscript'])) {
            $sanitized['load_hyperscript'] = (bool) $input['load_hyperscript'];
        }
        
        if (isset($input['load_alpinejs_with_htmx'])) {
            $sanitized['load_alpinejs_with_htmx'] = (bool) $input['load_alpinejs_with_htmx'];
        }
        
        if (isset($input['set_htmx_hxboost'])) {
            $sanitized['set_htmx_hxboost'] = (bool) $input['set_htmx_hxboost'];
        }
        
        if (isset($input['load_htmx_backend'])) {
            $sanitized['load_htmx_backend'] = (bool) $input['load_htmx_backend'];
        }
        
        if (isset($input['load_alpinejs_backend'])) {
            $sanitized['load_alpinejs_backend'] = (bool) $input['load_alpinejs_backend'];
        }
        
        if (isset($input['load_datastar_backend'])) {
            $sanitized['load_datastar_backend'] = (bool) $input['load_datastar_backend'];
        }

        // Sanitize HTMX extensions - keep hyphenated keys as-is
        $extensions = $this->get_htmx_extensions();
        foreach ($extensions as $key => $details) {
            if (isset($input['load_extension_' . $key])) {
                $sanitized['load_extension_' . $key] = (bool) $input['load_extension_' . $key];
            }
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
