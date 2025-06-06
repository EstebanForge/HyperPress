<?php

// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

if (!hmapi_validate_request($hmvals, 'alpine_ajax_do_something')) {
    hmapi_die('Invalid request.');
}

// Do some server-side processing with the received $hmvals
sleep(2); // Simulate processing time

// Different responses based on demo type
$demo_type = $hmvals['demo_type'] ?? 'default';
$message = '';
$status = 'success';

switch ($demo_type) {
    case 'simple_get':
        $message = 'Alpine Ajax GET request processed successfully!';
        break;
    case 'post_with_data':
        $user_data = $hmvals['user_data'] ?? 'No data';
        $message = 'Alpine Ajax POST processed. You sent: ' . esc_html($user_data);
        break;
    case 'form_submission':
        $name = $hmvals['name'] ?? 'Unknown';
        $email = $hmvals['email'] ?? 'No email';
        $message = 'Form submitted successfully! Name: ' . esc_html($name) . ', Email: ' . esc_html($email);
        break;
    default:
        $message = 'Alpine Ajax request processed via noswap template.';
}

hmapi_send_header_response(
    wp_create_nonce('hmapi_nonce'),
    [
        'status'    => $status,
        'nonce'     => wp_create_nonce('hmapi_nonce'),
        'message'   => $message,
        'demo_type' => $demo_type,
        'params'    => $hmvals,
        'timestamp' => current_time('mysql'),
    ]
);
