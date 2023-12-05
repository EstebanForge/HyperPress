<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Check if nonce is valid.
if (!isset($_SERVER['HTTP_X_WP_NONCE']) && !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'hxwp_nonce')) {
	wp_die('Nonce verification failed.');
}

// Action = htmx_do_something
if (!isset($hxparams['action']) || $hxparams['action'] != 'htmx_do_something') {
	wp_die('Invalid action.');
}

// Do some server-side processing with the received $hxparams
sleep(5);
