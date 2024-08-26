<?php

/**
 * Plugin Name: API for HTMX
 * Plugin URI: https://github.com/TCattd/HTMX-API-WP
 * Description: Add an API endpoint to support HTMX powered themes on your site.
 * Version: 1.0.0
 * Author: Esteban Cuevas
 * Author URI: https://actitud.xyz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: api-for-htmx
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// Don't run when...
if (defined('DOING_CRON') && DOING_CRON || defined('DOING_AJAX') && DOING_AJAX || defined('REST_REQUEST') && REST_REQUEST || defined('XMLRPC_REQUEST') && XMLRPC_REQUEST || defined('WP_CLI') && WP_CLI) {
	return;
}

// Constants
// Auto-get version from plugin header
define('HXWP_VERSION', get_file_data(__FILE__, ['Version' => 'Version'], false)['Version']);
define('HXWP_ABSPATH', plugin_dir_path(__FILE__));
define('HXWP_BASENAME', plugin_basename(__FILE__));
define('HXWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HXWP_ENDPOINT', 'wp-htmx');
define('HXWP_ENDPOINT_VERSION', 'v1');
define('HXWP_TEMPLATE_DIR', 'htmx-templates');
define('HXWP_EXT', '.htmx.php');

// TODO: implement a proper autoloader
// Main Class
require_once HXWP_ABSPATH . 'classes/class-hxwp-main.php';

// Includes
require_once HXWP_ABSPATH . 'includes/helpers.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, ['HXWP\HXWP_Activate_Deactivate', 'activate']);
register_deactivation_hook(__FILE__, ['HXWP\HXWP_Activate_Deactivate', 'deactivate']);

// Initialize the plugin
$hxwp = new HXWP\HXWP_Main();
$hxwp->run();
