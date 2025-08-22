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

// Validate the request to ensure the correct plugin is being uninstalled
$plugin = isset($_REQUEST['plugin']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['plugin'])) : '';
$action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['action'])) : '';

$validPlugins = [
  'hyperpress/hyperpress.php',
  'hyperpress/api-for-htmx.php', // legacy entry point
  'api-for-htmx/api-for-htmx.php', // legacy entry point
];

if ($action !== 'delete-plugin' || !in_array($plugin, $validPlugins, true)) {
  wp_die('Error uninstalling: wrong plugin.');
}

// Clears HTMX API for WP options
global $wpdb;

$hp_options = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_hxwp_%' OR option_name LIKE 'hxwp_%' OR option_name LIKE '_hyperpress_%' OR option_name LIKE 'hyperpress_%'");

if (is_array($hp_options) && !empty($hp_options)) {
	foreach ($hp_options as $option) {
		delete_option($option->option_name);
	}
}

// Flush rewrite rules
flush_rewrite_rules();
