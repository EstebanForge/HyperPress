<?php

// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

// Rate limiting check
if (hm_ds_is_rate_limited()) {
    return;
}

if (!hm_validate_request($hmvals, 'datastar_do_something')) {
    hm_die('Invalid request.');
}

// Do some server-side processing with the received $hmvals
sleep(1); // Simulate processing time

// Different responses based on demo type
$demo_type = $hmvals['demo_type'] ?? 'default';
$message = '';
$status = 'success';
$extra_data = [];

switch ($demo_type) {
    case 'simple_get':
        $message = 'Datastar GET request processed successfully!';
        break;
    case 'post_with_data':
        $user_data = $hmvals['user_data'] ?? 'No data';
        $message = 'Datastar POST processed. You sent: ' . esc_html($user_data);
        break;
    case 'form_submission':
        $name = $hmvals['name'] ?? 'Unknown';
        $email = $hmvals['email'] ?? 'No email';
        $message = 'Form submitted successfully! Name: ' . esc_html($name) . ', Email: ' . esc_html($email);
        // Reset form data
        $extra_data['formData'] = ['name' => '', 'email' => ''];
        break;
    case 'fetch_merge':
        $message = 'Data fetched and merged successfully!';
        $extra_data['serverTime'] = current_time('Y-m-d H:i:s');
        $extra_data['randomNumber'] = wp_rand(1, 1000);
        break;
    default:
        $message = 'Datastar request processed via noswap template.';
}

// For Datastar, we need to send the response in a format that can be merged into the store
$response_data = [
    'status'    => $status,
    'nonce'     => wp_create_nonce('hmapi_nonce'),
    'message'   => $message,
    'demo_type' => $demo_type,
    'params'    => $hmvals,
    'timestamp' => current_time('mysql'),
];

// Merge any extra data
$response_data = array_merge($response_data, $extra_data);

// Send appropriate headers for Datastar store merging
if (!headers_sent()) {
    // For Datastar, we can send data that gets merged into the store
    header('Content-Type: text/vnd.datastar');

    // Send a merge fragment that updates the store
    echo 'data: merge ' . wp_json_encode($response_data) . "\n\n";
} else {
    // Fallback to standard response
    hm_send_header_response(
        wp_create_nonce('hmapi_nonce'),
        $response_data
    );
}
