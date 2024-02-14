<?php

/**
 * Load plugin Options
 *
 * @package HXWP
 * @since   2023
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Options Class
 */
class HXWP_Options
{
	private $option_name = 'hxwp_options';

	public function __construct()
	{
		add_action('admin_menu', [$this, 'add_plugin_page']);
		add_action('admin_init', [$this, 'page_init']);
	}

	public function add_plugin_page()
	{
		add_options_page(
			__('HTMX Options', 'hxwp'),
			__('HTMX Options', 'hxwp'),
			'manage_options',
			'htmx-options',
			[$this, 'create_admin_page']
		);
	}

	public function create_admin_page()
	{
?>
		<div class="wrap">
			<h2><?php _e('HTMX Options', 'hxwp'); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields('hxwp_options_group');
				do_settings_sections('htmx-options');
				submit_button(__('Save Changes', 'hxwp'));
				?>
			</form>
		</div>
<?php
	}

	public function page_init()
	{
		// Default values
		$default_values = [
			'load_from_cdn'    => 0, // Set to 1 for checked, 0 for unchecked
			'load_hyperscript' => 0,
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
			__('Settings', 'hxwp'),
			[$this, 'print_section_info'],
			'htmx-options'
		);

		add_settings_field(
			'load_from_cdn',
			__('Load scripts from CDN', 'hxwp'),
			[$this, 'load_from_cdn_callback'],
			'htmx-options',
			'hxwp_setting_section',
			['label_for' => 'load_from_cdn', 'options' => $options]
		);

		add_settings_field(
			'load_hyperscript',
			__('Load Hyperscript', 'hxwp'),
			[$this, 'load_hyperscript_callback'],
			'htmx-options',
			'hxwp_setting_section',
			['label_for' => 'load_hyperscript', 'options' => $options]
		);

		add_settings_section(
			'hxwp_setting_section_extensions',
			__('Extensions', 'hxwp'),
			[$this, 'print_section_info_extensions'],
			'htmx-options'
		);

		// HTMX extensions to load
		$extensions = [
			'ajax-header'           => 'includes the commonly-used X-Requested-With header that identifies ajax requests in many backend frameworks',
			'alpine-morph'          => '	an extension for using the Alpine.js morph plugin as the swapping mechanism in htmx.',
			'class-tools'           => 'an extension for manipulating timed addition and removal of classes on HTML elements',
			'client-side-templates' => 'support for client side template processing of JSON/XML responses',
			'debug'                 => 'an extension for debugging of a particular element using htmx',
			'event-header'          => 'includes a JSON serialized version of the triggering event, if any',
			'head-support'          => 'support for merging the head tag from responses into the existing documents head',
			'include-vars'          => 'allows you to include additional values in a request',
			'json-enc'              => 'use JSON encoding in the body of requests, rather than the default x-www-form-urlencoded',
			'loading-states'        => 'allows you to disable inputs, add and remove CSS classes to any element while a request is in-flight.',
			'method-override'       => 'use the X-HTTP-Method-Override header for non-GET and POST requests',
			'morphdom-swap'         => 'an extension for using the morphdom library as the swapping mechanism in htmx.',
			'multi-swap'            => 'allows to swap multiple elements with different swap methods',
			'path-deps'             => '	an extension for expressing path-based dependencies similar to intercoolerjs',
			'preload'               => 'preloads selected href and hx-get targets based on rules you control.',
			'remove-me'             => 'allows you to remove an element after a given amount of time',
			'response-targets'      => 'allows to specify different target elements to be swapped when different HTTP response codes are received',
			'restored'              => 'allows you to trigger events when the back button has been pressed',
			'sse'                   => 'Server send events. Uni-directional server push messaging via EventSource',
			'ws'                    => 'WebSockets. Bi-directional connection to WebSocket servers',
			'path-params'           => 'allows to use parameters for path variables instead of sending them in query or body',
		];

		foreach ($extensions as $key => $extension) {
			add_settings_field(
				'load_extension_' . $key,
				__('Load', 'hxwp') . ' ' . $key,
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
		echo __('General Options.', 'hxwp');
	}

	public function print_section_info_extensions()
	{
		echo __('Choose which HTMX extensions to load.', 'hxwp');
	}

	public function load_from_cdn_callback($args)
	{
		$options = $args['options'];
		$checked = isset($options['load_from_cdn']) && $options['load_from_cdn'] ? 'checked' : '';

		echo '<input type="checkbox" id="load_from_cdn" name="' . $this->option_name . '[load_from_cdn]" value="1" ' . $checked . ' />';
		echo '<p class="description">' . __('Choose whether to load HTMX and Hypertext from a CDN or locally. Keep it disabled to load HTMX and Hypertext locally.', 'hxwp') . '</p>';
	}

	public function load_hyperscript_callback($args)
	{
		$options = $args['options'];
		$checked = isset($options['load_hyperscript']) && $options['load_hyperscript'] ? 'checked' : '';

		echo '<input type="checkbox" id="load_hyperscript" name="' . $this->option_name . '[load_hyperscript]" value="1" ' . $checked . ' />';
		echo '<p class="description">' . __('Choose whether to load Hyperscript or not. Keep it enabled to load Hyperscript. HTMX is always loaded.', 'hxwp') . '</p>';
	}

	public function setting_extensions_callback($args)
	{
		$options   = $args['options'];
		$extension = $args['extension'];
		$key       = $args['key'];

		$checked   = isset($options['load_extension_' . $key]) ? checked(1, $options['load_extension_' . $key], false) : '';

		echo '<input type="checkbox" id="load_extension_' . $key . '" name="' . $this->option_name . '[load_extension_' . $key . ']" value="1" ' . $checked . ' />';
		echo '<p class="description">' . __('Load', 'hxwp') . ' ' . $extension . __(' extension.', 'hxwp') . '</p>';
	}
}
