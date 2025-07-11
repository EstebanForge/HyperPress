<?php

// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

if (!hm_validate_request($hmvals, 'htmx_do_something')) {
    hm_die('Invalid request.');
}

// Do some server-side processing with the received $hmvals
sleep(5);

hm_send_header_response(
    wp_create_nonce('hmapi_nonce'),
    [
        'status'  => 'success',
        'nonce'   => wp_create_nonce('hmapi_nonce'),
        'message' => 'Server-side processing done.',
        'params'  => $hmvals,
    ]
);
