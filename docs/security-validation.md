# Security & Validation Functions

**`hm_validate_request(array $hmvals = null, string $action = null): bool`**

Validates hypermedia requests by checking nonces and optionally validating specific actions. Supports both new (`hmapi_nonce`) and legacy (`hxwp_nonce`) nonce formats.

Note: This function is designed for traditional HTMX/Alpine Ajax requests. For Datastar SSE endpoints, nonce validation works differently since SSE connections don't follow the same request pattern. Consider alternative security measures for SSE endpoints (user capability checks, rate limiting, etc.).

```php
// Basic nonce validation (works for all hypermedia libraries)
if (!hm_validate_request()) {
    hm_die('Security check failed');
}

// Validate specific action
if (!hm_validate_request($_REQUEST, 'delete_post')) {
    hm_die('Invalid action');
}

// Validate custom data array
$custom_data = ['action' => 'save_settings', '_wpnonce' => $_POST['_wpnonce']];
if (!hm_validate_request($custom_data, 'save_settings')) {
    hm_die('Validation failed');
}

// Datastar SSE endpoint with real-time validation
// hypermedia/validate-form.hm.php
$signals = hm_ds_read_signals();
$email = $signals['email'] ?? '';
$password = $signals['password'] ?? '';

// Validate email in real-time
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    hm_ds_patch_elements('<div class="error">Valid email required</div>', ['selector' => '#email-error']);
    hm_ds_patch_signals(['email_valid' => false]);
} else {
    hm_ds_remove_elements('#email-error');
    hm_ds_patch_signals(['email_valid' => true]);
}

// Validate password strength
if (strlen($password) < 8) {
    hm_ds_patch_elements('<div class="error">Password must be 8+ characters</div>', ['selector' => '#password-error']);
    hm_ds_patch_signals(['password_valid' => false]);
} else {
    hm_ds_remove_elements('#password-error');
    hm_ds_patch_signals(['password_valid' => true]);
}
```
