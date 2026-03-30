<?php

// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

if (!hp_validate_request($hp_vals, 'alpine_ajax_do_something')) {
    hp_die('Invalid request.');
}

// Do some server-side processing with the received $hp_vals
sleep(2); // Simulate processing time

// Different responses based on demo type
$demo_type = $hp_vals['demo_type'] ?? 'default';
$message = '';
$status = 'success';

switch ($demo_type) {
    case 'simple_get':
        $message = 'Alpine Ajax GET request processed successfully!';
        break;
    case 'post_with_data':
        $user_data = $hp_vals['user_data'] ?? 'No data';
        $message = 'Alpine Ajax POST processed. You sent: ' . esc_html($user_data);
        break;
    case 'form_submission':
        $name = $hp_vals['name'] ?? 'Unknown';
        $email = $hp_vals['email'] ?? 'No email';
        $message = 'Form submitted successfully! Name: ' . esc_html($name) . ', Email: ' . esc_html($email);
        break;
    default:
        $message = 'Alpine Ajax request processed via noswap template.';
}

hp_send_header_response(
    wp_create_nonce('hyperpress_nonce'),
    [
        'status'    => $status,
        'nonce'     => wp_create_nonce('hyperpress_nonce'),
        'message'   => $message,
        'demo_type' => $demo_type,
        'params'    => $hp_vals,
        'timestamp' => current_time('mysql'),
    ]
);
