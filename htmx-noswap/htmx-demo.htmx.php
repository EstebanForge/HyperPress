<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Check if nonce is valid.
if (!isset($_SERVER['HTTP_X_WP_NONCE']) || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'hxwp_nonce')) {
	hxwp_die('Nonce verification failed.');
}

// Action = htmx_do_something
if (!isset($hxvals['action']) || $hxvals['action'] != 'htmx_do_something') {
	hxwp_die('Invalid action.');
}

// Do some server-side processing with the received $hxvals
sleep(5);
