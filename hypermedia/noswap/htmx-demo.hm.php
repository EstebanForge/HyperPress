<?php

// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

if (!hp_validate_request($hp_vals, 'htmx_do_something')) {
    hp_die('Invalid request.');
}

// Do some server-side processing with the received $hp_vals
sleep(5);

hp_send_header_response(
    wp_create_nonce('hyperpress_nonce'),
    [
        'status'  => 'success',
        'nonce'   => wp_create_nonce('hyperpress_nonce'),
        'message' => 'Server-side processing done.',
        'params'  => $hp_vals,
    ]
);
