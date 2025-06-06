<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://actitud.xyz
 * @since      2023-12-01
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Nonce?: https://core.trac.wordpress.org/ticket/38661

if (isset($_REQUEST['plugin']) && $_REQUEST['plugin'] != 'api-for-htmx/api-for-htmx.php' && $_REQUEST['action'] != 'delete-plugin') {
	wp_die('Error uninstalling: wrong plugin.');
}

// Clears HTMX API for WP options
global $wpdb;

$hmapi_options = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_hxwp_%' OR option_name LIKE 'hxwp_%' OR option_name LIKE '_hmapi_%' OR option_name LIKE 'hmapi_%'");

if (is_array($hmapi_options) && !empty($hmapi_options)) {
	foreach ($hmapi_options as $option) {
		delete_option($option->option_name);
	}
}

// Flush rewrite rules
flush_rewrite_rules();
