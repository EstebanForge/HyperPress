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

$hxwp_options = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_hxwp_%' OR option_name LIKE 'hxwp_%'");

if (is_array($hxwp_options) && !empty($hxwp_options)) {
	foreach ($hxwp_options as $option) {
		delete_option($option->option_name);
	}
}

// Flush rewrite rules
flush_rewrite_rules();
