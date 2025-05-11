<?php

/**
 * Load plugin Options
 *
 * @package HXWP
 * @since   2023
 */

namespace HXWP\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Options Class
 */
class Options
{
    private $option_name = 'hxwp_options';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
        add_filter('plugin_action_links_' . HXWP_BASENAME, [$this, 'plugin_action_links']);
    }

    public function add_plugin_page()
    {
        add_options_page(
            esc_html__('HTMX Options', 'api-for-htmx'),
            esc_html__('HTMX Options', 'api-for-htmx'),
            'manage_options',
            'htmx-options',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page()
    {
        ?>
<div class="wrap">
	<h2><?php esc_html_e('HTMX Options', 'api-for-htmx'); ?>
	</h2>
	<form method="post" action="options.php">
		<?php
                        settings_fields('hxwp_options_group');
        do_settings_sections('htmx-options');
        submit_button(esc_html__('Save Changes', 'api-for-htmx'));
        ?>
	</form>

	<p class="description">
		<?php
        // Translators: %s = Actitud Studio URL
        printf(
            esc_html__('Proudly brought to you by %s.', 'api-for-htmx'),
            '<a href="https://actitud.xyz" target="_blank">' . esc_html__('Actitud Studio', 'api-for-htmx') . '</a>'
        );
        ?>
	</p>
</div>
<?php
    }

    public function page_init()
    {
        // Default values. 1 = checked, 0 = unchecked
        $default_values = [
            'load_from_cdn'     => 0,
            'load_hyperscript'  => 0,
            'load_alpinejs'     => 0,
            'set_htmx_hxboost'  => 0,
            'load_htmx_backend' => 0,
        ];

        // Retrieve current options
        $options = wp_parse_args(get_option($this->option_name, []), $default_values);

        register_setting(
            'hxwp_options_group',
            $this->option_name,
            [$this, 'sanitize']
        );

        add_settings_section(
            'hxwp_setting_section',
            esc_html__('Settings', 'api-for-htmx'),
            [$this, 'print_section_info'],
            'htmx-options'
        );

        add_settings_field(
            'load_from_cdn',
            esc_html__('Load scripts from CDN', 'api-for-htmx'),
            [$this, 'load_from_cdn_callback'],
            'htmx-options',
            'hxwp_setting_section',
            ['label_for' => 'load_from_cdn', 'options' => $options]
        );

        add_settings_field(
            'load_hyperscript',
            esc_html__('Load Hyperscript', 'api-for-htmx'),
            [$this, 'load_hyperscript_callback'],
            'htmx-options',
            'hxwp_setting_section',
            ['label_for' => 'load_hyperscript', 'options' => $options]
        );

        add_settings_field(
            'load_alpinejs',
            esc_html__('Load Alpine.js', 'api-for-htmx'),
            [$this, 'load_alpinejs_callback'],
            'htmx-options',
            'hxwp_setting_section',
            ['label_for' => 'load_alpinejs', 'options' => $options]
        );

        add_settings_field(
            'set_htmx_hxboost',
            esc_html__('Auto hx-boost="true"', 'api-for-htmx'),
            [$this, 'load_htmx_hxboost_callback'],
            'htmx-options',
            'hxwp_setting_section',
            ['label_for' => 'set_htmx_hxboost', 'options' => $options]
        );

        add_settings_field(
            'load_htmx_backend',
            esc_html__('Load HTMX/Hyperscript at WP backend', 'api-for-htmx'),
            [$this, 'load_htmx_backend_callback'],
            'htmx-options',
            'hxwp_setting_section',
            ['label_for' => 'load_htmx_backend', 'options' => $options]
        );

        add_settings_field(
            'load_alpinejs_backend',
            esc_html__('Load Alpine.js at WP backend', 'api-for-htmx'),
            [$this, 'load_alpinejs_backend_callback'],
            'htmx-options',
            'hxwp_setting_section',
            ['label_for' => 'load_alpinejs_backend', 'options' => $options]
        );

        add_settings_section(
            'hxwp_setting_section_extensions',
            esc_html__('Extensions', 'api-for-htmx'),
            [$this, 'print_section_info_extensions'],
            'htmx-options'
        );

        // HTMX extensions to load
        $extensions = [
            // Official extensions
            'sse'                   => 'Server send events. Uni-directional server push messaging via EventSource',
            'ws'                    => 'WebSockets. Bi-directional connection to WebSocket servers',
            'htmx-1-compat'         => 'HTMX 1.x compatibility mode. Rolls back most of the behavioral changes of htmx 2 to the htmx 1 defaults.',
            'preload'               => 'preloads selected href and hx-get targets based on rules you control.',
            'response-targets'      => 'allows to specify different target elements to be swapped when different HTTP response codes are received',
            // Community extensions
            'ajax-header'           => 'includes the commonly-used X-Requested-With header that identifies ajax requests in many backend frameworks',
            'alpine-morph'          => '	an extension for using the Alpine.js morph plugin as the swapping mechanism in htmx.',
            'class-tools'           => 'an extension for manipulating timed addition and removal of classes on HTML elements',
            'client-side-templates' => 'support for client side template processing of JSON/XML responses',
            'debug'                 => 'an extension for debugging of a particular element using htmx',
            //'disable-element'       => 'This extension disables an element during an htmx request, when configured on the element triggering the request. Note that this functionality is now part of the core of htmx via the hx-disabled-elt attribute',
            'event-header'          => 'includes a JSON serialized version of the triggering event, if any',
            'include-vars'          => 'allows you to include additional values in a request',
            'json-enc'              => 'use JSON encoding in the body of requests, rather than the default x-www-form-urlencoded',
            'loading-states'        => 'allows you to disable inputs, add and remove CSS classes to any element while a request is in-flight.',
            'morphdom-swap'         => 'Provides a morph swap strategy based on the morphdom morphing library.',
            'multi-swap'            => 'This extension allows you to swap multiple elements marked from the HTML response. You can also choose for each element which swap method should be used.',
            'no-cache'              => 'This extension forces HTMX to bypass client caches and make a new request. An `hx-no-cache` header is also added to allow server-side caching to be bypassed.',
            'path-deps'             => 'This extension supports expressing inter-element dependencies based on paths, inspired by the intercooler.js dependencies mechanism.',
            'path-params'           => 'allows to use parameters for path variables instead of sending them in query or body',
            'remove-me'             => 'allows you to remove an element after a given amount of time',
            'restored'              => 'allows you to trigger events when the back button has been pressed',
        ];

        foreach ($extensions as $key => $extension) {
            add_settings_field(
                'load_extension_' . $key,
                esc_html__('Load', 'api-for-htmx') . ' ' . $key,
                [$this, 'setting_extensions_callback'],
                'htmx-options',
                'hxwp_setting_section_extensions',
                [
                    'label_for' => 'load_extension_' . $key,
                    'key'       => $key,
                    'extension' => $extension,
                    'options'   => $options
                ]
            );
        }
    }

    public function sanitize($input)
    {
        // load_from_cdn
        if (isset($input['load_from_cdn'])) {
            $input['load_from_cdn'] = isset($input['load_from_cdn']) ? 1 : 0;
        } else {
            $input['load_from_cdn'] = 0;
        }

        // load_hyperscript
        if (isset($input['load_hyperscript'])) {
            $input['load_hyperscript'] = isset($input['load_hyperscript']) ? 1 : 0;
        } else {
            $input['load_hyperscript'] = 0;
        }

        // load_alpinejs
        if (isset($input['load_alpinejs'])) {
            $input['load_alpinejs'] = isset($input['load_alpinejs']) ? 1 : 0;
        } else {
            $input['load_alpinejs'] = 0;
        }

        // set_htmx_hxboost
        if (isset($input['set_htmx_hxboost'])) {
            $input['set_htmx_hxboost'] = isset($input['set_htmx_hxboost']) ? 1 : 0;
        } else {
            $input['set_htmx_hxboost'] = 0;
        }

        // load_htmx_backend
        if (isset($input['load_htmx_backend'])) {
            $input['load_htmx_backend'] = isset($input['load_htmx_backend']) ? 1 : 0;
        } else {
            $input['load_htmx_backend'] = 0;
        }

        // If load extensions, options begins with load_extension_
        foreach ($input as $key => $value) {
            if (strpos($key, 'load_extension_') === 0) {
                $input[$key] = isset($input[$key]) ? 1 : 0;
            }
        }

        return $input;
    }

    public function print_section_info()
    {
        echo '<p>' . esc_html__('HTMX API for WordPress. ', 'api-for-htmx') . '<a href="https://github.com/EstebanForge/HTMX-API-WP/" target="_blank">' . esc_html__('Learn more', 'api-for-htmx') . '</a>.</p>';
        echo '<p>' . esc_html__('HTMX is always loaded at the frontend while the plugin is active.', 'api-for-htmx') . '</p>';
    }

    public function print_section_info_extensions()
    {
        echo '<p>' . esc_html__('Choose which ', 'api-for-htmx') . '<a href="' . esc_url('https://extensions.htmx.org/') . '" target="_blank">' . esc_html__('HTMX extensions', 'api-for-htmx') . '</a>' . esc_html__(' to load.', 'api-for-htmx') . '</p>';
    }

    public function load_from_cdn_callback($args)
    {
        $options = $args['options'];
        $checked = isset($options['load_from_cdn']) && $options['load_from_cdn'] ? 'checked' : '';

        echo '<input type="checkbox" id="load_from_cdn" name="' . $this->option_name . '[load_from_cdn]" value="1" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__('Choose whether to load HTMX and Hypertext from a CDN or locally. Keep it disabled to load HTMX and Hypertext locally.', 'api-for-htmx') . '</p>';
    }

    public function load_hyperscript_callback($args)
    {
        $options = $args['options'];
        $checked = isset($options['load_hyperscript']) && $options['load_hyperscript'] ? 'checked' : '';

        echo '<input type="checkbox" id="load_hyperscript" name="' . $this->option_name . '[load_hyperscript]" value="1" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__('Choose whether to load Hyperscript or not. Keep it enabled to load Hyperscript. HTMX is always loaded.', 'api-for-htmx') . '</p>';
    }

    public function load_alpinejs_callback($args)
    {
        $options = $args['options'];
        $checked = isset($options['load_alpinejs']) && $options['load_alpinejs'] ? 'checked' : '';

        echo '<input type="checkbox" id="load_alpinejs" name="' . $this->option_name . '[load_alpinejs]" value="1" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__('Choose whether to load Alpine.js or not. Keep it enabled to load Alpine.js. HTMX is always loaded.', 'api-for-htmx') . '</p>';
    }

    public function load_htmx_hxboost_callback($args)
    {
        $options = $args['options'];
        $checked = isset($options['set_htmx_hxboost']) && $options['set_htmx_hxboost'] ? 'checked' : '';

        echo '<input type="checkbox" id="set_htmx_hxboost" name="' . $this->option_name . '[set_htmx_hxboost]" value="1" ' . $checked . ' />';

        echo '<p class="description">' . esc_html__('HTMX API for WordPress. ', 'api-for-htmx') . '<a href="' . esc_url('https://github.com/EstebanForge/HTMX-API-WP/') . '" target="_blank">' . esc_html__('Learn more', 'api-for-htmx') . '</a>.</p>';
    }

    public function load_htmx_backend_callback($args)
    {
        $options = $args['options'];
        $checked = isset($options['load_htmx_backend']) && $options['load_htmx_backend'] ? 'checked' : '';

        echo '<input type="checkbox" id="load_htmx_backend" name="' . $this->option_name . '[load_htmx_backend]" value="1" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__('Choose whether to load HTMX (and Hyperscript if activated) at WP backend (wp-admin) or not. HTMX is always loaded at the site\'s frontend.', 'api-for-htmx') . '</p>';
    }

    public function load_alpinejs_backend_callback($args)
    {
        $options = $args['options'];
        $checked = isset($options['load_alpinejs_backend']) && $options['load_alpinejs_backend'] ? 'checked' : '';

        echo '<input type="checkbox" id="load_alpinejs_backend" name="' . $this->option_name . '[load_alpinejs_backend]" value="1" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__('Choose whether to load Alpine.js at WP backend (wp-admin) or not. Alpine.js is always loaded at the site\'s frontend.', 'api-for-htmx') . '</p>';
    }

    public function setting_extensions_callback($args)
    {
        $options   = $args['options'];
        $extension = $args['extension'];
        $key       = $args['key'];

        $checked   = isset($options['load_extension_' . $key]) ? checked(1, $options['load_extension_' . $key], false) : '';

        echo '<input type="checkbox" id="load_extension_' . $key . '" name="' . $this->option_name . '[load_extension_' . $key . ']" value="1" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__('Load', 'api-for-htmx') . ' ' . $extension . esc_html__(' extension.', 'api-for-htmx') . '</p>';
    }

    /**
     * Add link to plugins settings page on plugins list page
     *
     * @param array $links
     *
     * @return array
     */
    public function plugin_action_links($links)
    {
        $links[] = '<a href="' . esc_url(admin_url('options-general.php?page=htmx-options')) . '">' . esc_html__('Settings', 'api-for-htmx') . '</a>';

        return $links;
    }
}
?>
