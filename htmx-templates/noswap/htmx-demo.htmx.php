<?php

// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

if (!hxwp_validate_request($hxvals, 'htmx_do_something')) {
    hxwp_die('Invalid request.');
}

// Do some server-side processing with the received $hxvals
sleep(5);

hxwp_send_header_response(
    wp_create_nonce('hxwp_nonce'),
    [
        'status'  => 'success',
        'nonce'   => wp_create_nonce('hxwp_nonce'),
        'message' => 'Server-side processing done.',
        'params'  => $hxvals,
    ]
);
